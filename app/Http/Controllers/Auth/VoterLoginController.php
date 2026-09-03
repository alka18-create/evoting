<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Elections\Enums\VotingEventStatus;
use App\Domain\Voting\Services\VotingEventService;
use App\Http\Controllers\Controller;
use App\Models\Voter;
use App\Models\VotingEvent;
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

        return view('auth.voter-login', compact('votingEvents', 'hasEvents'));
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'student_id' => ['required', 'string'],
            // P0-06: token baru 8 char alfanumerik, tetap terima legacy 6 digit selama transisi
            'token' => ['required', 'string', 'min:6', 'max:16'],
            'voting_event_id' => ['required', 'integer', 'exists:voting_events,id'],
        ]);

        $studentId = $request->input('student_id');
        $token = $request->input('token');
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

        // Cek apakah voting event masih OPEN
        $votingEvent = VotingEvent::findOrFail($votingEventId);
        if ($votingEvent->status !== VotingEventStatus::Open) {
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
