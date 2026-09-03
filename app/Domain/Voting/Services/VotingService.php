<?php

namespace App\Domain\Voting\Services;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use Illuminate\Support\Facades\DB;

class VotingService
{
    /**
     * Single entry point untuk voting.
     *
     * Atomic transaction: auth → resolve eligibility → verify → BEGIN → lock row →
     * re-check eligible → create ballot (tanpa voter_id) → mark voted → COMMIT
     *
     * @throws \Exception
     */
    public function castVote(
        int $electionId,
        int $candidateId,
        int $eligibilityId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Ballot {
        return DB::transaction(function () use ($electionId, $candidateId, $eligibilityId, $ipAddress, $userAgent) {

            // 1. Resolve eligibility dengan lock (FOR UPDATE)
            $eligibility = VoterEligibility::lockForUpdate()
                ->where('id', $eligibilityId)
                ->where('election_id', $electionId)
                ->firstOrFail();

            // 2. Re-check: eligibility harus ELIGIBLE (bukan VOTED atau lainnya)
            if ($eligibility->status !== 'ELIGIBLE') {
                throw new \Exception('Anda sudah memberikan suara untuk pemilihan ini.');
            }

            // 3. Verify election status
            $election = Election::findOrFail($electionId);
            if ($election->status !== ElectionStatus::Open) {
                throw new \Exception('Pemilihan belum dibuka atau sudah ditutup.');
            }

            // 4. Verify candidate belongs to election
            $candidate = Candidate::where('id', $candidateId)
                ->where('election_id', $electionId)
                ->first();

            if (! $candidate) {
                throw new \Exception('Kandidat tidak valid untuk pemilihan ini.');
            }

            // 5. Create ballot TANPA voter_id (anonim)
            $ballot = Ballot::create([
                'election_id' => $electionId,
                'candidate_id' => $candidateId,
                'ballot_hash' => bin2hex(random_bytes(64)),
                'created_at' => now(),
            ]);

            // 6. Mark eligibility = VOTED
            $eligibility->update([
                'status' => 'VOTED',
                'voted_at' => now(),
            ]);

            // 7. Audit log (tanpa menyimpan voter→candidate relationship)
            AuditLogger::log(
                action: 'VOTE_CAST',
                resourceType: 'Ballot',
                resourceId: $ballot->id,
                metadata: [
                    'election_id' => $electionId,
                    // Jangan simpan candidate_id di audit log untuk privacy
                ]
            );

            return $ballot;
        });
    }

    /**
     * Batch wizard: 1 token untuk semua organisasi, 1x submit atomik.
     * All-or-nothing: lock semua eligibilities, baru create N ballots.
     *
     * @param  array<int,int>  $selections  [electionId => candidateId]
     * @return array<int, Ballot>
     *
     * @throws \Exception
     */
    public function castVotesBatch(
        int $votingEventId,
        int $voterId,
        array $selections,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): array {
        if (empty($selections)) {
            throw new \Exception('Tidak ada pilihan untuk disimpan.');
        }

        return DB::transaction(function () use ($votingEventId, $voterId, $selections, $ipAddress, $userAgent) {
            $votingEvent = VotingEvent::findOrFail($votingEventId);

            if ($votingEvent->status !== VotingEventStatus::Open) {
                throw new \Exception('Event pemilihan belum dibuka atau sudah ditutup.');
            }

            $ballots = [];

            // Lock & validasi semua elections dulu sebelum create ballot manapun
            $lockedEligibilities = [];

            foreach ($selections as $electionId => $candidateId) {
                $election = Election::findOrFail($electionId);

                if ((int) $election->voting_event_id !== (int) $votingEventId) {
                    throw new \Exception("Pemilihan {$election->name} tidak termasuk dalam event ini.");
                }

                if ($election->status !== ElectionStatus::Open) {
                    throw new \Exception("Pemilihan {$election->name} belum dibuka atau sudah ditutup.");
                }

                // Optional: effective date check jika Election pakai inherit
                $startsAt = $election->effectiveStartsAt();
                $endsAt = $election->effectiveEndsAt();
                if ($startsAt && now()->lt($startsAt)) {
                    throw new \Exception("Pemilihan {$election->name} belum dimulai.");
                }
                if ($endsAt && now()->gt($endsAt)) {
                    throw new \Exception("Pemilihan {$election->name} sudah berakhir.");
                }

                $eligibility = VoterEligibility::lockForUpdate()
                    ->where('election_id', $electionId)
                    ->where('voter_id', $voterId)
                    ->first();

                if (! $eligibility) {
                    throw new \Exception("Anda tidak terdaftar untuk pemilihan {$election->name}.");
                }

                if ($eligibility->status !== 'ELIGIBLE') {
                    throw new \Exception("Anda sudah memberikan suara untuk pemilihan {$election->name}.");
                }

                $candidate = Candidate::where('id', $candidateId)
                    ->where('election_id', $electionId)
                    ->first();

                if (! $candidate) {
                    throw new \Exception("Kandidat tidak valid untuk pemilihan {$election->name}.");
                }

                $lockedEligibilities[$electionId] = $eligibility;
            }

            // Semua valid, baru create ballots
            foreach ($selections as $electionId => $candidateId) {
                $ballot = Ballot::create([
                    'election_id' => $electionId,
                    'candidate_id' => $candidateId,
                    'ballot_hash' => bin2hex(random_bytes(64)),
                    'created_at' => now(),
                ]);

                $lockedEligibilities[$electionId]->update([
                    'status' => 'VOTED',
                    'voted_at' => now(),
                ]);

                AuditLogger::log(
                    action: 'VOTE_CAST_BATCH',
                    resourceType: 'Ballot',
                    resourceId: $ballot->id,
                    metadata: ['voting_event_id' => $votingEventId, 'election_id' => $electionId]
                );

                $ballots[$electionId] = $ballot;
            }

            return $ballots;
        });
    }
}