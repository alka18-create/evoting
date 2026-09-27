<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Voting\Services\VotingEventService;
use App\Http\Controllers\Controller;
use App\Models\VoterEligibility;
use App\Models\VotingEvent;
use App\Models\Voter;
use App\Support\VotingToken;
use Illuminate\Http\Request;

class ScanController extends Controller
{
    public function show()
    {
        \Illuminate\Support\Facades\Gate::authorize('verifyVoter');

        return view('admin.backups.scan');
    }

    public function verify(Request $request)
    {
        \Illuminate\Support\Facades\Gate::authorize('verifyVoter');
        // P1-01: student_id opsional untuk QR v2 opaque (resolve by token dalam scope event/election).
        $request->validate([
            'student_id' => 'nullable|string',
            'token' => 'required|string|min:6|max:16',
            'election_id' => 'nullable|integer|exists:elections,id',
            'voting_event_id' => 'nullable|integer|exists:voting_events,id',
        ]);

        if (! $request->election_id && ! $request->voting_event_id) {
            return back()->withErrors(['error' => 'QR tidak mengandung election_id atau voting_event_id.']);
        }

        $voter = null;
        if ($request->filled('student_id')) {
            $voter = Voter::where('student_id', $request->student_id)->first();

            if (! $voter) {
                return back()->withErrors(['error' => 'Pemilih dengan NIS "' . $request->student_id . '" tidak ditemukan.']);
            }
        }

        // New flow: voting_event_id (1 token untuk semua organisasi)
        if ($request->voting_event_id) {
            $eventVoter = null;
            if ($voter) {
                $eventVoter = VotingEventService::resolveByToken(
                    (int) $request->voting_event_id,
                    (int) $voter->id,
                    (string) $request->token
                );
            } else {
                // P1-01 opaque QR v2: cari pemilik token dalam event ini saja.
                $eventVoter = \App\Models\VotingEventVoter::where('voting_event_id', $request->voting_event_id)
                    ->where('token_hash', VotingToken::hash((string) $request->token))
                    ->with('voter')
                    ->first();
                $voter = $eventVoter?->voter;
            }

            if (! $eventVoter || ! $voter) {
                // Jangan log token plain (Threat Model §39)
                AuditLogger::log(action: 'SCAN_FAILED', resourceType: 'VotingEventVoter', resourceId: 0, metadata: ['voting_event_id' => $request->voting_event_id, 'reason' => 'Token tidak valid']);

                return back()->withErrors(['error' => 'Token tidak valid untuk event ini.']);
            }

            if ($eventVoter->isExpired()) {
                AuditLogger::log(action: 'SCAN_FAILED', resourceType: 'VotingEventVoter', resourceId: $eventVoter->id, metadata: ['student_id' => $request->student_id, 'voting_event_id' => $request->voting_event_id, 'reason' => 'Token expired']);

                return back()->withErrors(['error' => 'Token sudah kedaluwarsa. Terbitkan ulang token baru.']);
            }

            $votingEvent = VotingEvent::with(['elections.organization'])->find($request->voting_event_id);
            $electionIds = $votingEvent->elections->pluck('id');

            $eligibilities = VoterEligibility::whereIn('election_id', $electionIds)->where('voter_id', $voter->id)->with('election')->get();

            $votedCount = $eligibilities->where('status', 'VOTED')->count();
            $total = $eligibilities->count();

            if ($total > 0 && $votedCount === $total) {
                return back()->with('warning', 'Pemilih "' . $voter->name . '" sudah memberikan suara untuk semua pemilihan dalam event "' . $votingEvent->name . '".');
            }

            $statusList = $eligibilities->map(fn ($e) => $e->election->name . ': ' . $e->status)->implode(', ');

            AuditLogger::log(action: 'SCAN_SUCCESS', resourceType: 'VotingEventVoter', resourceId: $eventVoter->id, metadata: ['student_id' => $request->student_id, 'voting_event_id' => $request->voting_event_id, 'voter_name' => $voter->name, 'status' => $statusList]);

            $pending = $total - $votedCount;

            return back()->with('success', 'Verifikasi berhasil! Pemilih "' . $voter->name . '" terdaftar di event "' . $votingEvent->name . '" — ' . $pending . '/' . $total . ' pemilihan belum dipilih. Status: ' . $statusList);
        }

        // Legacy flow: election_id (hash-only, P0).
        $hash = VotingToken::hash((string) $request->token);
        $eligibility = null;
        if ($voter) {
            $eligibility = VoterEligibility::where('election_id', $request->election_id)
                ->where('voter_id', $voter->id)
                ->where('token_hash', $hash)
                ->first();
        } else {
            // Opaque v2: resolve pemilik token dalam election ini.
            $eligibility = VoterEligibility::where('election_id', $request->election_id)
                ->where('token_hash', $hash)
                ->with('voter')
                ->first();
            $voter = $eligibility?->voter;
        }

        if (! $eligibility || ! $voter) {
            AuditLogger::log(
                action: 'SCAN_FAILED',
                resourceType: 'VoterEligibility',
                resourceId: 0,
                metadata: [
                    'election_id' => $request->election_id,
                    'reason' => 'Token tidak valid atau tidak cocok',
                ]
            );

            return back()->withErrors(['error' => 'Token tidak valid untuk pemilihan ini.']);
        }

        if ($eligibility->isExpired()) {
            return back()->withErrors(['error' => 'Token sudah kedaluwarsa. Terbitkan ulang token baru.']);
        }

        if ($eligibility->hasVoted()) {
            return back()->with('warning', 'Pemilih ini sudah memberikan suara.');
        }

        AuditLogger::log(
            action: 'SCAN_SUCCESS',
            resourceType: 'VoterEligibility',
            resourceId: $eligibility->id,
            metadata: [
                'student_id' => $request->student_id,
                'election_id' => $request->election_id,
                'voter_name' => $voter->name,
            ]
        );

        return back()->with('success', 'Verifikasi berhasil! Pemilih "' . $voter->name . '" terdaftar dan siap memberikan suara.');
    }
}
