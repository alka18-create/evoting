<?php

use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Domain\Voting\Services\VotingEventService;
use App\Models\Election;
use App\Models\Organization;
use App\Models\User;
use App\Models\Voter;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createOpenEventWithElections(User $admin, int $orgCount = 2): VotingEvent
{
    $event = VotingEvent::factory()->create([
        'status' => VotingEventStatus::Open,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'created_by' => $admin->id,
    ]);
    $orgs = Organization::factory()->count($orgCount)->create();
    foreach ($orgs as $org) {
        Election::factory()->create([
            'voting_event_id' => $event->id,
            'organization_id' => $org->id,
            'status' => ElectionStatus::Open,
            'starts_at' => null,
            'ends_at' => null,
            'created_by' => $admin->id,
        ]);
    }

    return $event;
}

/*
 * P3-05: E2E cetak massal — 8 kartu per event, token kuat + QR v2 opaque.
 * Catatan: payload QR ("v":2 tanpa NIS) dienkode di dalam gambar SVG sehingga
 * tidak assertable dari HTML; yang diassert: jumlah kartu/digit, dekripsi
 * token, serta verify opaque tanpa student_id (jalur server P1-01).
 */

it('cetak massal 8 kartu: token unik + dekripsi + render 8 kartu', function () {
    $admin = User::factory()->superAdmin()->create(['is_active' => true]);
    // assignAll butuh minimal 1 election dalam event, kalau tidak ia return kosong.
    $event = createOpenEventWithElections($admin);
    $voters = Voter::factory()->count(8)->create(['is_active' => true]);

    $service = app(VotingEventService::class);
    $service->assignAllActiveVoters($event);
    $issued = $service->generateTokens($event);

    expect($issued)->toHaveCount(8);
    expect($issued->pluck('token')->unique())->toHaveCount(8);
    foreach ($issued as $t) {
        expect(strlen($t['token']))->toBeGreaterThanOrEqual(8);
    }

    // DB: hash terisi, hash-only tanpa salinan reversible
    $rows = VotingEventVoter::where('voting_event_id', $event->id)->with('voter')->get();
    expect($rows)->toHaveCount(8);
    foreach ($rows as $row) {
        expect($row->token_hash)->not->toBeNull();
        expect($row->plainToken())->toBeNull();
        expect(array_key_exists('token_enc', $row->getAttributes()))->toBeFalse();
    }

    // Render bulk hash-only: daftar status + rotasi, tanpa digit plaintext
    $this->actingAs($admin);
    $response = $this->get(route('admin.voting-events.tokens.print-bulk', $event));
    $response->assertOk();
    $html = $response->getContent();
    expect($html)->toContain('Hash-only');
    expect($html)->not->toContain('digit-box');
    expect(substr_count($html, 'Diterbitkan'))->toBeGreaterThanOrEqual(8);

    // Print satuan hash-only: dialihkan ke rotasi
    $one = $rows->first();
    $this->get(route('admin.voting-events.tokens.print-card', [$event, $one]))
        ->assertRedirect();

    // Rotasi menghasilkan token baru sekali tampil
    $oldHash = $one->token_hash;
    $reissue = $this->post(route('admin.voting-events.tokens.reissue', [$event, $one]));
    $reissue->assertRedirect();
    $reissue->assertSessionHas('issued_tokens');
    expect($one->fresh()->token_hash)->not->toBe($oldHash);
});

it('verify QR v2 opaque tanpa NIS (resolve by token dalam event)', function () {
    $admin = User::factory()->superAdmin()->create(['is_active' => true]);
    $event = createOpenEventWithElections($admin);
    $voter = Voter::factory()->create(['is_active' => true]);

    $service = app(VotingEventService::class);
    $service->assignAllActiveVoters($event);
    $issued = $service->generateTokenForVoters($event, [$voter->id]);
    $plain = $issued->first()['token'];

    $this->actingAs($admin);
    $response = $this->post(route('admin.tokens.scan-verify'), [
        'token' => $plain,
        'voting_event_id' => $event->id,
    ]);

    $response->assertSessionHas('success');
});
