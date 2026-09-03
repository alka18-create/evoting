<?php

use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\User;
use App\Enums\UserRole;
use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use App\Support\VotingToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Token Management — Admin TokenController', function () {

    it('admin dapat mengakses halaman token management', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();

        $this->actingAs($admin);

        $response = $this->get(route('admin.elections.tokens.index', $election));
        $response->assertOk();
    });

    it('operator dapat mengakses halaman token management', function () {
        $operator = User::factory()->operator()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();

        $this->actingAs($operator);

        $response = $this->get(route('admin.elections.tokens.index', $election));
        $response->assertOk();
    });

    it('admin dapat menerbitkan token untuk voter', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();
        $voter = Voter::factory()->create();

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.tokens.issue', $election), [
            'voter_ids' => [$voter->id],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('voter_eligibilities', [
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
        ]);

        $eligibility = VoterEligibility::where('election_id', $election->id)
            ->where('voter_id', $voter->id)
            ->first();

        $this->assertTrue($eligibility->hasToken());
        $this->assertNotNull($eligibility->token_hash);
        // Plain tidak lagi disimpan di DB (P0-01)
        $this->assertNull($eligibility->token);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'TOKEN_ISSUED',
            'resource_type' => 'VoterEligibility',
            'resource_id' => $eligibility->id,
        ]);
    });

    it('admin dapat menerbitkan token untuk multiple voters', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();
        $voters = Voter::factory()->count(3)->create();

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.tokens.issue', $election), [
            'voter_ids' => $voters->pluck('id')->toArray(),
        ]);

        $response->assertRedirect();

        foreach ($voters as $voter) {
            $this->assertDatabaseHas('voter_eligibilities', [
                'election_id' => $election->id,
                'voter_id' => $voter->id,
                'status' => 'ELIGIBLE',
            ]);
        }
    });

    it('skip penerbitan token jika voter sudah punya token', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();
        $voter = Voter::factory()->create();

        $existing = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
            'token' => '999999',
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.tokens.issue', $election), [
            'voter_ids' => [$voter->id],
        ]);

        $existing->refresh();
        $this->assertEquals('999999', $existing->token);
    });

    it('admin dapat menghapus token (jika belum vote)', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();
        $voter = Voter::factory()->create();

        $eligibility = VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
            'token' => '123456',
        ]);

        $this->actingAs($admin);

        $response = $this->delete(route('admin.elections.tokens.destroy', [$election, $eligibility]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('voter_eligibilities', [
            'id' => $eligibility->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'TOKEN_REVOKED',
            'resource_type' => 'VoterEligibility',
        ]);
    });

    it('menolak hapus token jika voter sudah vote', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();
        $voter = Voter::factory()->create();

        $eligibility = VoterEligibility::factory()->voted()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'token' => '123456',
        ]);

        $this->actingAs($admin);

        $response = $this->delete(route('admin.elections.tokens.destroy', [$election, $eligibility]));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        $this->assertDatabaseHas('voter_eligibilities', [
            'id' => $eligibility->id,
        ]);
    });

    it('admin dapat melihat halaman print card', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();
        $voter = Voter::factory()->create();

        VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
            'status' => 'ELIGIBLE',
            'token' => '123456',
        ]);

        $eligibility = VoterEligibility::where('election_id', $election->id)
            ->where('voter_id', $voter->id)
            ->first();

        $this->actingAs($admin);

        $response = $this->get(route('admin.elections.tokens.print-card', [$election, $eligibility]));
        $response->assertOk();
    });

    it('admin dapat melihat halaman print cards bulk', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();

        $this->actingAs($admin);

        $response = $this->get(route('admin.elections.tokens.print-bulk', $election));
        $response->assertOk();
    });
});

