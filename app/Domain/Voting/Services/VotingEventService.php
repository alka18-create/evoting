<?php

namespace App\Domain\Voting\Services;

use App\Domain\Auditing\Services\AuditLogger;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use App\Support\VotingToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class VotingEventService
{
    /**
     * Assign semua voter aktif ke event: buat VotingEventVoter + VoterEligibility per election.
     * Idempotent: skip voter yang sudah ada.
     *
     * @return array{event_voters_created: int, eligibilities_created: int}
     */
    public function assignAllActiveVoters(VotingEvent $event): array
    {
        $event->loadMissing('elections');
        $electionIds = $event->elections->pluck('id');

        if ($electionIds->isEmpty()) {
            return ['event_voters_created' => 0, 'eligibilities_created' => 0];
        }

        $voters = Voter::where('is_active', true)->get();
        $eventVotersCreated = 0;
        $eligibilitiesCreated = 0;

        DB::transaction(function () use ($voters, $event, $electionIds, &$eventVotersCreated, &$eligibilitiesCreated) {
            foreach ($voters as $voter) {
                $evv = VotingEventVoter::firstOrCreate(
                    ['voting_event_id' => $event->id, 'voter_id' => $voter->id]
                );
                if ($evv->wasRecentlyCreated) {
                    $eventVotersCreated++;
                }

                foreach ($electionIds as $electionId) {
                    $elig = VoterEligibility::firstOrNew(
                        ['election_id' => $electionId, 'voter_id' => $voter->id]
                    );
                    if (! $elig->exists) {
                        $elig->forceFill(['status' => 'ELIGIBLE'])->save();
                        $eligibilitiesCreated++;
                    }
                }
            }
        });

        AuditLogger::log(
            action: 'VOTING_EVENT_ASSIGN_VOTERS',
            resourceType: 'VotingEvent',
            resourceId: $event->id,
            metadata: ['event_voters_created' => $eventVotersCreated, 'eligibilities_created' => $eligibilitiesCreated]
        );

        return ['event_voters_created' => $eventVotersCreated, 'eligibilities_created' => $eligibilitiesCreated];
    }

    /**
     * Generate token kuat per event untuk semua VotingEventVoter yang belum punya token.
     * Hash-only: disimpan sebagai token_hash + expiry saja.
     * Plain hanya dikembalikan sekali via $issued (jangan di-log).
     *
     * @return Collection<int, array{student_id: string, name: string, class_name: string, token: string}>
     */
    public function generateTokens(VotingEvent $event): Collection
    {
        $pending = VotingEventVoter::where('voting_event_id', $event->id)
            ->whereNull('token_hash')
            ->with('voter')
            ->get();

        if ($pending->isEmpty()) {
            return collect();
        }

        $issued = collect();
        $existingHashes = VotingEventVoter::where('voting_event_id', $event->id)
            ->whereNotNull('token_hash')
            ->pluck('token_hash')
            ->flip()
            ->toArray();

        foreach ($pending as $evv) {
            // P1-04: retry + tangani race unique index (23505).
            $stored = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                [$plain, $hash] = VotingToken::generateUnique($existingHashes);

                try {
                    $evv->forceFill([
                        'token_hash' => $hash,
                        'expires_at' => $event->ends_at,
                    ])->save();
                    $stored = ['plain' => $plain, 'hash' => $hash];
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (($e->errorInfo[0] ?? null) !== '23505') {
                        throw $e;
                    }
                    unset($existingHashes[$hash]);
                }
            }

            if ($stored === null) {
                continue;
            }

            $issued->push([
                'student_id' => $evv->voter->student_id,
                'name' => $evv->voter->name,
                'class_name' => $evv->voter->class_name,
                'token' => $stored['plain'],
            ]);

            AuditLogger::log(
                action: 'VOTING_EVENT_TOKEN_ISSUED',
                resourceType: 'VotingEventVoter',
                resourceId: $evv->id,
                metadata: ['voting_event_id' => $event->id, 'voter_id' => $evv->voter_id]
            );
        }

        return $issued;
    }

    /**
     * Rotasi token hash-only: timpa hash lama dengan hash baru.
     * Plain baru dikembalikan sekali — caller wajib tampilkan via flash,
     * tidak disimpan di DB.
     */
    public function rotateToken(VotingEvent $event, VotingEventVoter $evv): string
    {
        $existingHashes = VotingEventVoter::where('voting_event_id', $event->id)
            ->whereNotNull('token_hash')
            ->pluck('token_hash')
            ->flip()
            ->toArray();

        unset($existingHashes[$evv->token_hash]);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            [$plain, $hash] = VotingToken::generateUnique($existingHashes);

            try {
                $evv->forceFill([
                    'token_hash' => $hash,
                    'expires_at' => $event->ends_at,
                ])->save();

                AuditLogger::log(
                    action: 'VOTING_EVENT_TOKEN_ROTATED',
                    resourceType: 'VotingEventVoter',
                    resourceId: $evv->id,
                    metadata: ['voting_event_id' => $event->id, 'voter_id' => $evv->voter_id]
                );

                return $plain;
            } catch (\Illuminate\Database\QueryException $e) {
                if (($e->errorInfo[0] ?? null) !== '23505') {
                    throw $e;
                }
                unset($existingHashes[$hash]);
            }
        }

        throw new \RuntimeException('Gagal merotasi token setelah 10 percobaan.');
    }

    /**
     * Generate token untuk voter spesifik (dipakai issue manual).
     */
    public function generateTokenForVoters(VotingEvent $event, array $voterIds): Collection
    {
        // Pastikan voter ter-assign dulu
        foreach ($voterIds as $voterId) {
            VotingEventVoter::firstOrCreate(
                ['voting_event_id' => $event->id, 'voter_id' => $voterId]
            );
        }

        // Pastikan eligibilities per election juga ada
        $event->loadMissing('elections');
        foreach ($voterIds as $voterId) {
            foreach ($event->elections as $election) {
                $elig = VoterEligibility::firstOrNew(
                    ['election_id' => $election->id, 'voter_id' => $voterId]
                );
                if (! $elig->exists) {
                    $elig->forceFill(['status' => 'ELIGIBLE'])->save();
                }
            }
        }

        $pending = VotingEventVoter::where('voting_event_id', $event->id)
            ->whereIn('voter_id', $voterIds)
            ->whereNull('token_hash')
            ->with('voter')
            ->get();

        $existingHashes = VotingEventVoter::where('voting_event_id', $event->id)
            ->whereNotNull('token_hash')
            ->pluck('token_hash')
            ->flip()
            ->toArray();

        $issued = collect();
        foreach ($pending as $evv) {
            $stored = null;
            for ($attempt = 0; $attempt < 5; $attempt++) {
                [$plain, $hash] = VotingToken::generateUnique($existingHashes);

                try {
                    $evv->forceFill([
                        'token_hash' => $hash,
                        'expires_at' => $event->ends_at,
                    ])->save();
                    $stored = $plain;
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    if (($e->errorInfo[0] ?? null) !== '23505') {
                        throw $e;
                    }
                    unset($existingHashes[$hash]);
                }
            }

            if ($stored === null) {
                continue;
            }

            $issued->push([
                'student_id' => $evv->voter->student_id,
                'name' => $evv->voter->name,
                'class_name' => $evv->voter->class_name,
                'token' => $stored,
            ]);

            AuditLogger::log(
                action: 'VOTING_EVENT_TOKEN_ISSUED',
                resourceType: 'VotingEventVoter',
                resourceId: $evv->id,
                metadata: ['voting_event_id' => $event->id, 'voter_id' => $evv->voter_id]
            );
        }

        return $issued;
    }

    /**
     * Resolve VotingEventVoter dari token plain via hash HMAC.
     * P0: hash-only — tidak ada lagi fallback plaintext.
     */
    public static function resolveByToken(int $votingEventId, int $voterId, string $plainToken): ?VotingEventVoter
    {
        $query = fn (string $hash) => VotingEventVoter::where('voting_event_id', $votingEventId)
            ->where('voter_id', $voterId)
            ->where('token_hash', $hash)
            ->first();

        // Utama: token ternormalisasi (trim + uppercase).
        $eventVoter = $query(VotingToken::hash($plainToken));

        // Fallback legacy: hash mentah (token lama bisa tersimpan tanpa normalisasi).
        if (! $eventVoter && $plainToken !== VotingToken::normalize($plainToken)) {
            $eventVoter = $query(VotingToken::hashRaw($plainToken));
        }

        return $eventVoter;
    }
}
