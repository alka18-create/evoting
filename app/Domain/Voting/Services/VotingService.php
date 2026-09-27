<?php

namespace App\Domain\Voting\Services;

use App\Domain\Auditing\Services\AuditLogger;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use Illuminate\Support\Facades\DB;

class VotingService
{
    /**
     * Jalur single-election (legacy).
     *
     * Atomic transaction: auth → resolve eligibility (milik voter ini) →
     * verify → BEGIN → lock row → re-check eligible → create ballot
     * (tanpa voter_id) → mark voted → COMMIT
     *
     * @throws \Exception
     */
    public function castVote(
        int $electionId,
        int $candidateId,
        int $eligibilityId,
        int $voterId,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): Ballot {
        return DB::transaction(function () use ($electionId, $candidateId, $eligibilityId, $voterId, $ipAddress, $userAgent) {

            // 1. Resolve eligibility dengan lock (FOR UPDATE).
            // P0-C1: wajib cocok voter_id agar voter A tidak bisa memakai
            // eligibility milik voter B.
            $eligibility = VoterEligibility::lockForUpdate()
                ->where('id', $eligibilityId)
                ->where('election_id', $electionId)
                ->where('voter_id', $voterId)
                ->firstOrFail();

            // 2. Re-check: eligibility harus ELIGIBLE (bukan VOTED atau lainnya)
            if ($eligibility->status !== 'ELIGIBLE') {
                throw new \Exception('Anda sudah memberikan suara untuk pemilihan ini.');
            }

            // 3. Verify election status + window via gerbang terpusat (P0).
            // Kill-switch voter nonaktif: tolak meski eligibility masih ELIGIBLE.
            $election = Election::with('votingEvent')->findOrFail($electionId);
            $voterActive = \App\Models\Voter::whereKey($voterId)->value('is_active');
            if (! $voterActive) {
                throw new \Exception('Akun pemilih dinonaktifkan. Hubungi panitia.');
            }
            VotingGate::assertElectionVotable($election);

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
            $eligibility->forceFill([
                'status' => 'VOTED',
                'voted_at' => now(),
            ])->save();

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

            // P0-C2: tolak voter yang dinonaktifkan di tengah sesi.
            $voterActive = \App\Models\Voter::whereKey($voterId)->value('is_active');
            if (! $voterActive) {
                throw new \Exception('Akun pemilih dinonaktifkan. Hubungi panitia.');
            }

            // P0: validasi event (status + window) terpusat via VotingGate.
            VotingGate::assertEventOpen($votingEvent);

            $ballots = [];

            // Lock & validasi semua elections dulu sebelum create ballot manapun
            $lockedEligibilities = [];

            foreach ($selections as $electionId => $candidateId) {
                $election = Election::with('votingEvent')->findOrFail($electionId);

                if ((int) $election->voting_event_id !== (int) $votingEventId) {
                    throw new \Exception("Pemilihan {$election->name} tidak termasuk dalam event ini.");
                }

                // P0: status + effective window via gerbang terpusat.
                VotingGate::assertElectionVotable($election);

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

                $lockedEligibilities[$electionId]->forceFill([
                    'status' => 'VOTED',
                    'voted_at' => now(),
                ])->save();

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