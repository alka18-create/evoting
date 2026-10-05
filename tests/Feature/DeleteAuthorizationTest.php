<?php

use App\Models\Election;
use App\Models\Organization;
use App\Models\User;
use App\Models\Voter;
use App\Models\VotingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Hapus data = superadmin saja (policy). Admin/Operator harus dapat 403
 * DAN tombolnya tidak tampil di daftar (dulu tombol tampil → user bingung
 * melihat error 403).
 */
beforeEach(function () {
    $this->superadmin = User::factory()->superAdmin()->create(['is_active' => true]);
    $this->admin = User::factory()->admin()->create(['is_active' => true]);

    $this->voter = Voter::factory()->create(['is_active' => true]);
    $this->org = Organization::factory()->create();
    $this->event = VotingEvent::factory()->create(['created_by' => $this->superadmin->id]);
    $this->election = Election::factory()->create([
        'organization_id' => $this->org->id,
        'voting_event_id' => $this->event->id,
        'created_by' => $this->superadmin->id,
    ]);
});

it('admin biasa ditolak 403 saat menghapus pemilih', function () {
    $this->actingAs($this->admin)
        ->delete(route('admin.voters.destroy', $this->voter))
        ->assertForbidden();

    expect(Voter::find($this->voter->id))->not->toBeNull();
});

it('tombol hapus tidak tampil untuk admin biasa', function () {
    $html = $this->actingAs($this->admin)
        ->get(route('admin.voters.index'))
        ->assertOk()
        ->getContent();

    // Route hapus pemilih tidak boleh muncul di markup admin biasa.
    expect($html)->not->toContain(route('admin.voters.destroy', $this->voter));
});

it('tombol hapus tampil untuk superadmin', function () {
    $html = $this->actingAs($this->superadmin)
        ->get(route('admin.voters.index'))
        ->assertOk()
        ->getContent();

    expect($html)->toContain(route('admin.voters.destroy', $this->voter));
});

it('superadmin dapat menghapus pemilih tanpa relasi', function () {
    $this->actingAs($this->superadmin)
        ->delete(route('admin.voters.destroy', $this->voter))
        ->assertRedirect(route('admin.voters.index'));

    expect(Voter::find($this->voter->id))->toBeNull();
});

it('admin biasa ditolak 403 untuk hapus pemilihan, event, dan organisasi', function () {
    $this->actingAs($this->admin);

    $this->delete(route('admin.elections.destroy', $this->election))->assertForbidden();
    $this->delete(route('admin.voting-events.destroy', $this->event))->assertForbidden();
    $this->delete(route('admin.organizations.destroy', $this->org))->assertForbidden();

    expect(Election::find($this->election->id))->not->toBeNull();
    expect(VotingEvent::find($this->event->id))->not->toBeNull();
    expect(Organization::find($this->org->id))->not->toBeNull();
});
