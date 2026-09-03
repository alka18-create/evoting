<?php

namespace App\Http\Controllers\Voter;

use App\Domain\Voting\Services\VotingService;
use App\Events\VoteCasted;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WizardController extends Controller
{
    private function getWizardData(Request $request): array
    {
        $votingEventId = $request->session()->get('voting_event_id');
        $voterId = Auth::guard('voter')->id();

        if (! $votingEventId || ! $voterId) {
            abort(redirect()->route('vote.login'));
        }

        $votingEvent = VotingEvent::with(['elections.organization'])->findOrFail($votingEventId);

        // P2-01: 1 query eligibilitas untuk semua election (hindari N+1).
        $allElections = $votingEvent->elections->sortBy([['organization_id', 'asc'], ['id', 'asc']])->values();
        $electionIds = $allElections->pluck('id')->all();

        $statusByElection = $electionIds === []
            ? collect()
            : VoterEligibility::whereIn('election_id', $electionIds)
                ->where('voter_id', $voterId)
                ->pluck('status', 'election_id');

        // Elections dalam event yang masih ELIGIBLE (skip yang sudah VOTED legacy)
        $elections = $allElections
            ->filter(fn (Election $e) => ($statusByElection[$e->id] ?? null) === 'ELIGIBLE')
            ->values();

        // Jika semua sudah VOTED, anggap selesai
        if ($elections->isEmpty()) {
            $hasAnyVoted = $statusByElection->contains('VOTED');

            if ($hasAnyVoted) {
                return ['voted' => true, 'votingEvent' => $votingEvent, 'elections' => collect()];
            }
        }

        return ['voted' => false, 'votingEvent' => $votingEvent, 'elections' => $elections];
    }

    public function step(Request $request, int $step)
    {
        $data = $this->getWizardData($request);

        if ($data['voted']) {
            return view('voter.voted', ['votingEvent' => $data['votingEvent']]);
        }

        $elections = $data['elections'];
        $votingEvent = $data['votingEvent'];

        if ($step < 1 || $step > $elections->count()) {
            return redirect()->route('vote.wizard.step', ['step' => 1]);
        }

        $election = $elections[$step - 1];
        $candidates = Candidate::where('election_id', $election->id)->orderBy('candidate_number')->get();

        $selections = $request->session()->get('wizard.selections', []);
        $selectedCandidateId = $selections[$election->id] ?? null;

        $voter = Auth::guard('voter')->user();

        return view('voter.wizard.step', [
            'votingEvent' => $votingEvent,
            'elections' => $elections,
            'election' => $election,
            'candidates' => $candidates,
            'step' => $step,
            'totalSteps' => $elections->count(),
            'selectedCandidateId' => $selectedCandidateId,
            'selections' => $selections,
            'voter' => $voter,
        ]);
    }

    public function storeStep(Request $request, int $step)
    {
        $request->validate([
            'candidate_id' => 'required|integer|exists:candidates,id',
        ]);

        $data = $this->getWizardData($request);
        $elections = $data['elections'];

        if ($step < 1 || $step > $elections->count()) {
            return redirect()->route('vote.wizard.step', ['step' => 1]);
        }

        $election = $elections[$step - 1];
        $candidateId = (int) $request->input('candidate_id');

        // Verify candidate belongs to this election
        $candidate = Candidate::where('id', $candidateId)->where('election_id', $election->id)->first();
        if (! $candidate) {
            return back()->withErrors(['candidate_id' => 'Kandidat tidak valid untuk pemilihan ini.']);
        }

        $selections = $request->session()->get('wizard.selections', []);
        $selections[$election->id] = $candidateId;
        $request->session()->put('wizard.selections', $selections);

        // Next step or review
        if ($step < $elections->count()) {
            return redirect()->route('vote.wizard.step', ['step' => $step + 1]);
        }

        return redirect()->route('vote.wizard.review');
    }

    public function review(Request $request)
    {
        $data = $this->getWizardData($request);

        if ($data['voted']) {
            return view('voter.voted', ['votingEvent' => $data['votingEvent']]);
        }

        $elections = $data['elections'];
        $votingEvent = $data['votingEvent'];
        $selections = $request->session()->get('wizard.selections', []);

        // Harus sudah pilih semua
        if (count($selections) < $elections->count()) {
            // Cari step pertama yang belum dipilih
            foreach ($elections as $idx => $el) {
                if (! isset($selections[$el->id])) {
                    return redirect()->route('vote.wizard.step', ['step' => $idx + 1])
                        ->withErrors(['error' => 'Lengkapi pilihan untuk ' . $el->name . ' terlebih dahulu.']);
                }
            }
        }

        // Load candidate details for review
        $reviewData = [];
        foreach ($elections as $el) {
            $candidateId = $selections[$el->id] ?? null;
            $candidate = $candidateId ? Candidate::find($candidateId) : null;
            $reviewData[] = ['election' => $el, 'candidate' => $candidate];
        }

        $voter = Auth::guard('voter')->user();

        return view('voter.wizard.review', [
            'votingEvent' => $votingEvent,
            'elections' => $elections,
            'reviewData' => $reviewData,
            'selections' => $selections,
            'voter' => $voter,
        ]);
    }

    public function submit(Request $request, VotingService $votingService)
    {
        $votingEventId = $request->session()->get('voting_event_id');
        $voterId = Auth::guard('voter')->id();
        $selections = $request->session()->get('wizard.selections', []);

        if (! $votingEventId || ! $voterId || empty($selections)) {
            return redirect()->route('vote.login')->withErrors(['error' => 'Sesi tidak valid, silakan login kembali.']);
        }

        // Validasi semua elections dalam event sudah ada pilihan
        $data = $this->getWizardData($request);
        $elections = $data['elections'];
        if (count($selections) < $elections->count()) {
            return redirect()->route('vote.wizard.review')->withErrors(['error' => 'Lengkapi semua pilihan terlebih dahulu.']);
        }

        try {
            $ballots = $votingService->castVotesBatch(
                votingEventId: $votingEventId,
                voterId: $voterId,
                selections: $selections,
                ipAddress: $request->ip(),
                userAgent: $request->userAgent(),
            );

            // Broadcast per election untuk realtime per organisasi
            foreach ($ballots as $electionId => $ballot) {
                $election = Election::find($electionId);
                $totalEligible = VoterEligibility::where('election_id', $electionId)->count();
                $totalVoted = VoterEligibility::where('election_id', $electionId)->where('status', 'VOTED')->count();
                $participationRate = $totalEligible > 0 ? round(($totalVoted / $totalEligible) * 100, 2) : 0;
                broadcast(new VoteCasted($election, $totalVoted, $totalEligible, $participationRate));
            }

            // Clear wizard & logout (prevent re-vote)
            $hashes = collect($ballots)->map(fn ($b) => $b->ballot_hash)->values()->toArray();
            $request->session()->forget('wizard.selections');
            Auth::guard('voter')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('vote.confirmation', ['hashes' => implode(',', $hashes)]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
