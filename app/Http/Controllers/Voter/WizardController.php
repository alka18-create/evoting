<?php

namespace App\Http\Controllers\Voter;

use App\Domain\Voting\Services\VotingGate;
use App\Domain\Voting\Services\VotingService;
use App\Events\VoteCasted;
use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WizardController extends Controller
{
    /**
     * P0: re-validasi sesi per-request — token yang dicabut/expired dan
     * voter yang dinonaktifkan di tengah sesi langsung dikembalikan ke login.
     * (Provider sudah memfilter is_active, sehingga $voter null = nonaktif.)
     *
     * @return array{voted: bool, votingEvent: \App\Models\VotingEvent, elections: \Illuminate\Support\Collection}|RedirectResponse
     */
    private function getWizardData(Request $request): array|RedirectResponse
    {
        $votingEventId = $request->session()->get('voting_event_id');
        $eventVoterId = $request->session()->get('voting_event_voter_id');
        /** @var \App\Models\Voter|null $voter */
        $voter = Auth::guard('voter')->user();

        $logoutAndLogin = function (string $message) use ($request): RedirectResponse {
            Auth::guard('voter')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('vote.login')
                ->withErrors(['error' => $message]);
        };

        if (! $votingEventId || ! $eventVoterId || ! $voter) {
            return $logoutAndLogin('Sesi tidak valid, silakan login kembali.');
        }

        $eventVoter = VotingEventVoter::find($eventVoterId);

        if (! $eventVoter
            || (int) $eventVoter->voting_event_id !== (int) $votingEventId
            || (int) $eventVoter->voter_id !== (int) $voter->getKey()
            || $eventVoter->isExpired()
        ) {
            return $logoutAndLogin('Sesi tidak valid, silakan login kembali.');
        }

        $votingEvent = VotingEvent::with(['elections.organization'])->findOrFail($votingEventId);

        // P0: event yang ditutup di tengah sesi menolak langkah berikutnya.
        try {
            VotingGate::assertEventOpen($votingEvent);
        } catch (\Exception) {
            return $logoutAndLogin('Event pemilihan sudah ditutup.');
        }

        // P2-01: 1 query eligibilitas untuk semua election (hindari N+1).
        $allElections = $votingEvent->elections->sortBy([['organization_id', 'asc'], ['id', 'asc']])->values();
        $electionIds = $allElections->pluck('id')->all();

        $statusByElection = $electionIds === []
            ? collect()
            : VoterEligibility::whereIn('election_id', $electionIds)
                ->where('voter_id', $voter->getKey())
                ->pluck('status', 'election_id');

        // Elections dalam event yang masih ELIGIBLE (skip yang sudah VOTED legacy)
        $elections = $allElections
            ->filter(fn (Election $e) => ($statusByElection[$e->id] ?? null) === 'ELIGIBLE')
            ->values();

        // Peta status votability per pemilihan: pemilih yang menunggu diberi
        // alasan jelas ("belum dimulai" / "sudah berakhir"), bukan error senyap.
        $votability = $elections
            ->mapWithKeys(fn (Election $e) => [$e->id => VotingGate::electionVotability($e)])
            ->all();

        // Jika semua sudah VOTED, anggap selesai
        if ($elections->isEmpty()) {
            $hasAnyVoted = $statusByElection->contains('VOTED');

            if ($hasAnyVoted) {
                return ['voted' => true, 'votingEvent' => $votingEvent, 'elections' => collect(), 'votability' => []];
            }
        }

        return ['voted' => false, 'votingEvent' => $votingEvent, 'elections' => $elections, 'votability' => $votability];
    }

    public function step(Request $request, int $step)
    {
        $data = $this->getWizardData($request);
        if ($data instanceof RedirectResponse) {
            return $data;
        }

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

        // Pemilihan yang belum berjalan / sudah berakhir ditampilkan dengan
        // penjelasan, bukan form pilih yang pasti gagal saat submit.
        $votability = $data['votability'][$election->id] ?? 'VOTABLE';

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
            'votability' => $votability,
        ]);
    }

    public function storeStep(Request $request, int $step)
    {
        $request->validate([
            'candidate_id' => 'required|integer|exists:candidates,id',
        ]);

        $data = $this->getWizardData($request);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        $elections = $data['elections'];

        if ($step < 1 || $step > $elections->count()) {
            return redirect()->route('vote.wizard.step', ['step' => 1]);
        }

        $election = $elections[$step - 1];

        // Tolak submit untuk pemilihan yang belum berjalan / sudah berakhir.
        $votability = $data['votability'][$election->id] ?? 'VOTABLE';
        if ($votability !== 'VOTABLE') {
            $reasons = [
                'NOT_OPEN' => 'Pemilihan ini belum dibuka panitia.',
                'NOT_STARTED' => 'Pemilihan ini belum dimulai.',
                'ENDED' => 'Pemilihan ini sudah berakhir.',
            ];

            return back()->withErrors(['error' => $reasons[$votability] ?? 'Pemilihan ini belum bisa dipilih.']);
        }

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
        if ($data instanceof RedirectResponse) {
            return $data;
        }

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
        // (getWizardData juga me-revalidasi token/expiry/is_active/event-open)
        $data = $this->getWizardData($request);
        if ($data instanceof RedirectResponse) {
            return $data;
        }
        $elections = $data['elections'];
        if (count($selections) < $elections->count()) {
            return redirect()->route('vote.wizard.review')->withErrors(['error' => 'Lengkapi semua pilihan terlebih dahulu.']);
        }

        try {
            $ballots = $votingService->castVotesBatch(
                votingEventId: $votingEventId,
                voterId: (int) $voterId,
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

            $receipt = Str::random(32);
            Cache::put('vote-receipt:' . $receipt, $hashes, now()->addMinutes(5));

            return redirect()->route('vote.confirmation', ['receipt' => $receipt]);

        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
