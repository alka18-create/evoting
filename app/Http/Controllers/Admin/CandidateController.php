<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Elections\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CandidateRequest;
use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Support\Facades\Gate;

class CandidateController extends Controller
{
    /**
     * P2-05 (INV-06): kandidat dikunci setelah election dibuka.
     * Perubahan hanya boleh saat DRAFT/SCHEDULED.
     */
    private function ensureEditable(Election $election): ?\Illuminate\Http\RedirectResponse
    {
        if (! in_array($election->status, [ElectionStatus::Draft, ElectionStatus::Scheduled], true)) {
            return back()->withErrors(['error' => 'Kandidat dikunci karena pemilihan sudah dibuka/ditutup.']);
        }

        return null;
    }

    /**
     * IDOR fix: pastikan candidate benar milik election di URL.
     */
    private function ensureSameElection(Election $election, Candidate $candidate): void
    {
        if ((int) $candidate->election_id !== (int) $election->id) {
            abort(404);
        }
    }

    public function index(Election $election)
    {
        Gate::authorize('viewAny', Candidate::class);

        $candidates = $election->candidates()->orderBy('candidate_number')->paginate(20);
        return view('admin.candidates.index', compact('election', 'candidates'));
    }

    public function create(Election $election)
    {
        Gate::authorize('create', Candidate::class);

        if ($redirect = $this->ensureEditable($election)) {
            return $redirect;
        }

        return view('admin.candidates.create', compact('election'));
    }

    public function store(CandidateRequest $request, Election $election)
    {
        Gate::authorize('create', Candidate::class);

        if ($redirect = $this->ensureEditable($election)) {
            return $redirect;
        }

        // P2-04: tipe + dimensi foto divalidasi di CandidateRequest.
        $validated = $request->validated();

        // Check number uniqueness within election
        if ($election->candidates()->where('candidate_number', $validated['candidate_number'])->exists()) {
            return back()->withErrors(['candidate_number' => 'Nomor urut sudah digunakan.']);
        }

        // Handle photo upload
        if (isset($validated['photo'])) {
            $validated['photo_path'] = $validated['photo']->store('candidates', 'public');
            unset($validated['photo']);
        }

        if (isset($validated['running_mate_photo'])) {
            $validated['running_mate_photo_path'] = $validated['running_mate_photo']->store('candidates', 'public');
            unset($validated['running_mate_photo']);
        }

        if (! isset($validated['running_mate_name'])) {
            $validated['running_mate_name'] = null;
            $validated['running_mate_photo_path'] = null;
        }

        $candidate = $election->candidates()->create($validated);

        AuditLogger::log(
            action: 'CANDIDATE_CREATED',
            resourceType: 'Candidate',
            resourceId: $candidate->id,
            metadata: ['election_id' => $election->id, 'name' => $candidate->name, 'number' => $candidate->candidate_number]
        );

        return redirect()->route('admin.elections.candidates.index', $election)
            ->with('success', 'Kandidat berhasil ditambahkan.');
    }

    public function show(Election $election, Candidate $candidate)
    {
        $this->ensureSameElection($election, $candidate);
        Gate::authorize('view', $candidate);

        return view('admin.candidates.show', compact('election', 'candidate'));
    }

    public function edit(Election $election, Candidate $candidate)
    {
        $this->ensureSameElection($election, $candidate);
        Gate::authorize('update', $candidate);

        if ($redirect = $this->ensureEditable($election)) {
            return $redirect;
        }

        return view('admin.candidates.edit', compact('election', 'candidate'));
    }

    public function update(CandidateRequest $request, Election $election, Candidate $candidate)
    {
        $this->ensureSameElection($election, $candidate);
        Gate::authorize('update', $candidate);

        if ($redirect = $this->ensureEditable($election)) {
            return $redirect;
        }

        // P2-04: tipe + dimensi foto divalidasi di CandidateRequest.
        $validated = $request->validated();

        // Check number uniqueness (excluding current candidate)
        if ($election->candidates()
            ->where('candidate_number', $validated['candidate_number'])
            ->where('id', '!=', $candidate->id)
            ->exists()) {
            return back()->withErrors(['candidate_number' => 'Nomor urut sudah digunakan.']);
        }

        // Handle photo upload
        if (isset($validated['photo'])) {
            $validated['photo_path'] = $validated['photo']->store('candidates', 'public');
            unset($validated['photo']);
        }

        if (isset($validated['running_mate_photo'])) {
            $validated['running_mate_photo_path'] = $validated['running_mate_photo']->store('candidates', 'public');
            unset($validated['running_mate_photo']);
        }

        // Nama wakil dikosongkan = kembali jadi kandidat tunggal.
        if (! isset($validated['running_mate_name'])) {
            $validated['running_mate_name'] = null;
            $validated['running_mate_photo_path'] = null;
        }

        $candidate->update($validated);

        AuditLogger::log(
            action: 'CANDIDATE_UPDATED',
            resourceType: 'Candidate',
            resourceId: $candidate->id,
            metadata: ['election_id' => $election->id, 'name' => $candidate->name, 'number' => $candidate->candidate_number]
        );

        return redirect()->route('admin.elections.candidates.index', $election)
            ->with('success', 'Kandidat berhasil diperbarui.');
    }

    public function destroy(Election $election, Candidate $candidate)
    {
        $this->ensureSameElection($election, $candidate);
        Gate::authorize('delete', $candidate);

        if ($redirect = $this->ensureEditable($election)) {
            return $redirect;
        }

        if ($candidate->ballots()->exists()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus kandidat yang sudah memiliki suara.']);
        }

        $candidate->delete();

        AuditLogger::log(
            action: 'CANDIDATE_DELETED',
            resourceType: 'Candidate',
            resourceId: $candidate->id,
            metadata: ['election_id' => $election->id, 'name' => $candidate->name, 'number' => $candidate->candidate_number]
        );

        return redirect()->route('admin.elections.candidates.index', $election)
            ->with('success', 'Kandidat berhasil dihapus.');
    }
}
