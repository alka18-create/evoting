<?php

namespace App\Domain\Voting\Services;

use App\Domain\Auditing\Services\AuditLogger;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use App\Support\VotingToken;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Crypt;
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
                    ['voting_event_id' => $event->id, 'voter_id' => $voter->id],
                    ['token' => null]
                );
                if ($evv->wasRecentlyCreated) {
                    $eventVotersCreated++;
                }

                foreach ($electionIds as $electionId) {
                    $elig = VoterEligibility::firstOrCreate(
                        ['election_id' => $electionId, 'voter_id' => $voter->id],
                        ['status' => 'ELIGIBLE']
                    );
                    if ($elig->wasRecentlyCreated) {
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
     * Disimpan sebagai hash (token_hash) + ciphertext (token_enc) + expiry.
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
                    $evv->update([
                        'token' => null, // jangan simpan plaintext (P0-01)
                        'token_hash' => $hash,
                        'token_enc' => Crypt::encryptString($plain),
                        'expires_at' => $event->ends_at,
                    ]);
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
     * Generate token untuk voter spesifik (dipakai issue manual).
     */
    public function generateTokenForVoters(VotingEvent $event, array $voterIds): Collection
    {
        // Pastikan voter ter-assign dulu
        foreach ($voterIds as $voterId) {
            VotingEventVoter::firstOrCreate(
                ['voting_event_id' => $event->id, 'voter_id' => $voterId],
                ['token' => null, 'token_hash' => null]
            );
        }

        // Pastikan eligibilities per election juga ada
        $event->loadMissing('elections');
        foreach ($voterIds as $voterId) {
            foreach ($event->elections as $election) {
                VoterEligibility::firstOrCreate(
                    ['election_id' => $election->id, 'voter_id' => $voterId],
                    ['status' => 'ELIGIBLE']
                );
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
                    $evv->update([
                        'token' => null,
                        'token_hash' => $hash,
                        'token_enc' => Crypt::encryptString($plain),
                        'expires_at' => $event->ends_at,
                    ]);
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
     * Resolve VotingEventVoter dari token plain (dual-read transisi).
     * Prioritas token_hash, fallback token plain lama + auto-upgrade ke hash.
     */
    public static function resolveByToken(int $votingEventId, int $voterId, string $plainToken): ?VotingEventVoter
    {
        $hash = VotingToken::hash($plainToken);

        $evv = VotingEventVoter::where('voting_event_id', $votingEventId)
            ->where('voter_id', $voterId)
            ->where('token_hash', $hash)
            ->first();

        if ($evv) {
            return $evv;
        }

        // Fallback transisi: cocokkan plain lama, lalu upgrade ke hash+enc.
        // TODO: hapus fallback setelah kolom `token` di-drop.
        $legacy = VotingEventVoter::where('voting_event_id', $votingEventId)
            ->where('voter_id', $voterId)
            ->where('token', $plainToken)
            ->first();

        if ($legacy && $legacy->token_hash === null) {
            $legacy->update([
                'token_hash' => $hash,
                'token_enc' => Crypt::encryptString($plainToken),
                'token' => null,
            ]);

            return $legacy->refresh();
        }

        return $legacy;
    }
}
