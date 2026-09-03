<?php

use App\Models\User;
use App\Models\Election;
use App\Models\Voter;
use App\Models\Candidate;
use App\Models\AuditLog;
use App\Enums\UserRole;
use App\Domain\Elections\Enums\ElectionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('RBAC — Role-Based Access Control', function () {

    describe('Election Policy', function () {

        it('SUPER_ADMIN dapat mengakses semua fitur pemilihan', function () {
            $user = User::factory()->superAdmin()->create(['is_active' => true]);
            $this->actingAs($user);

            $this->get(route('admin.elections.index'))->assertOk();
            $this->get(route('admin.elections.create'))->assertOk();
        });

        it('ADMIN dapat melihat dan membuat pemilihan', function () {
            $user = User::factory()->admin()->create(['is_active' => true]);
            $this->actingAs($user);

            $this->get(route('admin.elections.index'))->assertOk();
            $this->get(route('admin.elections.create'))->assertOk();
        });

        it('OPERATOR dapat melihat pemilihan tapi tidak membuat', function () {
            $user = User::factory()->operator()->create(['is_active' => true]);
            $this->actingAs($user);

            $this->get(route('admin.elections.index'))->assertOk();
            $this->get(route('admin.elections.create'))->assertForbidden();
        });

        it('VOTER role tidak dapat mengakses admin area', function () {
            $user = User::factory()->create(['is_active' => true, 'role' => UserRole::Voter]);
            $this->actingAs($user);

            $this->get(route('admin.dashboard'))->assertForbidden();
        });

        it('akun tidak aktif tidak dapat login', function () {
            $user = User::factory()->admin()->create(['is_active' => false]);

            $this->post('/login', [
                'login' => $user->username,
                'password' => 'password',
            ]);

            $this->assertGuest();
        });
    });

    describe('Candidate Policy', function () {

        it('OPERATOR dapat membuat kandidat saat DRAFT', function () {
            $user = User::factory()->operator()->create(['is_active' => true]);
            $election = Election::factory()->create(); // DRAFT default — editable (P2-05)

            $this->actingAs($user);

            $this->get(route('admin.elections.candidates.create', $election))->assertOk();
        });

        it('menolak buat kandidat saat OPEN (INV-06)', function () {
            $user = User::factory()->operator()->create(['is_active' => true]);
            $election = Election::factory()->open()->create();

            $this->actingAs($user);

            $this->get(route('admin.elections.candidates.create', $election))->assertRedirect();
        });

        it('OPERATOR tidak dapat menghapus kandidat', function () {
            $user = User::factory()->operator()->create(['is_active' => true]);
            $election = Election::factory()->open()->create();
            $candidate = Candidate::factory()->forElection($election, 1)->create();

            $this->actingAs($user);

            $response = $this->delete(route('admin.elections.candidates.destroy', [$election, $candidate]));
            $response->assertForbidden();
        });
    });

    describe('Voter Policy', function () {

        it('SUPER_ADMIN dapat menghapus data voter', function () {
            $user = User::factory()->superAdmin()->create(['is_active' => true]);
            $voter = Voter::factory()->create();

            $this->actingAs($user);

            $response = $this->delete(route('admin.voters.destroy', $voter));
            $response->assertRedirect();
            $this->assertDatabaseMissing('voters', ['id' => $voter->id]);
        });

        it('admin biasa tidak dapat menghapus data voter', function () {
            $user = User::factory()->admin()->create(['is_active' => true]);
            $voter = Voter::factory()->create();

            $this->actingAs($user);

            $response = $this->delete(route('admin.voters.destroy', $voter));
            $response->assertForbidden();

            $this->assertDatabaseHas('voters', ['id' => $voter->id]);
        });
    });

    describe('Audit Log Policy', function () {

        it('SUPER_ADMIN dapat mengakses audit log', function () {
            $user = User::factory()->superAdmin()->create(['is_active' => true]);
            $this->actingAs($user);

            $this->get(route('admin.audit-logs.index'))->assertOk();
        });

        it('SUPER_ADMIN dapat export audit log', function () {
            $user = User::factory()->superAdmin()->create(['is_active' => true]);
            $this->actingAs($user);

            $this->get(route('admin.audit-logs.export'))->assertOk();
        });

        it('ADMIN dapat melihat audit log', function () {
            $user = User::factory()->admin()->create(['is_active' => true]);
            $this->actingAs($user);

            $this->get(route('admin.audit-logs.index'))->assertOk();
        });

        it('ADMIN tidak dapat export audit log', function () {
            $user = User::factory()->admin()->create(['is_active' => true]);
            $this->actingAs($user);

            $this->get(route('admin.audit-logs.export'))->assertForbidden();
        });

        it('OPERATOR tidak dapat mengakses audit log', function () {
            $user = User::factory()->operator()->create(['is_active' => true]);
            $this->actingAs($user);

            $this->get(route('admin.audit-logs.index'))->assertForbidden();
        });
    });
});