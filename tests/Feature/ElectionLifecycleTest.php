<?php

use App\Models\Election;
use App\Models\User;
use App\Models\Candidate;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Domain\Elections\Enums\ElectionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

describe('Election Lifecycle — Status Transitions', function () {

    it('SUPER_ADMIN dapat membuat pemilihan', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.store'), [
            'name' => 'Pemilihan OSIS 2026',
            'description' => 'Pemilihan ketua OSIS',
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addDays(2),
        ]);

        $response->assertRedirect(route('admin.elections.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('elections', [
            'name' => 'Pemilihan OSIS 2026',
            'status' => 'DRAFT',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ELECTION_CREATED',
            'resource_type' => 'Election',
        ]);
    });

    it('SUPER_ADMIN dapat menjadwalkan pemilihan dari DRAFT', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->create(['status' => ElectionStatus::Draft]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.schedule', $election));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $election->refresh();
        $this->assertEquals(ElectionStatus::Scheduled, $election->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ELECTION_SCHEDULED',
            'resource_type' => 'Election',
            'resource_id' => $election->id,
        ]);
    });

    it('menolak menjadwalkan pemilihan yang bukan DRAFT', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.schedule', $election));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        $election->refresh();
        $this->assertEquals(ElectionStatus::Open, $election->status);
    });

    it('SUPER_ADMIN dapat membuka pemilihan dari SCHEDULED', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        // Jadwal efektif sudah dimulai — guard open() menolak yang belum mulai.
        $election = Election::factory()->scheduled()->create([
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addDay(),
        ]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.open', $election));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $election->refresh();
        $this->assertEquals(ElectionStatus::Open, $election->status);
        $this->assertNotNull($election->opened_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ELECTION_OPENED',
            'resource_type' => 'Election',
            'resource_id' => $election->id,
        ]);
    });

    it('menolak membuka pemilihan yang jadwalnya belum tiba', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->scheduled()->create([
            'starts_at' => now()->addWeek(),
            'ends_at' => now()->addWeek()->addDays(2),
        ]);

        $this->actingAs($admin);

        $this->post(route('admin.elections.open', $election))
            ->assertSessionHasErrors('error');

        $election->refresh();
        $this->assertEquals(ElectionStatus::Scheduled, $election->status);
    });

    it('menolak membuka pemilihan yang jadwalnya sudah lewat', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->scheduled()->create([
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->subDay(),
        ]);

        $this->actingAs($admin);

        $this->post(route('admin.elections.open', $election))
            ->assertSessionHasErrors('error');

        $election->refresh();
        $this->assertEquals(ElectionStatus::Scheduled, $election->status);
    });

    it('menolak membuka pemilihan yang bukan SCHEDULED', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->create(['status' => ElectionStatus::Draft]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.open', $election));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    });

    it('SUPER_ADMIN dapat menutup pemilihan dari OPEN', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.close', $election));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $election->refresh();
        $this->assertEquals(ElectionStatus::Closed, $election->status);
        $this->assertNotNull($election->closed_at);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ELECTION_CLOSED',
            'resource_type' => 'Election',
            'resource_id' => $election->id,
        ]);
    });

    it('menolak menutup pemilihan yang bukan OPEN', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->scheduled()->create();

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.close', $election));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    });

    it('SUPER_ADMIN dapat mengarsipkan pemilihan dari CLOSED', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->closed()->create();

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.archive', $election));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $election->refresh();
        $this->assertEquals(ElectionStatus::Archived, $election->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ELECTION_ARCHIVED',
            'resource_type' => 'Election',
            'resource_id' => $election->id,
        ]);
    });

    it('menolak mengarsipkan pemilihan yang bukan CLOSED', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.archive', $election));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    });

    it('admin biasa dapat menjadwalkan pemilihan', function () {
        $admin = User::factory()->admin()->create(['is_active' => true]);
        $election = Election::factory()->create(['status' => ElectionStatus::Draft]);

        $this->actingAs($admin);

        $response = $this->post(route('admin.elections.schedule', $election));
        $response->assertRedirect();
        $response->assertSessionHas('success');
    });

    it('SUPER_ADMIN dapat menghapus pemilihan DRAFT', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->create(['status' => ElectionStatus::Draft]);

        $this->actingAs($admin);

        $response = $this->delete(route('admin.elections.destroy', $election));

        $response->assertRedirect('/admin/elections');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('elections', ['id' => $election->id]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'ELECTION_DELETED',
            'resource_type' => 'Election',
        ]);
    });

    it('menolak menghapus pemilihan yang bukan DRAFT', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->scheduled()->create();

        $this->actingAs($admin);

        $response = $this->delete(route('admin.elections.destroy', $election));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');

        $this->assertDatabaseHas('elections', ['id' => $election->id]);
    });

    it('menolak menghapus pemilihan yang sudah memiliki data', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->create(['status' => ElectionStatus::Draft]);
        $voter = Voter::factory()->create();

        VoterEligibility::factory()->create([
            'election_id' => $election->id,
            'voter_id' => $voter->id,
        ]);

        $this->actingAs($admin);

        $response = $this->delete(route('admin.elections.destroy', $election));

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    });

    it('menolak mengedit pemilihan yang sudah OPEN', function () {
        $admin = User::factory()->superAdmin()->create(['is_active' => true]);
        $election = Election::factory()->open()->create();

        $this->actingAs($admin);

        $response = $this->put(route('admin.elections.update', $election), [
            'name' => 'Updated Name',
            'starts_at' => now()->subHour(),
            'ends_at' => now()->addDay(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('error');
    });
});