describe('Voter Login — Token Authentication (wizard event)', function () {

    // Helper lokal: event OPEN + voter aktif + token hash yang konsisten.
    beforeEach(function () {
        $this->admin = User::factory()->admin()->create(['is_active' => true]);
        $this->event = VotingEvent::factory()->create([
            'status' => VotingEventStatus::Open,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
            'created_by' => $this->admin->id,
        ]);
    });

    // Helper file-level (closure describe tidak ter-bind $this di Pest).
    if (! function_exists('issueEventTokenForTest')) {
        function issueEventTokenForTest(VotingEvent $event, Voter $voter, string $plain, ?string $expiresAt = null): VotingEventVoter
        {
            return VotingEventVoter::create([
                'voting_event_id' => $event->id,
                'voter_id' => $voter->id,
                'token' => null,
                'token_hash' => VotingToken::hash($plain),
                'token_enc' => encrypt($plain),
                'expires_at' => $expiresAt ?? now()->addDay(),
            ]);
        }
    }

    it('voter dapat login dengan token yang valid', function () {
        $voter = Voter::factory()->create(['student_id' => '2024001', 'is_active' => true]);
        issueEventTokenForTest($this->event, $voter, 'ABCDEFGH');

        $response = $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => 'ABCDEFGH',
            'voting_event_id' => $this->event->id,
        ]);

        $response->assertRedirect(route('vote.wizard.step', ['step' => 1]));
        $this->assertAuthenticatedAs($voter, 'voter');
    });

    it('menolak login dengan token yang salah (pesan generik)', function () {
        $voter = Voter::factory()->create(['student_id' => '2024001', 'is_active' => true]);
        issueEventTokenForTest($this->event, $voter, 'ABCDEFGH');

        $response = $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => 'ZZZZZZZZ',
            'voting_event_id' => $this->event->id,
        ]);

        // P0-04: NIS salah vs token salah tidak bisa dibedakan.
        $response->assertSessionHasErrors('student_id');
        $this->assertGuest('voter');
    });

    it('menolak login dengan NIS yang tidak ada (pesan generik sama)', function () {
        $response = $this->post(route('vote.login.submit'), [
            'student_id' => '0000000',
            'token' => 'ABCDEFGH',
            'voting_event_id' => $this->event->id,
        ]);

        $response->assertSessionHasErrors('student_id');
        $this->assertGuest('voter');
    });

    it('menolak login dengan NIS tidak aktif', function () {
        $voter = Voter::factory()->inactive()->create(['student_id' => '2024001']);
        issueEventTokenForTest($this->event, $voter, 'ABCDEFGH');

        $response = $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => 'ABCDEFGH',
            'voting_event_id' => $this->event->id,
        ]);

        $response->assertSessionHasErrors('student_id');
        $this->assertGuest('voter');
    });

    it('menolak login jika sudah vote semua election dalam event', function () {
        $voter = Voter::factory()->create(['student_id' => '2024001', 'is_active' => true]);
        issueEventTokenForTest($this->event, $voter, 'ABCDEFGH');
        $election = Election::factory()->create([
            'voting_event_id' => $this->event->id,
            'status' => ElectionStatus::Open,
            'created_by' => $this->admin->id,
        ]);
        VoterEligibility::factory()->voted()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
        ]);

        $response = $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => 'ABCDEFGH',
            'voting_event_id' => $this->event->id,
        ]);

        $response->assertSessionHasErrors('token');
        $this->assertGuest('voter');
    });

    it('menolak login jika token belum diterbitkan', function () {
        $voter = Voter::factory()->create(['student_id' => '2024001', 'is_active' => true]);
        VotingEventVoter::create([
            'voting_event_id' => $this->event->id,
            'voter_id' => $voter->id,
            'token' => null,
            'token_hash' => null,
        ]);

        $response = $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => 'ABCDEFGH',
            'voting_event_id' => $this->event->id,
        ]);

        $response->assertSessionHasErrors('student_id');
        $this->assertGuest('voter');
    });

    it('menolak login jika token kedaluwarsa', function () {
        $voter = Voter::factory()->create(['student_id' => '2024001', 'is_active' => true]);
        // Tanggal fix jauh di masa lalu (kebal selisih timezone/presisi jam).
        $eventVoter = issueEventTokenForTest($this->event, $voter, 'ABCDEFGH', '2000-01-01 00:00:00');

        // Diagnostik lapis data: model harus menganggap token expired.
        expect($eventVoter->fresh()->isExpired())->toBeTrue();

        $response = $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => 'ABCDEFGH',
            'voting_event_id' => $this->event->id,
        ]);

        $response->assertSessionHasErrors('student_id');
        $this->assertGuest('voter');
    });

    it('menolak login jika token terlalu pendek', function () {
        $response = $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => '123',
            'voting_event_id' => $this->event->id,
        ]);

        $response->assertSessionHasErrors('token');
    });

    it('mencatat audit log saat voter login', function () {
        $voter = Voter::factory()->create(['student_id' => '2024001', 'is_active' => true]);
        $eventVoter = issueEventTokenForTest($this->event, $voter, 'ABCDEFGH');

        $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => 'ABCDEFGH',
            'voting_event_id' => $this->event->id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'VOTER_LOGIN',
            'resource_type' => 'VotingEventVoter',
            'resource_id' => $eventVoter->id,
        ]);
    });

    it('voter dapat logout', function () {
        $voter = Voter::factory()->create(['student_id' => '2024001', 'is_active' => true]);
        issueEventTokenForTest($this->event, $voter, 'ABCDEFGH');

        // Login first
        $this->post(route('vote.login.submit'), [
            'student_id' => '2024001',
            'token' => 'ABCDEFGH',
            'voting_event_id' => $this->event->id,
        ]);

        $this->assertAuthenticatedAs($voter, 'voter');

        // Logout
        $response = $this->post(route('vote.logout'));
        $response->assertRedirect('/vote/login');
        $this->assertGuest('voter');
    });
});
