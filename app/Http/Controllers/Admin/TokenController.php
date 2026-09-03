<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Support\VotingToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class TokenController extends Controller
{
    public function overview()
    {
        Gate::authorize('viewAny', VoterEligibility::class);

        $elections = Election::selectRaw('
            elections.*,
            (SELECT count(*) FROM voter_eligibilities WHERE election_id = elections.id) as total_tokens,
            (SELECT count(*) FROM voter_eligibilities WHERE election_id = elections.id AND (token_hash IS NOT NULL OR token IS NOT NULL)) as issued_tokens,
            (SELECT count(*) FROM voter_eligibilities WHERE election_id = elections.id AND status = ?) as voted_tokens
        ', ['VOTED'])
            ->latest()
            ->paginate(20);

        return view('admin.tokens.overview', compact('elections'));
    }

    public function index(Election $election)
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

        return view('admin.tokens.index', compact('election', 'eligibilities', 'availableVoters'));
    }

    public function issue(Request $request, Election $election)
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
            $eligibility = VoterEligibility::firstOrCreate([
                'election_id' => $election->id,
                'voter_id' => $voterId,
            ], [
                'status' => 'ELIGIBLE',
            ]);

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
                    $eligibility->update([
                        'token' => null,
                        'token_hash' => $hash,
                        'token_enc' => Crypt::encryptString($plain),
                        'expires_at' => $election->ends_at,
                    ]);
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
            return redirect()->route('admin.elections.tokens.index', $election)
                ->with('success', "{$issued} token berhasil diterbitkan.")
                ->with('issued_tokens', $issuedTokens);
        }

        return redirect()->route('admin.elections.tokens.index', $election)
            ->with('success', 'Tidak ada token baru yang diterbitkan.');
    }

    public function printCard(Election $election, VoterEligibility $eligibility)
    {
        Gate::authorize('viewAny', VoterEligibility::class);

        $eligibility->load('voter');

        if (! $eligibility->hasToken()) {
            return back()->withErrors(['error' => 'Token belum diterbitkan untuk pemilih ini.']);
        }

        $plainToken = $eligibility->plainToken();
        if (! $plainToken) {
            return back()->withErrors(['error' => 'Token hash-only tidak dapat dicetak ulang. Terbitkan ulang token baru.']);
        }

        return view('admin.tokens.print-card', [
            'election' => $election,
            'eligibility' => $eligibility,
            'plainToken' => $plainToken,
        ]);
    }

    public function printCardsBulk(Election $election)
    {
        Gate::authorize('viewAny', VoterEligibility::class);

        $eligibilities = VoterEligibility::where('election_id', $election->id)
            ->whereNotNull('token_hash')
            ->with('voter')
            ->orderBy('id')
            ->get()
            // Hanya yang masih bisa didekripsi (hash+enc); hash-only dilewati
            ->filter(fn ($e) => $e->plainToken() !== null)
            ->values();

        // Fallback transisi: tampilkan juga plain lama yang belum termigrasi
        if ($eligibilities->isEmpty()) {
            $eligibilities = VoterEligibility::where('election_id', $election->id)
                ->whereNotNull('token')
                ->with('voter')
                ->orderBy('id')
                ->get();
        }

        return view('admin.tokens.print-cards', [
            'election' => $election,
            'eligibilities' => $eligibilities,
        ]);
    }

    public function destroy(Election $election, VoterEligibility $eligibility)
    {
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
