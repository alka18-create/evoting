<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Elections\Enums\ElectionStatus;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Domain\Voting\Services\VotingEventService;
use App\Domain\Voting\Services\VotingGate;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Support\VotingToken;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class VoterLoginController extends Controller
{
    public function showLoginForm()
    {
        $votingEvents = VotingEvent::withCount('elections')
            ->where('status', VotingEventStatus::Open)
            ->where('starts_at', '<=', now())
            ->where('ends_at', '>=', now())
            ->orderBy('starts_at', 'desc')
            ->get();

        // Fallback legacy: jika belum ada voting_events, tetap support election lama
        $hasEvents = $votingEvents->isNotEmpty();

        // Opsi B: pemilihan tunggal (standalone, tanpa event) yang sedang
        // dibuka — login langsung dengan token eligibilitasnya.
        $standaloneElections = Election::whereNull('voting_event_id')
            ->where('status', ElectionStatus::Open)
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            })
            ->orderBy('starts_at', 'desc')
            ->get();

        return view('auth.voter-login', compact('votingEvents', 'hasEvents', 'standaloneElections'));
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'string'],
            // P0-06: token baru 8 char alfanumerik, tetap terima legacy 6 digit selama transisi
            'token' => ['required', 'string', 'min:6', 'max:16'],
            'voting_event_id' => ['required_without:election_id', 'integer', 'exists:voting_events,id'],
            'election_id' => ['required_without:voting_event_id', 'integer', 'exists:elections,id'],
        ]);

        $studentId = $request->input('student_id');
        $token = $request->input('token');

        // Jalur pemilihan tunggal (tanpa event).
        if ($request->filled('election_id')) {
            return $this->loginStandaloneElection($request, (int) $request->input('election_id'), $studentId, $token);
        }

        $votingEventId = $request->input('voting_event_id');

        // P0-04: pesan generik tunggal agar tidak bisa enumerasi NIS vs token.
        $genericError = ['student_id' => ['Kredensial tidak valid atau tidak dapat digunakan.']];

        // P1-03: catat kegagalan tanpa token plain untuk deteksi brute force.
        $logFailed = function (string $reason) use ($votingEventId, $studentId) {
            \App\Domain\Auditing\Services\AuditLogger::log(
                action: 'VOTER_LOGIN_FAILED',
                resourceType: 'VotingEventVoter',
                resourceId: 0,
                metadata: ['voting_event_id' => $votingEventId, 'student_id' => $studentId, 'reason' => $reason]
            );
        };

        // P1-03: lockout progresif — melengkapi throttle per-menit di
        // middleware. Blokir sementara bila terlalu banyak gagal dari IP ini
        // untuk NIS ini (brute force), tanpa memblokir siswa sah (sukses
        // tidak dihitung).
        $lockoutMax = (int) config('auth.voter_lockout.max_attempts', 10);
        $lockoutMinutes = (int) config('auth.voter_lockout.decay_minutes', 15);
        $recentFails = \App\Models\AuditLog::where('action', 'VOTER_LOGIN_FAILED')
            ->where('ip_address', $request->ip())
            ->where('created_at', '>=', now()->subMinutes($lockoutMinutes))
            ->where('metadata->student_id', $studentId)
            ->count();

        if ($recentFails >= $lockoutMax) {
            \App\Domain\Auditing\Services\AuditLogger::log(
                action: 'VOTER_LOGIN_LOCKED',
                resourceType: 'VotingEventVoter',
                resourceId: 0,
                metadata: ['voting_event_id' => $votingEventId, 'student_id' => $studentId, 'fails' => $recentFails]
            );

            $e = ValidationException::withMessages([
                'student_id' => ["Terlalu banyak percobaan gagal. Coba lagi dalam {$lockoutMinutes} menit."],
            ]);
            $e->status = 429;

            throw $e;
        }

        // Cari voter
        /** @var Voter|null $voter */
        $voter = Voter::where('student_id', $studentId)
            ->where('is_active', true)
            ->first();

        if (! $voter) {
            $logFailed('unknown_voter');
            throw ValidationException::withMessages($genericError);
        }

        // Cari VotingEventVoter via hash (dual-read transisi + auto-upgrade).
        // 1 token untuk semua organisasi.
        $eventVoter = VotingEventService::resolveByToken($votingEventId, $voter->id, $token);

        if (! $eventVoter) {
            $logFailed('bad_token');
            throw ValidationException::withMessages($genericError);
        }

        // P0-06: tolak token expired (token null/hash null dianggap invalid).
        if ($eventVoter->isExpired()) {
            $logFailed('expired');
            throw ValidationException::withMessages($genericError);
        }

        // Cek apakah voting event masih OPEN (status + window via gerbang P0).
        $votingEvent = VotingEvent::findOrFail($votingEventId);
        try {
            \App\Domain\Voting\Services\VotingGate::assertEventOpen($votingEvent);
        } catch (\Exception) {
            $logFailed('event_closed');
            throw ValidationException::withMessages($genericError);
        }

        // Cek apakah voter sudah vote semua elections dalam event (wizard all-or-nothing: jika sudah VOTED semua, tolak)
        $electionIds = $votingEvent->elections()->pluck('id');
        if ($electionIds->isNotEmpty()) {
            $alreadyVotedCount = \App\Models\VoterEligibility::whereIn('election_id', $electionIds)
                ->where('voter_id', $voter->id)
                ->where('status', 'VOTED')
                ->count();
            if ($alreadyVotedCount === $electionIds->count() && $alreadyVotedCount > 0) {
                throw ValidationException::withMessages([
                    'token' => ['Anda sudah memberikan suara untuk semua pemilihan dalam event ini.'],
                ]);
            }
        }

        // Regenerate session
        $request->session()->regenerate();

        // Login via voter guard
        Auth::guard('voter')->loginById($voter->id);

        // Store voting_event_id + voter_id + clear wizard selections
        $request->session()->put('voting_event_id', $votingEventId);
        $request->session()->put('voting_event_voter_id', $eventVoter->id);
        // P1-02: penanda aktivitas awal untuk idle-timeout sesi voter.
        $request->session()->put('voter_last_activity', now()->timestamp);
        $request->session()->forget('wizard.selections');
        $request->session()->forget('eligibility_id');
        $request->session()->forget('election_id');

        // Audit log
        \App\Domain\Auditing\Services\AuditLogger::log(
            action: 'VOTER_LOGIN',
            resourceType: 'VotingEventVoter',
            resourceId: $eventVoter->id,
            metadata: ['voting_event_id' => $votingEventId]
        );

        return redirect()->route('vote.wizard.step', ['step' => 1]);
    }

    /**
     * Login langsung ke pemilihan tunggal (tanpa event) dengan token
     * eligibilitas. Properti keamanan disamakan dengan jalur event:
     * pesan generik (anti-enumerasi) + lockout progresif + audit.
     */
    private function loginStandaloneElection(Request $request, int $electionId, string $studentId, string $token): RedirectResponse
    {
        // P0-04: pesan generik tunggal agar tidak bisa enumerasi NIS vs token.
        $genericError = ['student_id' => ['Kredensial tidak valid atau tidak dapat digunakan.']];

        $logFailed = function (string $reason) use ($electionId, $studentId) {
            \App\Domain\Auditing\Services\AuditLogger::log(
                action: 'VOTER_LOGIN_FAILED',
                resourceType: 'VoterEligibility',
                resourceId: 0,
                metadata: ['election_id' => $electionId, 'student_id' => $studentId, 'reason' => $reason]
            );
        };

        // P1-03: lockout progresif, sama seperti jalur event.
        $lockoutMax = (int) config('auth.voter_lockout.max_attempts', 10);
        $lockoutMinutes = (int) config('auth.voter_lockout.decay_minutes', 15);
        $recentFails = \App\Models\AuditLog::where('action', 'VOTER_LOGIN_FAILED')
            ->where('ip_address', $request->ip())
            ->where('created_at', '>=', now()->subMinutes($lockoutMinutes))
            ->where('metadata->student_id', $studentId)
            ->count();

        if ($recentFails >= $lockoutMax) {
            \App\Domain\Auditing\Services\AuditLogger::log(
                action: 'VOTER_LOGIN_LOCKED',
                resourceType: 'VoterEligibility',
                resourceId: 0,
                metadata: ['election_id' => $electionId, 'student_id' => $studentId, 'fails' => $recentFails]
            );

            $e = ValidationException::withMessages([
                'student_id' => ["Terlalu banyak percobaan gagal. Coba lagi dalam {$lockoutMinutes} menit."],
            ]);
            $e->status = 429;

            throw $e;
        }

        $election = Election::findOrFail($electionId);

        // Jalur ini khusus pemilihan tunggal; yang dalam event lewat login event.
        if ($election->voting_event_id !== null) {
            $logFailed('use_event_login');
            throw ValidationException::withMessages($genericError);
        }

        try {
            VotingGate::assertElectionVotable($election);
        } catch (\Exception) {
            $logFailed('election_closed');
            throw ValidationException::withMessages($genericError);
        }

        /** @var Voter|null $voter */
        $voter = Voter::where('student_id', $studentId)
            ->where('is_active', true)
            ->first();

        if (! $voter) {
            $logFailed('unknown_voter');
            throw ValidationException::withMessages($genericError);
        }

        $eligibility = VoterEligibility::where('election_id', $election->id)
            ->where('voter_id', $voter->id)
            ->where('token_hash', VotingToken::hash($token))
            ->first();

        if (! $eligibility || ! $eligibility->hasToken()) {
            $logFailed('bad_token');
            throw ValidationException::withMessages($genericError);
        }

        // P0-06: tolak token expired.
        if ($eligibility->isExpired()) {
            $logFailed('expired');
            throw ValidationException::withMessages($genericError);
        }

        if ($eligibility->hasVoted()) {
            throw ValidationException::withMessages([
                'token' => ['Anda sudah memberikan suara untuk pemilihan ini.'],
            ]);
        }

        // Regenerate session
        $request->session()->regenerate();

        // Login via voter guard
        Auth::guard('voter')->loginById($voter->id);

        // Sesi jalur tunggal (legacy VotingController): eligibility + election.
        $request->session()->put('eligibility_id', $eligibility->id);
        $request->session()->put('election_id', $election->id);
        // P1-02: penanda aktivitas awal untuk idle-timeout sesi voter.
        $request->session()->put('voter_last_activity', now()->timestamp);
        $request->session()->forget('wizard.selections');
        $request->session()->forget('voting_event_id');
        $request->session()->forget('voting_event_voter_id');

        // Audit log
        \App\Domain\Auditing\Services\AuditLogger::log(
            action: 'VOTER_LOGIN',
            resourceType: 'VoterEligibility',
            resourceId: $eligibility->id,
            metadata: ['election_id' => $election->id]
        );

        return redirect()->route('vote.index');
    }

    public function logout(Request $request): RedirectResponse
    {
        $eventVoterId = $request->session()->get('voting_event_voter_id');

        Auth::guard('voter')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($eventVoterId) {
            \App\Domain\Auditing\Services\AuditLogger::log(
                action: 'VOTER_LOGOUT',
                resourceType: 'VotingEventVoter',
                resourceId: $eventVoterId
            );
        }

        return redirect()->route('vote.login');
    }
}
