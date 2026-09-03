<?php

use App\Domain\Voting\Services\VotingService;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Domain\Elections\Enums\ElectionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->votingService = new VotingService();
});

describe('VotingService — castVote()', function () {

    it('berhasil mencetak suara dengan data valid', function () {
        $election = Election::factory()->open()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
            'token' => '123456',
        ]);

        $ballot = $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $candidate->id,
            eligibilityId: $eligibility->id,
            ipAddress: '127.0.0.1',
            userAgent: 'TestAgent',
        );

        $this->assertInstanceOf(Ballot::class, $ballot);
        $this->assertEquals($election->id, $ballot->election_id);
        $this->assertEquals($candidate->id, $ballot->candidate_id);
        $this->assertNotNull($ballot->ballot_hash);

        $eligibility->refresh();
        $this->assertEquals('VOTED', $eligibility->status);
        $this->assertNotNull($eligibility->voted_at);

        $this->assertDatabaseHas('ballots', [
            'election_id' => $election->id,
            'candidate_id' => $candidate->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'VOTE_CAST',
            'resource_type' => 'Ballot',
            'resource_id' => $ballot->id,
        ]);
    });

    it('menolak jika voter sudah memberikan suara', function () {
        $election = Election::factory()->open()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->voted()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Anda sudah memberikan suara');

        $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $candidate->id,
            eligibilityId: $eligibility->id,
        );
    });

    it('menolak jika status eligibility bukan ELIGIBLE', function () {
        $election = Election::factory()->open()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'REVOKED',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Anda sudah memberikan suara');

        $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $candidate->id,
            eligibilityId: $eligibility->id,
        );
    });

    it('menolak jika pemilihan belum dibuka', function () {
        $election = Election::factory()->scheduled()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pemilihan belum dibuka atau sudah ditutup');

        $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $candidate->id,
            eligibilityId: $eligibility->id,
        );
    });

    it('menolak jika pemilihan sudah ditutup', function () {
        $election = Election::factory()->closed()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pemilihan belum dibuka atau sudah ditutup');

        $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $candidate->id,
            eligibilityId: $eligibility->id,
        );
    });

    it('menolak jika kandidat tidak valid untuk pemilihan', function () {
        $election = Election::factory()->open()->create();
        $otherElection = Election::factory()->open()->create();
        $wrongCandidate = Candidate::factory()->forElection($otherElection, 1)->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Kandidat tidak valid untuk pemilihan ini');

        $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $wrongCandidate->id,
            eligibilityId: $eligibility->id,
        );
    });

    it('menolak jika eligibility tidak ada di election tersebut', function () {
        $election = Election::factory()->open()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $otherElection = Election::factory()->open()->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $otherElection->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $candidate->id,
            eligibilityId: $eligibility->id,
        );
    });

    it('menggunakan ballot_hash unik', function () {
        $election = Election::factory()->open()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
        ]);

        $ballot = $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $candidate->id,
            eligibilityId: $eligibility->id,
        );

        $this->assertNotEmpty($ballot->ballot_hash);
        $this->assertNotEquals('', $ballot->ballot_hash);
    });

    it('tidak menyimpan voter_id di ballot (anonim)', function () {
        $election = Election::factory()->open()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $voter = Voter::factory()->create();
        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
        ]);

        $ballot = $this->votingService->castVote(
            electionId: $election->id,
            candidateId: $candidate->id,
            eligibilityId: $eligibility->id,
        );

        $this->assertDatabaseMissing('ballots', [
            'id' => $ballot->id,
            'election_id' => $voter->id,
        ]);
    });
});
