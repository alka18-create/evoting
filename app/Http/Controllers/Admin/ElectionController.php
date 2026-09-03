<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Elections\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ElectionController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Election::class);

        $query = Election::with(['votingEvent', 'organization'])->latest();

        if ($request->filled('voting_event_id')) {
            $query->where('voting_event_id', $request->voting_event_id);
        }
        if ($request->filled('organization_id')) {
            $query->where('organization_id', $request->organization_id);
        }
        if ($request->filled('q')) {
            $query->where('name', 'ILIKE', '%' . $request->q . '%');
        }

        $elections = $query->paginate(20)->withQueryString();
        $votingEvents = \App\Models\VotingEvent::orderBy('name')->get();
        $organizations = \App\Models\Organization::orderBy('name')->get();

        return view('admin.elections.index', compact('elections', 'votingEvents', 'organizations'));
    }

    public function create(Request $request)
    {
        Gate::authorize('create', Election::class);

        $votingEvents = \App\Models\VotingEvent::orderBy('name')->get();
        $organizations = \App\Models\Organization::orderBy('name')->get();
        $preselectedEventId = $request->query('voting_event_id');

        return view('admin.elections.create', compact('votingEvents', 'organizations', 'preselectedEventId'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Election::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'voting_event_id' => 'nullable|exists:voting_events,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'starts_at' => 'nullable|date|after:now',
            'ends_at' => 'nullable|date|after:starts_at',
            'settings' => 'nullable|array',
        ]);

        $validated['created_by'] = Auth::id();

        $election = Election::create($validated);

        AuditLogger::log(
            action: 'ELECTION_CREATED',
            resourceType: 'Election',
            resourceId: $election->id,
            metadata: ['name' => $election->name]
        );

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan berhasil dibuat.');
    }

    public function show(Election $election)
    {
        Gate::authorize('view', $election);

        $election->load(['votingEvent', 'organization']);

        return view('admin.elections.show', compact('election'));
    }

    public function edit(Election $election)
    {
        Gate::authorize('update', $election);

        $votingEvents = \App\Models\VotingEvent::orderBy('name')->get();
        $organizations = \App\Models\Organization::orderBy('name')->get();

        return view('admin.elections.edit', compact('election', 'votingEvents', 'organizations'));
    }

    public function update(Request $request, Election $election)
    {
        Gate::authorize('update', $election);

        if (!in_array($election->status, [ElectionStatus::Draft, ElectionStatus::Scheduled])) {
            return back()->withErrors(['error' => 'Hanya pemilihan DRAFT atau SCHEDULED yang bisa diedit.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'voting_event_id' => 'nullable|exists:voting_events,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date|after:starts_at',
            'settings' => 'nullable|array',
        ]);

        $election->update($validated);

        AuditLogger::log(
            action: 'ELECTION_UPDATED',
            resourceType: 'Election',
            resourceId: $election->id,
            metadata: ['name' => $election->name]
        );

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan berhasil diperbarui.');
    }

    public function destroy(Election $election)
    {
        Gate::authorize('delete', $election);

        if ($election->status !== ElectionStatus::Draft) {
            return back()->withErrors(['error' => 'Hanya pemilihan DRAFT yang bisa dihapus.']);
        }

        if ($election->ballots()->exists() || $election->eligibilities()->exists()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus pemilihan yang sudah memiliki data voter atau suara.']);
        }

        $election->delete();

        AuditLogger::log(
            action: 'ELECTION_DELETED',
            resourceType: 'Election',
            resourceId: $election->id,
            metadata: ['name' => $election->name]
        );

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan berhasil dihapus.');
    }

    public function schedule(Election $election)
    {
        Gate::authorize('update', $election);

        if ($election->status !== ElectionStatus::Draft) {
            return back()->withErrors(['error' => 'Transisi tidak valid.']);
        }

        $election->update(['status' => ElectionStatus::Scheduled]);

        AuditLogger::log(
            action: 'ELECTION_SCHEDULED',
            resourceType: 'Election',
            resourceId: $election->id,
            metadata: ['name' => $election->name]
        );

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan dijadwalkan.');
    }

    public function open(Election $election)
    {
        Gate::authorize('open', $election);

        if ($election->status !== ElectionStatus::Scheduled) {
            return back()->withErrors(['error' => 'Transisi tidak valid.']);
        }

        $election->update([
            'status' => ElectionStatus::Open,
            'opened_at' => now(),
        ]);

        AuditLogger::log(
            action: 'ELECTION_OPENED',
            resourceType: 'Election',
            resourceId: $election->id,
            metadata: ['name' => $election->name]
        );

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan dibuka untuk voting.');
    }

    public function close(Election $election)
    {
        Gate::authorize('close', $election);

        if ($election->status !== ElectionStatus::Open) {
            return back()->withErrors(['error' => 'Transisi tidak valid.']);
        }

        $election->update([
            'status' => ElectionStatus::Closed,
            'closed_at' => now(),
        ]);

        AuditLogger::log(
            action: 'ELECTION_CLOSED',
            resourceType: 'Election',
            resourceId: $election->id,
            metadata: ['name' => $election->name]
        );

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan ditutup.');
    }

    public function archive(Election $election)
    {
        Gate::authorize('archive', $election);

        if ($election->status !== ElectionStatus::Closed) {
            return back()->withErrors(['error' => 'Transisi tidak valid.']);
        }

        $election->update(['status' => ElectionStatus::Archived]);

        AuditLogger::log(
            action: 'ELECTION_ARCHIVED',
            resourceType: 'Election',
            resourceId: $election->id,
            metadata: ['name' => $election->name]
        );

        return redirect()->route('admin.elections.index')
            ->with('success', 'Pemilihan diarsipkan.');
    }
}
