<?php

use App\Domain\Results\Services\ResultService;
use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Models\VoterEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->admin()->create(['is_active' => true]);
    $this->election = Election::factory()->create(); // DRAFT — kandidat bisa diedit
});

describe('Kandidat berpasangan', function () {

    it('admin dapat membuat kandidat berpasangan', function () {
        $this->actingAs($this->user);

        $this->post(route('admin.elections.candidates.store', $this->election), [
            'candidate_number' => 1,
            'name' => 'Budi Santoso',
            'running_mate_name' => 'Agus Salim',
            'vision' => 'Visi pasangan',
            'mission' => 'Misi pasangan',
        ])->assertRedirect();

        $candidate = Candidate::where('election_id', $this->election->id)->firstOrFail();
        $this->assertSame('Budi Santoso', $candidate->name);
        $this->assertSame('Agus Salim', $candidate->running_mate_name);
        $this->assertTrue($candidate->isPair());
        $this->assertSame('Budi Santoso - Agus Salim', $candidate->display_name());
    });

    it('kandidat tunggal tetap tanpa wakil', function () {
        $candidate = Candidate::factory()->forElection($this->election, 1)->create([
            'running_mate_name' => null,
        ]);

        $this->assertFalse($candidate->isPair());
        $this->assertSame($candidate->name, $candidate->display_name());
    });

    it('mengosongkan nama wakil mengembalikan kandidat tunggal', function () {
        $candidate = Candidate::factory()->forElection($this->election, 1)->create([
            'running_mate_name' => 'Agus Salim',
            'running_mate_photo_path' => 'candidates/mate.jpg',
        ]);

        $this->actingAs($this->user);

        $this->put(route('admin.elections.candidates.update', [$this->election, $candidate]), [
            'candidate_number' => 1,
            'name' => 'Budi Santoso',
        ])->assertRedirect();

        $candidate->refresh();
        $this->assertNull($candidate->running_mate_name);
        $this->assertNull($candidate->running_mate_photo_path);
        $this->assertFalse($candidate->isPair());
    });

    it('result tally menyertakan nama wakil', function () {
        $closed = Election::factory()->closed()->create();
        $pair = Candidate::factory()->forElection($closed, 1)->create([
            'name' => 'Budi Santoso',
            'running_mate_name' => 'Agus Salim',
        ]);
        $single = Candidate::factory()->forElection($closed, 2)->create(['name' => 'Citra Dewi']);

        Ballot::factory()->forElection($closed, $pair)->count(3)->create();
        Ballot::factory()->forElection($closed, $single)->count(1)->create();

        Voter::factory()->count(4)->create()->each(function ($v, $i) use ($closed) {
            VoterEligibility::factory()->voted()->create([
                'election_id' => $closed->id,
                'voter_id' => $v->id,
            ]);
        });

        $result = (new ResultService())->tally($closed);

        $this->assertSame('Agus Salim', $result['winner']['running_mate_name']);
        $this->assertSame('Budi Santoso', $result['winner']['candidate_name']);
        $this->assertNull($result['results'][1]['running_mate_name']);
    });

    it('kartu kandidat voter menampilkan pasangan', function () {
        $candidate = Candidate::factory()->forElection($this->election, 1)->create([
            'name' => 'Budi Santoso',
            'running_mate_name' => 'Agus Salim',
        ]);

        $html = view('voter.partials.candidate-card', [
            'candidate' => $candidate,
            'selectedCandidateId' => null,
        ])->render();

        $this->assertStringContainsString('Agus Salim', $html);
        $this->assertStringContainsString('Budi Santoso', $html);
    });
});
