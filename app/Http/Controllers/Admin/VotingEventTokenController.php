<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Voting\Services\TokenCardService;
use App\Domain\Voting\Services\VotingEventService;
use App\Http\Controllers\Controller;
use App\Models\Voter;
use App\Models\VotingEvent;
use App\Models\VotingEventVoter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class VotingEventTokenController extends Controller
{
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

    public function issue(Request $request, VotingEvent $votingEvent, VotingEventService $service, TokenCardService $cards)
    {
        Gate::authorize('create', VotingEventVoter::class);

        $request->validate([
            'voter_ids' => 'required|array',
            'voter_ids.*' => 'exists:voters,id',
        ]);

        $issued = $service->generateTokenForVoters($votingEvent, $request->input('voter_ids'));

        if ($issued->isNotEmpty()) {
            return redirect()->route('admin.voting-events.tokens.print-bulk', $votingEvent)
                ->with('success', "{$issued->count()} token berhasil diterbitkan (1 token untuk semua organisasi).")
                ->with('issued_tokens', $issued->toArray())
                ->with('saved_card_pdf', $cards->savePdf($votingEvent, $issued));
        }

        return redirect()->route('admin.voting-events.tokens.index', $votingEvent)
            ->with('success', 'Tidak ada token baru yang diterbitkan (sudah punya token).');
    }

    public function issueAll(VotingEvent $votingEvent, VotingEventService $service, TokenCardService $cards)
    {
        Gate::authorize('create', VotingEventVoter::class);

        $issued = $service->generateTokens($votingEvent);

        if ($issued->isNotEmpty()) {
            // PDF kartu dibuat langsung di sini — token plain ada di tangan,
            // begitu halaman ditutup token tak lagi tersedia di mana pun.
            return redirect()->route('admin.voting-events.tokens.print-bulk', $votingEvent)
                ->with('success', "{$issued->count()} token berhasil diterbitkan.")
                ->with('issued_tokens', $issued->toArray())
                ->with('saved_card_pdf', $cards->savePdf($votingEvent, $issued));
        }

        return back()->with('success', 'Semua voter sudah memiliki token.');
    }

    public function printCard(VotingEvent $votingEvent, VotingEventVoter $votingEventVoter)
    {
        Gate::authorize('viewAny', VotingEventVoter::class);

        if ($votingEventVoter->voting_event_id !== $votingEvent->id) {
            abort(404);
        }

        // Hash-only: tidak ada halaman cetak ulang. Arahkan ke daftar + rotasi.
        return redirect()->route('admin.voting-events.tokens.index', $votingEvent)
            ->withErrors(['error' => 'Token hash-only tidak dapat dicetak ulang. Gunakan Rotasi pada baris siswa untuk menerbitkan token baru (tampil sekali).']);
    }

    public function reissue(VotingEvent $votingEvent, VotingEventVoter $votingEventVoter, VotingEventService $service, TokenCardService $cards)
    {
        Gate::authorize('create', VotingEventVoter::class);

        if ($votingEventVoter->voting_event_id !== $votingEvent->id) {
            abort(404);
        }

        $hasVoted = \App\Models\VoterEligibility::where('voter_id', $votingEventVoter->voter_id)
            ->whereIn('election_id', $votingEvent->elections()->pluck('id'))
            ->where('status', 'VOTED')
            ->exists();

        if ($hasVoted) {
            return back()->withErrors(['error' => 'Tidak dapat merotasi token pemilih yang sudah memberikan suara.']);
        }

        $plain = $service->rotateToken($votingEvent, $votingEventVoter);
        $votingEventVoter->load('voter');

        $issued = collect([[
            'student_id' => $votingEventVoter->voter->student_id,
            'name' => $votingEventVoter->voter->name,
            'class_name' => $votingEventVoter->voter->class_name,
            'token' => $plain,
        ]]);

        return redirect()->route('admin.voting-events.tokens.print-bulk', $votingEvent)
            ->with('success', 'Token baru diterbitkan (token lama tidak berlaku).')
            ->with('issued_tokens', $issued->toArray())
            ->with('saved_card_pdf', $cards->savePdf($votingEvent, $issued));
    }

    /**
     * Unduh PDF kartu tersimpan (disk private — tidak bisa diakses via URL).
     */
    public function downloadCardPdf(VotingEvent $votingEvent, string $file, TokenCardService $cards)
    {
        Gate::authorize('viewAny', VotingEventVoter::class);

        $relative = $cards->pathPdf($votingEvent, $file);

        if ($relative === null) {
            abort(404);
        }

        return Storage::disk('local')->download($relative, $file, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /**
     * Hapus PDF kartu (mis. setelah pemilihan selesai — berisi token plain).
     */
    public function destroyCardPdf(VotingEvent $votingEvent, string $file, TokenCardService $cards)
    {
        Gate::authorize('manageCards', VotingEventVoter::class);

        if ($cards->pathPdf($votingEvent, $file) === null) {
            abort(404);
        }

        $cards->deletePdf($votingEvent, $file);

        return back()->with('success', 'PDF kartu dihapus.');
    }

    public function printCardsBulk(VotingEvent $votingEvent, TokenCardService $cards)
    {
        Gate::authorize('viewAny', VotingEventVoter::class);

        // Hash-only: bulk reprint dari DB tidak mungkin.
        $eventVoters = VotingEventVoter::where('voting_event_id', $votingEvent->id)
            ->whereNotNull('token_hash')
            ->with('voter')
            ->orderBy('id')
            ->get();

        $votingEvent->load('elections.organization');

        return view('admin.voting-events.tokens.print-cards', [
            'votingEvent' => $votingEvent,
            'eventVoters' => $eventVoters,
            'savedPdfs' => $cards->listPdf($votingEvent),
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
