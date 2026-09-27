<?php

use App\Domain\Results\Services\ResultService;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Domain\Elections\Enums\ElectionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->resultService = new ResultService();
});

describe('ResultService — tally()', function () {

    it('menolak jika pemilihan belum ditutup', function () {
        $election = Election::factory()->open()->create();

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hasil hanya dapat dilihat setelah pemilihan ditutup.');

        $this->resultService->tally($election);
    });

    it('menolak untuk election status DRAFT', function () {
        $election = Election::factory()->create(['status' => ElectionStatus::Draft]);

        $this->expectException(\Exception::class);
        $this->resultService->tally($election);
    });

    it('menolak untuk election status SCHEDULED', function () {
        $election = Election::factory()->scheduled()->create();

        $this->expectException(\Exception::class);
        $this->resultService->tally($election);
    });

    it('menghitung hasil dengan benar pada election CLOSED', function () {
        $election = Election::factory()->closed()->create();
        $c1 = Candidate::factory()->forElection($election, 1)->create(['name' => 'Kandidat A']);
        $c2 = Candidate::factory()->forElection($election, 2)->create(['name' => 'Kandidat B']);

        // 3 voters
        $v1 = Voter::factory()->create();
        $v2 = Voter::factory()->create();
        $v3 = Voter::factory()->create();

        VoterEligibility::factory()->voted()->create(['election_id' => $election->id, 'voter_id' => $v1->id]);
        VoterEligibility::factory()->voted()->create(['election_id' => $election->id, 'voter_id' => $v2->id]);
        VoterEligibility::factory()->voted()->create(['election_id' => $election->id, 'voter_id' => $v3->id]);

        // 2 votes for c1, 1 vote for c2
        Ballot::factory()->forElection($election, $c1)->create();
        Ballot::factory()->forElection($election, $c1)->create();
        Ballot::factory()->forElection($election, $c2)->create();

        $result = $this->resultService->tally($election);

        $this->assertEquals(3, $result['total_votes']);
        $this->assertEquals(3, $result['total_eligible']);
        $this->assertEquals(3, $result['total_voted']);
        $this->assertEquals(100.0, $result['turnout']);

        // Winner should be c1
        $this->assertNotNull($result['winner']);
        $this->assertEquals($c1->id, $result['winner']['candidate_id']);
        $this->assertEquals('Kandidat A', $result['winner']['candidate_name']);
        $this->assertEquals(2, $result['winner']['vote_count']);
        $this->assertEquals(66.67, $result['winner']['percentage']);
    });

    it('menghitung hasil dengan benar pada election ARCHIVED', function () {
        $election = Election::factory()->archived()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();
        $voter = Voter::factory()->create();

        VoterEligibility::factory()->voted()->create(['election_id' => $election->id, 'voter_id' => $voter->id]);
        Ballot::factory()->forElection($election, $candidate)->create();

        $result = $this->resultService->tally($election);

        $this->assertEquals(1, $result['total_votes']);
        $this->assertEquals(1, $result['total_eligible']);
        $this->assertEquals(1, $result['total_voted']);
        $this->assertEquals(100.0, $result['turnout']);
        $this->assertNotNull($result['winner']);
    });

    it('mengembalikan winner null jika tidak ada suara', function () {
        $election = Election::factory()->closed()->create();

        $result = $this->resultService->tally($election);

        $this->assertEquals(0, $result['total_votes']);
        $this->assertNull($result['winner']);
        $this->assertEmpty($result['results']);
    });

    it('menghitung turnout dengan benar', function () {
        $election = Election::factory()->closed()->create();
        $candidate = Candidate::factory()->forElection($election, 1)->create();

        // 10 eligible, 5 voted
        $voters = Voter::factory()->count(10)->create();
        foreach ($voters as $i => $voter) {
            $status = $i < 5 ? 'VOTED' : 'ELIGIBLE';
            VoterEligibility::factory()->create([
                'election_id' => $election->id,
                'voter_id' => $voter->id,
                'status' => $status,
            ]);
        }

        Ballot::factory()->forElection($election, $candidate)->count(5)->create();

        $result = $this->resultService->tally($election);

        $this->assertEquals(10, $result['total_eligible']);
        $this->assertEquals(5, $result['total_voted']);
        $this->assertEquals(50.0, $result['turnout']);
    });

    it('menghitung persentase dengan benar untuk banyak kandidat', function () {
        $election = Election::factory()->closed()->create();
        $c1 = Candidate::factory()->forElection($election, 1)->create();
        $c2 = Candidate::factory()->forElection($election, 2)->create();
        $c3 = Candidate::factory()->forElection($election, 3)->create();

        Ballot::factory()->forElection($election, $c1)->count(5)->create();
        Ballot::factory()->forElection($election, $c2)->count(3)->create();
        Ballot::factory()->forElection($election, $c3)->count(2)->create();

        $result = $this->resultService->tally($election);

        $this->assertEquals(10, $result['total_votes']);
        $this->assertEquals(50.0, $result['results'][0]['percentage']);
        $this->assertEquals(30.0, $result['results'][1]['percentage']);
        $this->assertEquals(20.0, $result['results'][2]['percentage']);
    });
});
