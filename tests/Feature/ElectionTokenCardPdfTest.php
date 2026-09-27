<?php

use App\Domain\Elections\Enums\ElectionStatus;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Models\VotingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Sisi Election (/admin/elections/{id}/tokens): PDF kartu disimpan saat
 * issue/rotasi, ditampilkan & bisa diunduh/dihapus — scope terpisah
 * "el-{id}" agar tidak bentrok dengan ID VotingEvent.
 */
beforeEach(function () {
    Storage::fake('local');

    $this->admin = User::factory()->superAdmin()->create(['is_active' => true]);
    $event = VotingEvent::factory()->create(['created_by' => $this->admin->id]);
    $this->election = Election::factory()->create([
        'voting_event_id' => $event->id,
        'status' => ElectionStatus::Open,
        'created_by' => $this->admin->id,
    ]);
    $this->voters = Voter::factory()->count(2)->create(['is_active' => true]);

    $this->actingAs($this->admin);
});

it('issue token menyimpan PDF kartu ke scope el-{id} di disk privat', function () {
    $response = $this->post(route('admin.elections.tokens.issue', $this->election), [
        'voter_ids' => $this->voters->pluck('id')->all(),
    ]);

    $response->assertRedirect(route('admin.elections.tokens.index', $this->election));
    $response->assertSessionHas('issued_tokens');
    $response->assertSessionHas('saved_card_pdf');

    $file = session('saved_card_pdf');
    expect($file)->toMatch('/^kartu-[A-Za-z0-9\-]+\.pdf$/');

    $relative = 'token-cards/el-' . $this->election->id . '/' . $file;
    expect(Storage::disk('local')->exists($relative))->toBeTrue();
    expect(substr(Storage::disk('local')->get($relative), 0, 5))->toBe('%PDF-');

    // Scope harus terpisah dari ID VotingEvent mana pun.
    expect(Storage::disk('local')->exists('token-cards/' . $this->election->id . '/' . $file))->toBeFalse();
});

it('halaman index menampilkan panel PDF tersimpan beserta tombol unduh dan hapus', function () {
    $this->post(route('admin.elections.tokens.issue', $this->election), [
        'voter_ids' => $this->voters->pluck('id')->all(),
    ]);

    $html = $this->get(route('admin.elections.tokens.index', $this->election))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('PDF Kartu Tersimpan')
        ->toContain('tokens/cards-pdf/')
        ->toContain('Cetak Kartu');
});

it('print-bulk menampilkan daftar PDF tersimpan dan banner hash-only', function () {
    $this->post(route('admin.elections.tokens.issue', $this->election), [
        'voter_ids' => $this->voters->pluck('id')->all(),
    ]);

    $html = $this->get(route('admin.elections.tokens.print-bulk', $this->election))
        ->assertOk()
        ->getContent();

    expect($html)->toContain('PDF Kartu Tersimpan')
        ->toContain('tokens/cards-pdf/')
        ->toContain('Hash-only');
});

it('admin dapat mengunduh PDF dan nama file di luar pola ditolak', function () {
    $this->post(route('admin.elections.tokens.issue', $this->election), [
        'voter_ids' => $this->voters->pluck('id')->all(),
    ]);
    $file = session('saved_card_pdf');

    $response = $this->get(route('admin.elections.tokens.card-pdf.download', [$this->election, $file]));
    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain($file);

    $this->get(route('admin.elections.tokens.card-pdf.download', [$this->election, '..-..-evil.pdf']))
        ->assertNotFound();
    $this->delete(route('admin.elections.tokens.card-pdf.destroy', [$this->election, 'index.php']))
        ->assertNotFound();
});

it('menghapus PDF menghilangkan file dari disk', function () {
    $this->post(route('admin.elections.tokens.issue', $this->election), [
        'voter_ids' => $this->voters->pluck('id')->all(),
    ]);
    $file = session('saved_card_pdf');
    $relative = 'token-cards/el-' . $this->election->id . '/' . $file;

    $this->delete(route('admin.elections.tokens.card-pdf.destroy', [$this->election, $file]))
        ->assertRedirect();

    expect(Storage::disk('local')->exists($relative))->toBeFalse();
});

it('rotasi token juga menghasilkan PDF kartu baru', function () {
    $this->post(route('admin.elections.tokens.issue', $this->election), [
        'voter_ids' => [$this->voters->first()->id],
    ]);
    $first = session('saved_card_pdf');

    $eligibility = \App\Models\VoterEligibility::where('election_id', $this->election->id)->firstOrFail();

    $this->post(route('admin.elections.tokens.reissue', [$this->election, $eligibility]))
        ->assertSessionHas('issued_tokens')
        ->assertSessionHas('saved_card_pdf');

    $second = session('saved_card_pdf');
    expect($second)->not->toBe($first);
    expect(count(Storage::disk('local')->files('token-cards/el-' . $this->election->id)))->toBe(2);
});
