<?php

use App\Domain\Elections\Enums\ElectionStatus;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Support\VotingToken;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Opsi B (sisi voter): pemilihan tunggal bisa dipilih & dimasuki langsung
 * dengan token eligibilitas — tanpa event.
 */
beforeEach(function () {
    $this->voter = Voter::factory()->create(['is_active' => true]);
    $this->election = Election::factory()->open()->create([
        'voting_event_id' => null,
        'created_by' => User::factory()->superAdmin()->create()->id,
    ]);
    $this->token = 'ABCDEFGH';
    $this->eligibility = VoterEligibility::factory()->create([
        'election_id' => $this->election->id,
        'voter_id' => $this->voter->id,
        'token_hash' => VotingToken::hash($this->token),
    ]);
});

it('halaman login menampilkan pemilihan tunggal yang dibuka', function () {
    $html = $this->get(route('vote.login'))->assertOk()->getContent();

    expect($html)->toContain('Pemilihan Tunggal')
        ->toContain($this->election->name);
});

it('login pemilihan tunggal dengan token benar masuk ke halaman voting', function () {
    $response = $this->post(route('vote.login.submit'), [
        'student_id' => $this->voter->student_id,
        'token' => $this->token,
        'election_id' => $this->election->id,
    ]);

    $response->assertRedirect(route('vote.index'));
    $response->assertSessionHas('eligibility_id', $this->eligibility->id);

    $html = $this->get(route('vote.index'))->assertOk()->getContent();
    expect($html)->toContain($this->election->name);
});

it('login pemilihan tunggal menolak token salah tanpa enumerasi', function () {
    $response = $this->from(route('vote.login'))->post(route('vote.login.submit'), [
        'student_id' => $this->voter->student_id,
        'token' => 'SALAH123',
        'election_id' => $this->election->id,
    ]);

    $response->assertRedirect(route('vote.login'));
    $response->assertSessionHasErrors('student_id');
    $this->assertGuest('voter');
});

it('login pemilihan tunggal menolak yang sudah vote', function () {
    $this->eligibility->forceFill(['status' => 'VOTED'])->save();

    $response = $this->from(route('vote.login'))->post(route('vote.login.submit'), [
        'student_id' => $this->voter->student_id,
        'token' => $this->token,
        'election_id' => $this->election->id,
    ]);

    $response->assertSessionHasErrors('token');
    $this->assertGuest('voter');
});

it('login election_id ditolak untuk pemilihan dalam event', function () {
    $event = VotingEvent::factory()->create();
    $inEvent = Election::factory()->open()->create([
        'voting_event_id' => $event->id,
        'created_by' => User::factory()->create()->id,
    ]);
    VoterEligibility::factory()->create([
        'election_id' => $inEvent->id,
        'voter_id' => $this->voter->id,
        'token_hash' => VotingToken::hash($this->token),
    ]);

    $response = $this->from(route('vote.login'))->post(route('vote.login.submit'), [
        'student_id' => $this->voter->student_id,
        'token' => $this->token,
        'election_id' => $inEvent->id,
    ]);

    // Generik (seolah kredensial salah) — harus lewat login event.
    $response->assertSessionHasErrors('student_id');
    $this->assertGuest('voter');
});

it('login pemilihan tunggal ditolak bila belum dibuka', function () {
    $draft = Election::factory()->create([
        'status' => ElectionStatus::Draft,
        'voting_event_id' => null,
        'created_by' => User::factory()->create()->id,
    ]);

    $response = $this->from(route('vote.login'))->post(route('vote.login.submit'), [
        'student_id' => $this->voter->student_id,
        'token' => $this->token,
        'election_id' => $draft->id,
    ]);

    $response->assertSessionHasErrors('student_id');
    $this->assertGuest('voter');
});
