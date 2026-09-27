<?php

use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Domain\Voting\Services\VotingEventService;
use App\Models\Election;
use App\Models\Organization;
use App\Models\User;
use App\Models\Voter;
use App\Models\VotingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Opsi A: PDF kartu dibuat saat token diterbitkan dan disimpan ke disk
 * privat (storage/app/private/token-cards) — bisa diunduh ulang, sementara
 * token plaintext tetap tidak pernah masuk database.
 */
beforeEach(function () {
    Storage::fake('local');

    $this->admin = User::factory()->superAdmin()->create(['is_active' => true]);
    $this->event = VotingEvent::factory()->create([
        'status' => VotingEventStatus::Open,
        'starts_at' => now()->subDay(),
        'ends_at' => now()->addDay(),
        'created_by' => $this->admin->id,
    ]);
    $org = Organization::factory()->create();
    Election::factory()->create([
        'voting_event_id' => $this->event->id,
        'organization_id' => $org->id,
        'status' => ElectionStatus::Open,
        'starts_at' => null,
        'ends_at' => null,
        'created_by' => $this->admin->id,
    ]);

    Voter::factory()->count(3)->create(['is_active' => true]);
    app(VotingEventService::class)->assignAllActiveVoters($this->event);

    $this->actingAs($this->admin);
});

it('generate token semua langsung menyimpan PDF kartu ke disk privat', function () {
    $response = $this->post(route('admin.voting-events.tokens.issue-all', $this->event));

    $response->assertRedirect(route('admin.voting-events.tokens.print-bulk', $this->event));
    $response->assertSessionHas('issued_tokens');
    $response->assertSessionHas('saved_card_pdf');

    $file = session('saved_card_pdf');
    expect($file)->toMatch('/^kartu-[A-Za-z0-9\-]+\.pdf$/');

    $relative = 'token-cards/' . $this->event->id . '/' . $file;
    expect(Storage::disk('local')->exists($relative))->toBeTrue();

    $content = Storage::disk('local')->get($relative);
    expect(substr($content, 0, 5))->toBe('%PDF-');
});

it('PDF hanya tersimpan di disk privat — tidak ada di disk public', function () {
    $this->post(route('admin.voting-events.tokens.issue-all', $this->event));

    $public = collect(Storage::disk('public')->allFiles());
    expect($public->filter(fn ($f) => str_contains($f, '.pdf')))->toBeEmpty();
});

it('halaman kartu menampilkan PDF tersimpan beserta tombol unduh dan hapus', function () {
    $this->post(route('admin.voting-events.tokens.issue-all', $this->event));

    $html = $this->get(route('admin.voting-events.tokens.print-bulk', $this->event))
        ->assertOk()
        ->getContent();

    // Nama route tidak muncul di HTML — yang ada adalah URL (…/tokens/cards-pdf/…).
    expect($html)->toContain('PDF Kartu Tersimpan')
        ->toContain('tokens/cards-pdf/')
        ->toContain('Hash-only');
});

it('admin dapat mengunduh PDF kartu', function () {
    $this->post(route('admin.voting-events.tokens.issue-all', $this->event));
    $file = session('saved_card_pdf');

    $response = $this->get(route('admin.voting-events.tokens.card-pdf.download', [$this->event, $file]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain($file);
});

it('nama file di luar pola kartu ditolak (path traversal)', function () {
    $this->get(route('admin.voting-events.tokens.card-pdf.download', [$this->event, '..-..-evil.pdf']))
        ->assertNotFound();

    $this->delete(route('admin.voting-events.tokens.card-pdf.destroy', [$this->event, 'index.php']))
        ->assertNotFound();
});

it('PDF event lain tidak bisa diakses lewat event ini', function () {
    $this->post(route('admin.voting-events.tokens.issue-all', $this->event));
    $file = session('saved_card_pdf');

    $other = VotingEvent::factory()->create(['created_by' => $this->admin->id]);

    $this->get(route('admin.voting-events.tokens.card-pdf.download', [$other, $file]))
        ->assertNotFound();
});

it('menghapus PDF menghilangkan file dari disk', function () {
    $this->post(route('admin.voting-events.tokens.issue-all', $this->event));
    $file = session('saved_card_pdf');
    $relative = 'token-cards/' . $this->event->id . '/' . $file;

    $this->delete(route('admin.voting-events.tokens.card-pdf.destroy', [$this->event, $file]))
        ->assertRedirect();

    expect(Storage::disk('local')->exists($relative))->toBeFalse();
});

it('rotasi token juga menghasilkan PDF kartu baru', function () {
    $this->post(route('admin.voting-events.tokens.issue-all', $this->event));
    $first = session('saved_card_pdf');

    $evv = $this->event->votingEventVoters()->first();
    $this->post(route('admin.voting-events.tokens.reissue', [$this->event, $evv]))
        ->assertSessionHas('issued_tokens')
        ->assertSessionHas('saved_card_pdf');

    $second = session('saved_card_pdf');
    expect($second)->not->toBe($first);

    $dir = 'token-cards/' . $this->event->id;
    expect(count(Storage::disk('local')->files($dir)))->toBe(2);
});
