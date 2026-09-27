<?php

namespace App\Http\Controllers\Voter;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Voting\Services\VotingService;
use App\Events\VoteCasted;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\VoterEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VotingController extends Controller
{
    public function index(Request $request)
    {
        $eligibilityId = $request->session()->get('eligibility_id');
        $electionId = $request->session()->get('election_id');
        /** @var \App\Models\Voter|null $voter */
        $voter = Auth::guard('voter')->user();

        // P0: sesi legacy tanpa identitas voter = tidak valid.
        if (! $eligibilityId || ! $electionId || ! $voter || ! $voter->is_active) {
            Auth::guard('voter')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('vote.login');
        }

        $eligibility = VoterEligibility::with(['election', 'voter'])->findOrFail($eligibilityId);

        // P0-C1: eligibility harus milik voter yang login.
        if ((int) $eligibility->voter_id !== (int) $voter->getKey()
            || (int) $eligibility->election_id !== (int) $electionId) {
            Auth::guard('voter')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('vote.login');
        }

        // Cek apakah sudah vote
        if ($eligibility->hasVoted()) {
            return view('voter.voted', ['eligibility' => $eligibility]);
        }

        $candidates = Candidate::where('election_id', $electionId)
            ->orderBy('candidate_number')
            ->get();

        return view('voter.index', [
            'election' => $eligibility->election,
            'candidates' => $candidates,
            'eligibility' => $eligibility,
        ]);
    }

    public function vote(Request $request, VotingService $votingService)
    {
        $request->validate([
            'candidate_id' => 'required|integer|exists:candidates,id',
        ]);

        $eligibilityId = $request->session()->get('eligibility_id');
        $electionId = $request->session()->get('election_id');
        /** @var \App\Models\Voter|null $voter */
        $voter = Auth::guard('voter')->user();

        if (! $eligibilityId || ! $electionId || ! $voter || ! $voter->is_active) {
            Auth::guard('voter')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('vote.login');
        }

        // Idempotency: check if already voted before processing
        $eligibility = VoterEligibility::findOrFail($eligibilityId);

        // P0-C1: tolak eligibility milik voter lain.
        if ((int) $eligibility->voter_id !== (int) $voter->getKey()
            || (int) $eligibility->election_id !== (int) $electionId) {
            Auth::guard('voter')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('vote.login');
        }

        if ($eligibility->hasVoted()) {
            return redirect()->route('vote.login')
                ->with('error', 'Anda sudah memberikan suara untuk pemilihan ini.');
        }

        try {
            $ballot = $votingService->castVote(
                electionId: $electionId,
                candidateId: $request->input('candidate_id'),
                eligibilityId: $eligibilityId,
                voterId: (int) $voter->getKey(),
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            // Audit log
            AuditLogger::log(
                action: 'VOTE_SESSION_INVALIDATED',
                resourceType: 'VoterEligibility',
                resourceId: $eligibilityId,
                metadata: ['election_id' => $electionId]
            );

            // Broadcast realtime update
            $election = Election::find($electionId);
            $totalEligible = VoterEligibility::where('election_id', $electionId)->count();
            $totalVoted = VoterEligibility::where('election_id', $electionId)
                ->where('status', 'VOTED')
                ->count();
            $participationRate = $totalEligible > 0 ? round(($totalVoted / $totalEligible) * 100, 2) : 0;

            broadcast(new VoteCasted($election, $totalVoted, $totalEligible, $participationRate));

            // Invalidate voter session (security: prevent re-voting)
            Auth::guard('voter')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // One-time receipt: simpan ballot hash di cache 5 menit dengan
            // ID acak, agar URL konfirmasi tidak enumerable & sekali pakai.
            $receipt = Str::random(32);
            Cache::put('vote-receipt:' . $receipt, [$ballot->ballot_hash], now()->addMinutes(5));

            return redirect()->route('vote.confirmation', ['receipt' => $receipt]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function confirmation(Request $request)
    {
        // Pull = sekali pakai; refresh/link ulang tidak menampilkan hash lagi.
        $hashes = null;
        if ($request->query('receipt')) {
            $hashes = Cache::pull('vote-receipt:' . $request->query('receipt'));
        }

        // Backward-compat: tolak hash mentah di URL (enumerable).
        // Hanya receipt one-time yang dirender.

        return view('voter.confirmation', ['hashes' => $hashes]);
    }
}