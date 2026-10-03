<?php

use App\Models\Ballot;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\Organization;
use App\Models\User;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

/**
 * Hapus data bertingkat dari aplikasi (pengganti hapus-via-terminal):
 * konfirmasi ketik-nama bila ada relasi, cascade dalam transaksi.
 */
beforeEach(function () {
    Storage::fake('local');
    Storage::fake('public');

    $this->admin = User::factory()->superAdmin()->create(['is_active' => true]);
    $this->actingAs($this->admin);

    $this->org = Organization::factory()->create();
    $this->event = VotingEvent::factory()->create(['created_by' => $this->admin->id]);
    $this->election = Election::factory()->create([
        'organization_id' => $this->org->id,
        'voting_event_id' => $this->event->id,
        'created_by' => $this->admin->id,
    ]);
    $this->candidate = Candidate::factory()->create(['election_id' => $this->election->id]);
    $this->voter = Voter::factory()->create(['is_active' => true]);
    $this->eligibility = VoterEligibility::factory()->create([
        'election_id' => $this->election->id,
        'voter_id' => $this->voter->id,
    ]);
    $this->eventVoter = VotingEventVoter::factory()->create([
        'voting_event_id' => $this->event->id,
        'voter_id' => $this->voter->id,
    ]);
    $this->ballot = Ballot::factory()->forElection($this->election, $this->candidate)->create();

    Storage::disk('local')->put('token-cards/el-' . $this->election->id . '/kartu-test.pdf', 'pdf');
    Storage::disk('local')->put('token-cards/' . $this->event->id . '/kartu-test.pdf', 'pdf');
});

it('hapus voter tanpa relasi langsung jalan', function () {
    $loner = Voter::factory()->create(['is_active' => true]);

    $this->delete(route('admin.voters.destroy', $loner))
        ->assertRedirect(route('admin.voters.index'));

    expect(Voter::find($loner->id))->toBeNull();
});

it('hapus voter berelasi dialihkan ke konfirmasi lalu cascade', function () {
    // Tanpa konfirmasi → ke halaman konfirmasi.
    $this->delete(route('admin.voters.destroy', $this->voter))
        ->assertRedirect(route('admin.voters.delete-confirm', $this->voter));

    $this->get(route('admin.voters.delete-confirm', $this->voter))
        ->assertOk()
        ->assertSee($this->voter->name);

    // Konfirmasi salah → ditolak.
    $this->delete(route('admin.voters.destroy', $this->voter), ['confirmation' => 'salah'])
        ->assertSessionHasErrors('error');
    expect(Voter::find($this->voter->id))->not->toBeNull();

    // Konfirmasi benar → cascade (ballot anonim tetap ada).
    $this->delete(route('admin.voters.destroy', $this->voter), ['confirmation' => $this->voter->name])
        ->assertRedirect(route('admin.voters.index'));

    expect(Voter::find($this->voter->id))->toBeNull();
    expect(VoterEligibility::where('voter_id', $this->voter->id)->count())->toBe(0);
    expect(VotingEventVoter::where('voter_id', $this->voter->id)->count())->toBe(0);
    expect(Ballot::find($this->ballot->id))->not->toBeNull();
});

it('hapus election cascade termasuk suara, kandidat, dan PDF', function () {
    $eid = $this->election->id;
    $name = $this->election->name;

    $this->delete(route('admin.elections.destroy', $this->election))
        ->assertRedirect(route('admin.elections.delete-confirm', $this->election));

    $this->delete(route('admin.elections.destroy', $this->election), ['confirmation' => $name])
        ->assertRedirect(route('admin.elections.index'));

    expect(Election::find($eid))->toBeNull();
    expect(Candidate::where('election_id', $eid)->count())->toBe(0);
    expect(VoterEligibility::where('election_id', $eid)->count())->toBe(0);
    expect(Ballot::where('election_id', $eid)->count())->toBe(0);
    expect(Storage::disk('local')->exists('token-cards/el-' . $eid))->toBeFalse();
    // Event & org & voter tetap.
    expect(VotingEvent::find($this->event->id))->not->toBeNull();
    expect(Organization::find($this->org->id))->not->toBeNull();
    expect(Voter::find($this->voter->id))->not->toBeNull();
});

it('hapus event cascade seluruh isi event', function () {
    $eventId = $this->event->id;
    $name = $this->event->name;

    $this->get(route('admin.voting-events.delete-confirm', $this->event))->assertOk();

    $this->delete(route('admin.voting-events.destroy', $this->event), ['confirmation' => $name])
        ->assertRedirect(route('admin.voting-events.index'));

    expect(VotingEvent::find($eventId))->toBeNull();
    expect(Election::where('voting_event_id', $eventId)->count())->toBe(0);
    expect(VotingEventVoter::where('voting_event_id', $eventId)->count())->toBe(0);
    expect(Storage::disk('local')->exists('token-cards/' . $eventId))->toBeFalse();
    // Org & voter global tetap.
    expect(Organization::find($this->org->id))->not->toBeNull();
    expect(Voter::find($this->voter->id))->not->toBeNull();
});

it('hapus organisasi cascade pemilihannya', function () {
    $orgId = $this->org->id;
    $name = $this->org->name;

    $this->get(route('admin.organizations.delete-confirm', $this->org))->assertOk();

    $this->delete(route('admin.organizations.destroy', $this->org), ['confirmation' => $name])
        ->assertRedirect(route('admin.organizations.index'));

    expect(Organization::find($orgId))->toBeNull();
    expect(Election::where('organization_id', $orgId)->count())->toBe(0);
    expect(Ballot::find($this->ballot->id))->toBeNull();
});
