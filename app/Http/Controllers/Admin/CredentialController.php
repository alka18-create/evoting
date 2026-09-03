<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Credential;
use App\Models\Election;
use App\Models\Voter;
use App\Models\VoterEligibility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CredentialController extends Controller
{
    public function index(Election $election)
    {
        Gate::authorize('viewAny', Credential::class);

        $credentials = Credential::whereHas('eligibility', fn($q) => $q->where('election_id', $election->id))
            ->with('eligibility.voter')
            ->latest()
            ->paginate(20);

        $availableVoters = Voter::where('is_active', true)
            ->whereDoesntHave('eligibilities', fn($q) => $q->where('election_id', $election->id))
            ->orderBy('name')
            ->get();

        return view('admin.credentials.index', compact('election', 'credentials', 'availableVoters'));
    }

    public function issue(Request $request, Election $election)
    {
        Gate::authorize('issue', Credential::class);

        $request->validate([
            'voter_ids' => 'required|array',
            'voter_ids.*' => 'exists:voters,id',
        ]);

        $voterIds = $request->input('voter_ids');
        $issued = 0;
        $credentials = [];

        foreach ($voterIds as $voterId) {
            $eligibility = VoterEligibility::firstOrCreate([
                'election_id' => $election->id,
                'voter_id' => $voterId,
            ], [
                'status' => 'ELIGIBLE',
            ]);

            // Skip if already has credential
            if ($eligibility->credential) {
                continue;
            }

            // Generate 6-digit numeric token
            $plainCredential = (string) random_int(100000, 999999);

            $credential = Credential::create([
                'voter_eligibility_id' => $eligibility->id,
                'credential_hash' => Hash::make($plainCredential),
                'expires_at' => $election->ends_at,
            ]);

            $voter = $eligibility->voter;
            $credentials[] = [
                'name' => $voter->name,
                'student_id' => $voter->student_id,
                'credential' => $plainCredential,
            ];

            AuditLogger::log(
                action: 'CREDENTIAL_ISSUED',
                resourceType: 'Credential',
                resourceId: $credential->id,
                metadata: ['election_id' => $election->id, 'voter_id' => $voterId]
            );

            $issued++;
        }

        if ($issued > 0) {
            return redirect()->route('admin.elections.credentials.index', $election)
                ->with('success', "{$issued} credential berhasil diterbitkan.")
                ->with('issued_credentials', $credentials);
        }

        return redirect()->route('admin.elections.credentials.index', $election)
            ->with('success', 'Tidak ada credential baru yang diterbitkan.');
    }

    public function revoke(Credential $credential)
    {
        Gate::authorize('revoke', $credential);

        $credential->update(['revoked_at' => now()]);

        AuditLogger::log(
            action: 'CREDENTIAL_REVOKED',
            resourceType: 'Credential',
            resourceId: $credential->id
        );

        return back()->with('success', 'Credential berhasil dicabut.');
    }

    public function export(Election $election)
    {
        Gate::authorize('viewAny', Credential::class);

        $credentials = Credential::whereHas('eligibility', fn($q) => $q->where('election_id', $election->id))
            ->with('eligibility.voter')
            ->get();

        $filename = "credentials_{$election->id}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($credentials) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['student_id', 'name', 'class_name', 'credential_hash', 'status', 'issued_at', 'expires_at']);

            foreach ($credentials as $credential) {
                fputcsv($handle, [
                    $credential->eligibility->voter->student_id ?? '',
                    $credential->eligibility->voter->name ?? '',
                    $credential->eligibility->voter->class_name ?? '',
                    $credential->credential_hash,
                    $credential->revoked_at ? 'REVOKED' : 'ACTIVE',
                    $credential->created_at->format('Y-m-d H:i:s'),
                    $credential->expires_at?->format('Y-m-d H:i:s') ?? '',
                ]);
            }

            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }
}
