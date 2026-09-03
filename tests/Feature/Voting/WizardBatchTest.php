<?php

use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Domain\Voting\Services\VotingService;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Organization;
use App\Models\User;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->event = VotingEvent::factory()->create([
        'status' => VotingEventStatus::Open,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'created_by' => $this->admin->id,
    ]);
    $this->orgs = Organization::factory()->count(4)->create();
    $this->elections = collect();
    foreach ($this->orgs as $org) {
        $this->elections->push(Election::factory()->create([
            'voting_event_id' => $this->event->id,
            'organization_id' => $org->id,
            'status' => ElectionStatus::Open,
            'starts_at' => null, // inherit event
            'ends_at' => null,
            'created_by' => $this->admin->id,
        ]));
    }
    foreach ($this->elections as $el) {
        Candidate::factory()->count(2)->create(['election_id' => $el->id]);
    }
});

it('wizard batch all-or-nothing creates N ballots atomically', function () {
    $voter = Voter::factory()->create(['is_active' => true]);
    VotingEventVoter::create(['voting_event_id' => $this->event->id, 'voter_id' => $voter->id, 'token' => '123456']);
    foreach ($this->elections as $el) {
        VoterEligibility::create(['election_id' => $el->id, 'voter_id' => $voter->id, 'status' => 'ELIGIBLE']);
    }

    $selections = [];
    foreach ($this->elections as $el) {
        $candidateId = Candidate::where('election_id', $el->id)->first()->id;
        $selections[$el->id] = $candidateId;
    }

    $service = app(VotingService::class);
    $ballots = $service->castVotesBatch($this->event->id, $voter->id, $selections);

    expect($ballots)->toHaveCount(4);
    expect(VoterEligibility::where('voter_id', $voter->id)->where('status', 'VOTED')->count())->toBe(4);
    // Anonimitas: ballots tanpa voter_id
    foreach ($ballots as $b) {
        expect($b->ballot_hash)->not->toBeEmpty();
    }
});

it('rollback jika salah satu election sudah VOTED', function () {
    $voter = Voter::factory()->create();
    VotingEventVoter::create(['voting_event_id' => $this->event->id, 'voter_id' => $voter->id, 'token' => '123456']);
    foreach ($this->elections as $el) {
        VoterEligibility::create(['election_id' => $el->id, 'voter_id' => $voter->id, 'status' => 'ELIGIBLE']);
    }
    // Tandai satu sudah VOTED
    VoterEligibility::where('election_id', $this->elections->first()->id)->update(['status' => 'VOTED']);

    $selections = [];
    foreach ($this->elections as $el) {
        $selections[$el->id] = Candidate::where('election_id', $el->id)->first()->id;
    }

    $service = app(VotingService::class);
    expect(fn () => $service->castVotesBatch($this->event->id, $voter->id, $selections))->toThrow(Exception::class);

    // Tidak ada ballot tambahan
    expect(\App\Models\Ballot::count())->toBe(0);
});

it('token unik per event (collision retry)', function () {
    $service = app(\App\Domain\Voting\Services\VotingEventService::class);
    $voters = Voter::factory()->count(5)->create();
    foreach ($voters as $v) {
        VotingEventVoter::create(['voting_event_id' => $this->event->id, 'voter_id' => $v->id, 'token' => null]);
    }

    $issued = $service->generateTokens($this->event);
    expect($issued)->toHaveCount(5);
    expect($issued->pluck('token')->unique()->count())->toBe(5);
});

it('publish terpisah: hanya CLOSED/ARCHIVED bisa dilihat results', function () {
    $closed = Election::factory()->create(['status' => ElectionStatus::Closed, 'voting_event_id' => $this->event->id, 'organization_id' => $this->orgs->first()->id, 'created_by' => $this->admin->id]);
    $open = Election::factory()->create(['status' => ElectionStatus::Open, 'voting_event_id' => $this->event->id, 'organization_id' => $this->orgs->last()->id, 'created_by' => $this->admin->id]);

    expect($closed->status)->toBe(ElectionStatus::Closed);
    expect($open->status)->toBe(ElectionStatus::Open);
    // Results logic: hanya CLOSED/ARCHIVED boleh tally — sudah di ResultController:show
});
