<?php

use App\Domain\Elections\Enums\ElectionStatus;
use App\Models\Election;
use App\Models\User;
use App\Models\VotingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Opsi B: pemilihan standalone (tanpa event) sebagai jalur utama.
 * - store tanpa event wajib punya tanggal sendiri.
 * - pindah mode hanya saat DRAFT; detach wajib tanggal; attach wajib muat.
 * - lifecycle standalone (schedule → open) jalan tanpa event.
 */
beforeEach(function () {
    $this->admin = User::factory()->superAdmin()->create(['is_active' => true]);
    $this->actingAs($this->admin);
});

it('store tanpa event menolak bila tanpa tanggal', function () {
    $this->post(route('admin.elections.store'), ['name' => 'Tanpa Tanggal'])
        ->assertSessionHasErrors(['starts_at', 'ends_at']);

    expect(Election::where('name', 'Tanpa Tanggal')->exists())->toBeFalse();
});

it('store tanpa event berhasil bila ada tanggal', function () {
    $this->post(route('admin.elections.store'), [
        'name' => 'Ketua OSIS 2026',
        'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
    ])->assertRedirect(route('admin.elections.index'));

    $election = Election::where('name', 'Ketua OSIS 2026')->firstOrFail();
    expect($election->voting_event_id)->toBeNull()
        ->and($election->status)->toBe(ElectionStatus::Draft);
});

it('store ikut event boleh tanpa tanggal', function () {
    $event = VotingEvent::factory()->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'created_by' => $this->admin->id,
    ]);

    $this->post(route('admin.elections.store'), [
        'name' => 'Ikut Event',
        'voting_event_id' => $event->id,
    ])->assertRedirect(route('admin.elections.index'));

    expect(Election::where('name', 'Ikut Event')->firstOrFail()->voting_event_id)->toBe($event->id);
});

it('pindah mode ditolak bila tidak DRAFT', function () {
    $event = VotingEvent::factory()->create(['created_by' => $this->admin->id]);
    $election = Election::factory()->create([
        'status' => ElectionStatus::Scheduled,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'created_by' => $this->admin->id,
    ]);

    $this->put(route('admin.elections.update', $election), [
        'name' => $election->name,
        'voting_event_id' => $event->id,
    ])->assertSessionHasErrors('error');

    expect($election->fresh()->voting_event_id)->toBeNull();
});

it('attach ke event menolak bila tanggal di luar jadwal event', function () {
    $event = VotingEvent::factory()->create([
        'starts_at' => now()->addWeek(),
        'ends_at' => now()->addWeeks(2),
        'created_by' => $this->admin->id,
    ]);
    $election = Election::factory()->create([
        'status' => ElectionStatus::Draft,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'created_by' => $this->admin->id,
    ]);

    $this->put(route('admin.elections.update', $election), [
        'name' => $election->name,
        'voting_event_id' => $event->id,
    ])->assertSessionHasErrors('error');

    expect($election->fresh()->voting_event_id)->toBeNull();
});

it('attach ke event berhasil bila tanggal muat', function () {
    $event = VotingEvent::factory()->create([
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addWeeks(2),
        'created_by' => $this->admin->id,
    ]);
    $election = Election::factory()->create([
        'status' => ElectionStatus::Draft,
        'starts_at' => now()->addDays(2),
        'ends_at' => now()->addWeek(),
        'created_by' => $this->admin->id,
    ]);

    $this->put(route('admin.elections.update', $election), [
        'name' => $election->name,
        'voting_event_id' => $event->id,
    ])->assertRedirect(route('admin.elections.index'));

    expect($election->fresh()->voting_event_id)->toBe($event->id);
});

it('detach dari event menolak bila tanpa tanggal sendiri', function () {
    $event = VotingEvent::factory()->create(['created_by' => $this->admin->id]);
    $election = Election::factory()->create([
        'status' => ElectionStatus::Draft,
        'voting_event_id' => $event->id,
        'starts_at' => null,
        'ends_at' => null,
        'created_by' => $this->admin->id,
    ]);

    $this->put(route('admin.elections.update', $election), [
        'name' => $election->name,
        'voting_event_id' => '',
    ])->assertSessionHasErrors('error');

    expect($election->fresh()->voting_event_id)->toBe($event->id);
});

it('detach dari event berhasil bila disertai tanggal', function () {
    $event = VotingEvent::factory()->create(['created_by' => $this->admin->id]);
    $election = Election::factory()->create([
        'status' => ElectionStatus::Draft,
        'voting_event_id' => $event->id,
        'starts_at' => null,
        'ends_at' => null,
        'created_by' => $this->admin->id,
    ]);

    $this->put(route('admin.elections.update', $election), [
        'name' => $election->name,
        'voting_event_id' => '',
        'starts_at' => now()->addDay()->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDays(2)->format('Y-m-d\TH:i'),
    ])->assertRedirect(route('admin.elections.index'));

    expect($election->fresh()->voting_event_id)->toBeNull();
});

it('lifecycle standalone schedule lalu open tanpa event', function () {
    $election = Election::factory()->create([
        'status' => ElectionStatus::Draft,
        'voting_event_id' => null,
        'starts_at' => now()->addDay(),
        'ends_at' => now()->addDays(2),
        'created_by' => $this->admin->id,
    ]);

    // Mundurkan jadwal lewat update agar bisa dibuka sekarang.
    $this->put(route('admin.elections.update', $election), [
        'name' => $election->name,
        'starts_at' => now()->subHour()->format('Y-m-d\TH:i'),
        'ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
    ])->assertRedirect(route('admin.elections.index'));

    $this->post(route('admin.elections.schedule', $election))->assertRedirect();
    $this->post(route('admin.elections.open', $election))->assertRedirect();

    expect($election->fresh()->status)->toBe(ElectionStatus::Open);
});
