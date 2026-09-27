<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Voting\Services\TokenCardService;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Support\VotingToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class TokenController extends Controller
{
    public function overview()
    {
        Gate::authorize('viewAny', VoterEligibility::class);

        $elections = Election::selectRaw('
            elections.*,
            (SELECT count(*) FROM voter_eligibilities WHERE election_id = elections.id) as total_tokens,
            (SELECT count(*) FROM voter_eligibilities WHERE election_id = elections.id AND token_hash IS NOT NULL) as issued_tokens,
            (SELECT count(*) FROM voter_eligibilities WHERE election_id = elections.id AND status = ?) as voted_tokens
        ', ['VOTED'])
            ->latest()
            ->paginate(20);

        return view('admin.tokens.overview', compact('elections'));
    }

    public function index(Election $election, TokenCardService $cards)
    {
        Gate::authorize('viewAny', VoterEligibility::class);

        $eligibilities = VoterEligibility::where('election_id', $election->id)
            ->with('voter')
            ->latest()
            ->paginate(20);

        $availableVoters = Voter::where('is_active', true)
            ->whereDoesntHave('eligibilities', fn($q) => $q->where('election_id', $election->id))
            ->orderBy('name')
            ->get();

        $savedPdfs = $cards->listPdfFor($this->scope($election));

        return view('admin.tokens.index', compact('election', 'eligibilities', 'availableVoters', 'savedPdfs'));
    }

    public function issue(Request $request, Election $election, TokenCardService $cards)
    {
        Gate::authorize('create', VoterEligibility::class);

        $request->validate([
            'voter_ids' => 'required|array',
            'voter_ids.*' => 'exists:voters,id',
        ]);

        $voterIds = $request->input('voter_ids');
        $issued = 0;
        $issuedTokens = [];

        foreach ($voterIds as $voterId) {
            $eligibility = VoterEligibility::firstOrNew([
                'election_id' => $election->id,
                'voter_id' => $voterId,
            ]);
            if (! $eligibility->exists) {
                $eligibility->forceFill(['status' => 'ELIGIBLE'])->save();
            } else {
                $eligibility->refresh();
            }

            // Skip if already has token (hash preferred, fallback plain transisi)
            if ($eligibility->hasToken()) {
                continue;
            }

            // P1-04: retry hingga hash unik dalam election ini (max 10x),
            // tangani juga race duplicate dari unique index.
            $token = null;
            for ($attempt = 0; $attempt < 10; $attempt++) {
                $plain = VotingToken::generate();
                $hash = VotingToken::hash($plain);

                $exists = VoterEligibility::where('election_id', $election->id)
                    ->where('token_hash', $hash)
                    ->exists();
                if ($exists) {
                    continue;
                }

                try {
                    $eligibility->forceFill([
                        'token_hash' => $hash,
                        'expires_at' => $election->ends_at,
                    ])->save();
                    $token = $plain;
                    break;
                } catch (\Illuminate\Database\QueryException $e) {
                    // 23505 = unique violation (Postgres). Coba token lain.
                    if (($e->errorInfo[0] ?? null) !== '23505') {
                        throw $e;
                    }
                }
            }

            if ($token === null) {
                continue;
            }

            $voter = $eligibility->voter;
            $issuedTokens[] = [
                'name' => $voter->name,
                'student_id' => $voter->student_id,
                'class_name' => $voter->class_name,
                'token' => $token,
            ];

            AuditLogger::log(
                action: 'TOKEN_ISSUED',
                resourceType: 'VoterEligibility',
                resourceId: $eligibility->id,
                metadata: ['election_id' => $election->id, 'voter_id' => $voterId]
            );

            $issued++;
        }

        if ($issued > 0) {
            $saved = $cards->savePdfFor(
                $this->scope($election),
                ['el' => $election->id],
                collect($issuedTokens),
                'admin.tokens.pdf.cards',
                ['election' => $election],
            );

            return redirect()->route('admin.elections.tokens.index', $election)
                ->with('success', "{$issued} token berhasil diterbitkan.")
                ->with('issued_tokens', $issuedTokens)
                ->with('saved_card_pdf', $saved);
        }

        return redirect()->route('admin.elections.tokens.index', $election)
            ->with('success', 'Tidak ada token baru yang diterbitkan.');
    }

    public function printCard(Election $election, VoterEligibility $eligibility)
    {
        Gate::authorize('viewAny', VoterEligibility::class);

        if ((int) $eligibility->election_id !== (int) $election->id) {
            abort(404);
        }

        // Hash-only: tidak ada halaman cetak ulang. Arahkan ke daftar + rotasi.
        return redirect()->route('admin.elections.tokens.index', $election)
            ->withErrors(['error' => 'Token hash-only tidak dapat dicetak ulang. Gunakan Rotasi pada baris pemilih untuk menerbitkan token baru (tampil sekali).']);
    }

    public function reissue(Request $request, Election $election, VoterEligibility $eligibility, TokenCardService $cards)
    {
        Gate::authorize('create', VoterEligibility::class);

        if ((int) $eligibility->election_id !== (int) $election->id) {
            abort(404);
        }

        if ($eligibility->hasVoted()) {
            return back()->withErrors(['error' => 'Tidak dapat merotasi token pemilih yang sudah memberikan suara.']);
        }

        $plain = VotingToken::generate();
        $hash = VotingToken::hash($plain);

        for ($attempt = 0; $attempt < 10; $attempt++) {
            if (VoterEligibility::where('election_id', $election->id)->where('token_hash', $hash)->exists()) {
                $plain = VotingToken::generate();
                $hash = VotingToken::hash($plain);

                continue;
            }

            try {
                $eligibility->forceFill(['token_hash' => $hash, 'expires_at' => $election->ends_at])->save();

                break;
            } catch (\Illuminate\Database\QueryException $e) {
                if (($e->errorInfo[0] ?? null) !== '23505') {
                    throw $e;
                }
                $plain = VotingToken::generate();
                $hash = VotingToken::hash($plain);
            }
        }

        AuditLogger::log(
            action: 'TOKEN_ROTATED',
            resourceType: 'VoterEligibility',
            resourceId: $eligibility->id,
            metadata: ['election_id' => $election->id, 'voter_id' => $eligibility->voter_id]
        );

        $eligibility->load('voter');

        $saved = $cards->savePdfFor(
            $this->scope($election),
            ['el' => $election->id],
            collect([[
                'name' => $eligibility->voter->name,
                'student_id' => $eligibility->voter->student_id,
                'class_name' => $eligibility->voter->class_name,
                'token' => $plain,
            ]]),
            'admin.tokens.pdf.cards',
            ['election' => $election],
        );

        return redirect()->route('admin.elections.tokens.index', $election)
            ->with('success', 'Token baru diterbitkan (token lama tidak berlaku).')
            ->with('issued_tokens', [[
                'name' => $eligibility->voter->name,
                'student_id' => $eligibility->voter->student_id,
                'class_name' => $eligibility->voter->class_name,
                'token' => $plain,
            ]])
            ->with('saved_card_pdf', $saved);
    }

    public function printCardsBulk(Election $election, TokenCardService $cards)
    {
        Gate::authorize('viewAny', VoterEligibility::class);

        // Hash-only: bulk reprint dari DB tidak mungkin. Tampilkan daftar
        // status tanpa plaintext; token baru hanya via issue/rotasi.
        $eligibilities = VoterEligibility::where('election_id', $election->id)
            ->whereNotNull('token_hash')
            ->with('voter')
            ->orderBy('id')
            ->get();

        return view('admin.tokens.print-cards', [
            'election' => $election,
            'eligibilities' => $eligibilities,
            'savedPdfs' => $cards->listPdfFor($this->scope($election)),
        ]);
    }

    public function downloadCardPdf(Election $election, string $file, TokenCardService $cards)
    {
        Gate::authorize('viewAny', VoterEligibility::class);

        $relative = $cards->pathPdfFor($this->scope($election), $file);

        abort_unless($relative !== null, 404);

        return Storage::disk('local')->download($relative, $file, [
            'Content-Type' => 'application/pdf',
        ]);
    }

    public function destroyCardPdf(Election $election, string $file, TokenCardService $cards)
    {
        Gate::authorize('manageCards', VoterEligibility::class);

        abort_unless($cards->pathPdfFor($this->scope($election), $file) !== null, 404);

        $cards->deletePdfFor($this->scope($election), $file);

        return back()->with('success', 'PDF kartu dihapus.');
    }

    /** Kunci direktori PDF — prefiks "el-" agar tidak bentrok dengan ID VotingEvent. */
    private function scope(Election $election): string
    {
        return 'el-' . $election->id;
    }

    public function destroy(Election $election, VoterEligibility $eligibility)
    {
        if ((int) $eligibility->election_id !== (int) $election->id) {
            abort(404);
        }

        Gate::authorize('delete', $eligibility);

        if ($eligibility->hasVoted()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus token pemilih yang sudah memberikan suara.']);
        }

        AuditLogger::log(
            action: 'TOKEN_REVOKED',
            resourceType: 'VoterEligibility',
            resourceId: $eligibility->id,
            metadata: ['election_id' => $election->id, 'voter_id' => $eligibility->voter_id]
        );

        $eligibility->delete();

        return back()->with('success', 'Token berhasil dihapus.');
    }
}
