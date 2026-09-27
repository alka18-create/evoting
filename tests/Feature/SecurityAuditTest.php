<?php

use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use App\Support\VotingToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/*
 * P0 audit follow-up: IDOR, throttle vote-submit, hash-only,
 * konfirmasi one-time, mass-assignment.
 */

it('IDOR candidate cross-election ditolak 404', function () {
    $admin = User::factory()->admin()->create(['is_active' => true]);
    $electionA = Election::factory()->create(['status' => ElectionStatus::Draft, 'created_by' => $admin->id]);
    $electionB = Election::factory()->create(['status' => ElectionStatus::Draft, 'created_by' => $admin->id]);
    $candidate = Candidate::factory()->create(['election_id' => $electionA->id]);

    $this->actingAs($admin);

    $this->get(route('admin.elections.candidates.show', [$electionB, $candidate]))->assertNotFound();
    $this->get(route('admin.elections.candidates.edit', [$electionB, $candidate]))->assertNotFound();
    $this->put(route('admin.elections.candidates.update', [$electionB, $candidate]), [
        'name' => 'X', 'candidate_number' => 99,
    ])->assertNotFound();
    $this->delete(route('admin.elections.candidates.destroy', [$electionB, $candidate]))->assertNotFound();
});

it('hash-only: tanpa kolom token_enc, plainToken null', function () {
    expect(Schema::hasColumn('voting_event_voters', 'token_enc'))->toBeFalse();
    expect(Schema::hasColumn('voter_eligibilities', 'token_enc'))->toBeFalse();

    $elig = VoterEligibility::factory()->create();
    expect($elig->plainToken())->toBeNull();
    expect(array_key_exists('token_enc', $elig->getAttributes()))->toBeFalse();
});

it('mass-assignment: role/status/token tak bisa via create()', function () {
    // User::create dengan role harus ditolak (fillable minimal).
    try {
        $u = User::create([
            'name' => 'Evil', 'username' => 'evil', 'email' => 'evil@x.local',
            'password' => 'password123', 'role' => 'SUPER_ADMIN', 'is_active' => true,
        ]);
        // Jika tidak throw, role tidak boleh ter-set.
        expect($u->role?->value ?? $u->getAttribute('role'))->not->toBe('SUPER_ADMIN');
    } catch (\Illuminate\Database\Eloquent\MassAssignmentException) {
        expect(true)->toBeTrue();
    }

    $admin = User::factory()->admin()->create(['is_active' => true]);
    $election = Election::factory()->create(['status' => ElectionStatus::Draft, 'created_by' => $admin->id]);
    $voter = Voter::factory()->create();

    try {
        $elig = VoterEligibility::create([
            'election_id' => $election->id, 'voter_id' => $voter->id,
            'status' => 'VOTED', 'token_hash' => 'evil',
        ]);
        expect($elig->status)->not->toBe('VOTED');
    } catch (\Illuminate\Database\Eloquent\MassAssignmentException) {
        expect(true)->toBeTrue();
    }
});

it('konfirmasi one-time: receipt sekali pakai, hash mentah ditolak', function () {
    $admin = User::factory()->admin()->create(['is_active' => true]);
    $event = VotingEvent::factory()->create([
        'status' => VotingEventStatus::Open,
        'starts_at' => now()->subDay(), 'ends_at' => now()->addDay(),
        'created_by' => $admin->id,
    ]);
    $voter = Voter::factory()->create(['student_id' => '2024001', 'is_active' => true]);
    $evv = new VotingEventVoter(['voting_event_id' => $event->id, 'voter_id' => $voter->id]);
    $evv->forceFill([
        'token_hash' => VotingToken::hash('ABCDEFGH'),
        'expires_at' => now()->addDay(),
    ])->save();

    $this->post(route('vote.login.submit'), [
        'student_id' => '2024001', 'token' => 'ABCDEFGH', 'voting_event_id' => $event->id,
    ])->assertRedirect();

    // Tanpa election: wizard kosong — login sesi tetap valid, submit ditolak sesi.
    // Langsung uji confirmation: hash mentah tidak dirender.
    $res = $this->get(route('vote.confirmation', ['hash' => 'abc123']));
    $res->assertOk();
    expect($res->getContent())->not->toContain('abc123');

    // Receipt valid sekali pakai.
    \Illuminate\Support\Facades\Cache::put('vote-receipt:r-test-123', ['h1', 'h2'], now()->addMinutes(5));
    $first = $this->get(route('vote.confirmation', ['receipt' => 'r-test-123']));
    $first->assertOk();
    expect($first->getContent())->toContain('h1');

    $second = $this->get(route('vote.confirmation', ['receipt' => 'r-test-123']));
    $second->assertOk();
    expect($second->getContent())->not->toContain('h1');
});

it('vote-submit dibatasi throttle', function () {
    // Pastikan limiter terdaftar.
    expect(\Illuminate\Support\Facades\RateLimiter::limiter('vote-submit'))->not->toBeNull();
});
