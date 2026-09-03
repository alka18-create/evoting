<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Voting\Services\VotingEventService;
use App\Http\Controllers\Controller;
use App\Models\Voter;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class VotingEventTokenController extends Controller
{
    public function overview()
    {
        Gate::authorize('viewAny', VotingEventVoter::class);

        $events = VotingEvent::selectRaw('
            voting_events.*,
            (SELECT count(*) FROM voting_event_voters WHERE voting_event_id = voting_events.id) as total_voters,
            (SELECT count(*) FROM voting_event_voters WHERE voting_event_id = voting_events.id AND (token_hash IS NOT NULL OR token IS NOT NULL)) as issued_tokens
        ')
            ->latest()
            ->paginate(20);

        return view('admin.voting-events.tokens.overview', compact('events'));
    }

    public function index(VotingEvent $votingEvent)
    {
        Gate::authorize('viewAny', VotingEventVoter::class);

        $votingEvent->loadCount(['elections', 'votingEventVoters']);

        $eventVoters = VotingEventVoter::where('voting_event_id', $votingEvent->id)
            ->with('voter')
            ->latest()
            ->paginate(20);

        $availableVoters = Voter::where('is_active', true)
            ->whereDoesntHave('votingEventVoters', fn ($q) => $q->where('voting_event_id', $votingEvent->id))
            ->orderBy('name')
            ->get();

        return view('admin.voting-events.tokens.index', compact('votingEvent', 'eventVoters', 'availableVoters'));
    }

    public function assignAll(VotingEvent $votingEvent, VotingEventService $service)
    {
        Gate::authorize('create', VotingEventVoter::class);

        $result = $service->assignAllActiveVoters($votingEvent);

        return back()->with('success', "Berhasil assign: {$result['event_voters_created']} event voters, {$result['eligibilities_created']} eligibilities dibuat.");
    }

    public function issue(Request $request, VotingEvent $votingEvent, VotingEventService $service)
    {
        Gate::authorize('create', VotingEventVoter::class);

        $request->validate([
            'voter_ids' => 'required|array',
            'voter_ids.*' => 'exists:voters,id',
        ]);

        $issued = $service->generateTokenForVoters($votingEvent, $request->input('voter_ids'));

        if ($issued->isNotEmpty()) {
            return redirect()->route('admin.voting-events.tokens.index', $votingEvent)
                ->with('success', "{$issued->count()} token berhasil diterbitkan (1 token untuk semua organisasi).")
                ->with('issued_tokens', $issued->toArray());
        }

        return redirect()->route('admin.voting-events.tokens.index', $votingEvent)
            ->with('success', 'Tidak ada token baru yang diterbitkan (sudah punya token).');
    }

    public function issueAll(VotingEvent $votingEvent, VotingEventService $service)
    {
        Gate::authorize('create', VotingEventVoter::class);

        $issued = $service->generateTokens($votingEvent);

        if ($issued->isNotEmpty()) {
            return redirect()->route('admin.voting-events.tokens.index', $votingEvent)
                ->with('success', "{$issued->count()} token berhasil diterbitkan.")
                ->with('issued_tokens', $issued->toArray());
        }

        return back()->with('success', 'Semua voter sudah memiliki token.');
    }

    public function printCard(VotingEvent $votingEvent, VotingEventVoter $votingEventVoter)
    {
        Gate::authorize('viewAny', VotingEventVoter::class);

        if ($votingEventVoter->voting_event_id !== $votingEvent->id) {
            abort(404);
        }

        $votingEventVoter->load('voter');
        $votingEvent->load('elections.organization');

        if (! $votingEventVoter->hasToken()) {
            return back()->withErrors(['error' => 'Token belum diterbitkan untuk pemilih ini.']);
        }

        $plainToken = $votingEventVoter->plainToken();
        if (! $plainToken) {
            return back()->withErrors(['error' => 'Token hash-only tidak dapat dicetak ulang. Terbitkan ulang token baru.']);
        }

        return view('admin.voting-events.tokens.print-card', [
            'votingEvent' => $votingEvent,
            'eventVoter' => $votingEventVoter,
            'plainToken' => $plainToken,
        ]);
    }

    public function printCardsBulk(VotingEvent $votingEvent)
    {
        Gate::authorize('viewAny', VotingEventVoter::class);

        $eventVoters = VotingEventVoter::where('voting_event_id', $votingEvent->id)
            ->where(function ($q) {
                $q->whereNotNull('token_hash')->orWhereNotNull('token');
            })
            ->with('voter')
            ->orderBy('id')
            ->get()
            ->filter(fn ($e) => $e->plainToken() !== null)
            ->values();

        $votingEvent->load('elections.organization');

        return view('admin.voting-events.tokens.print-cards', [
            'votingEvent' => $votingEvent,
            'eventVoters' => $eventVoters,
        ]);
    }

    public function destroy(VotingEvent $votingEvent, VotingEventVoter $votingEventVoter)
    {
        Gate::authorize('delete', $votingEventVoter);

        if ($votingEventVoter->voting_event_id !== $votingEvent->id) {
            abort(404);
        }

        // Cek apakah voter sudah vote di salah satu election dalam event
        $hasVoted = \App\Models\VoterEligibility::where('voter_id', $votingEventVoter->voter_id)
            ->whereIn('election_id', $votingEvent->elections()->pluck('id'))
            ->where('status', 'VOTED')
            ->exists();

        if ($hasVoted) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus token pemilih yang sudah memberikan suara di salah satu pemilihan.']);
        }

        $votingEventVoter->delete();

        return back()->with('success', 'Token berhasil dihapus.');
    }
}
