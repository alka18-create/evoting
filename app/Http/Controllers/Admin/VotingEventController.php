<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Elections\Enums\VotingEventStatus;
use App\Domain\Maintenance\Services\DataWipeService;
use App\Http\Controllers\Controller;
use App\Models\VotingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class VotingEventController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', VotingEvent::class);

        $events = VotingEvent::withCount(['elections', 'votingEventVoters'])->latest()->paginate(20);

        return view('admin.voting-events.index', compact('events'));
    }

    public function create()
    {
        Gate::authorize('create', VotingEvent::class);

        return view('admin.voting-events.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', VotingEvent::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:voting_events,slug',
            'description' => 'nullable|string',
            'starts_at' => 'required|date|after:now',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']) . '-' . Str::random(5);
        $validated['created_by'] = Auth::id();

        $event = VotingEvent::create($validated);

        AuditLogger::log(action: 'VOTING_EVENT_CREATED', resourceType: 'VotingEvent', resourceId: $event->id, metadata: ['name' => $event->name]);

        return redirect()->route('admin.voting-events.index')->with('success', 'Event pemilihan berhasil dibuat.');
    }

    public function show(VotingEvent $votingEvent)
    {
        Gate::authorize('view', $votingEvent);

        $votingEvent->load(['elections.organization', 'elections' => fn ($q) => $q->withCount(['candidates', 'ballots'])]);
        $votingEvent->loadCount(['votingEventVoters', 'elections']);

        return view('admin.voting-events.show', compact('votingEvent'));
    }

    public function edit(VotingEvent $votingEvent)
    {
        Gate::authorize('update', $votingEvent);

        return view('admin.voting-events.edit', compact('votingEvent'));
    }

    public function update(Request $request, VotingEvent $votingEvent)
    {
        Gate::authorize('update', $votingEvent);

        if (! in_array($votingEvent->status, [VotingEventStatus::Draft, VotingEventStatus::Scheduled])) {
            return back()->withErrors(['error' => 'Hanya event DRAFT atau SCHEDULED yang bisa diedit.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:voting_events,slug,' . $votingEvent->id,
            'description' => 'nullable|string',
            'starts_at' => 'required|date',
            'ends_at' => 'required|date|after:starts_at',
        ]);

        if (empty($validated['slug'])) {
            $validated['slug'] = $votingEvent->slug;
        }

        // PENTING: token menyimpan snapshot expires_at = ends_at saat dibuat.
        // Kalau jadwal event digeser, token lama harus ikut disinkronkan —
        // jika tidak, semua pemilih gagal login walau event sudah dibuka.
        $endsAtChanged = $votingEvent->ends_at?->notEqualTo(\Illuminate\Support\Carbon::parse($validated['ends_at'])) ?? true;

        $votingEvent->update($validated);

        if ($endsAtChanged) {
            $synced = $votingEvent->votingEventVoters()
                ->update(['expires_at' => $votingEvent->ends_at]);

            if ($synced > 0) {
                AuditLogger::log(
                    action: 'VOTING_EVENT_TOKENS_EXPIRY_SYNCED',
                    resourceType: 'VotingEvent',
                    resourceId: $votingEvent->id,
                    metadata: ['name' => $votingEvent->name, 'tokens_synced' => $synced, 'new_ends_at' => (string) $votingEvent->ends_at]
                );
            }
        }

        AuditLogger::log(action: 'VOTING_EVENT_UPDATED', resourceType: 'VotingEvent', resourceId: $votingEvent->id, metadata: ['name' => $votingEvent->name]);

        return redirect()->route('admin.voting-events.index')->with('success', 'Event diperbarui.');
    }

    public function deleteConfirm(VotingEvent $votingEvent, DataWipeService $wipe)
    {
        Gate::authorize('delete', $votingEvent);

        $impact = $wipe->impactForVotingEvent($votingEvent);

        $warnings = [];
        if ($impact['ballots'] > 0) {
            $warnings[] = "{$impact['ballots']} suara yang sudah masuk akan ikut terhapus dan hasil berubah.";
        }
        if ($votingEvent->status !== VotingEventStatus::Draft) {
            $warnings[] = 'Event ini berstatus ' . $votingEvent->status->label() . ' (bukan DRAFT).';
        }

        return view('admin.shared.delete-confirm', [
            'title' => 'Hapus Event Voting',
            'itemType' => 'event voting',
            'itemName' => $votingEvent->name,
            'impacts' => [
                'Pemilihan' => $impact['elections'],
                'Kandidat' => $impact['candidates'],
                'Token / eligibilitas' => $impact['eligibilities'],
                'Suara (ballot)' => $impact['ballots'],
                'Data event-voter' => $impact['event_voters'],
                'File PDF kartu' => $impact['token_pdfs'],
            ],
            'warnings' => $warnings,
            'action' => route('admin.voting-events.destroy', $votingEvent),
            'cancelUrl' => route('admin.voting-events.index'),
        ]);
    }

    public function destroy(VotingEvent $votingEvent, Request $request, DataWipeService $wipe)
    {
        Gate::authorize('delete', $votingEvent);

        $impact = $wipe->impactForVotingEvent($votingEvent);
        $related = $impact['elections'] + $impact['event_voters'];

        if ($related > 0 && $request->input('confirmation') !== $votingEvent->name) {
            if ($request->has('confirmation')) {
                return back()->withErrors(['error' => 'Konfirmasi tidak cocok. Ketik nama persis seperti ditampilkan.']);
            }

            return redirect()->route('admin.voting-events.delete-confirm', $votingEvent);
        }

        $eventName = $votingEvent->name;
        $eventId = $votingEvent->id;

        $counts = $wipe->deleteVotingEvent($votingEvent);

        AuditLogger::log(
            action: 'VOTING_EVENT_DELETED',
            resourceType: 'VotingEvent',
            resourceId: $eventId,
            metadata: ['name' => $eventName, 'wiped' => $counts]
        );

        return redirect()->route('admin.voting-events.index')
            ->with('success', "Event \"{$eventName}\" berhasil dihapus.");
    }

    public function schedule(VotingEvent $votingEvent)
    {
        Gate::authorize('update', $votingEvent);

        if ($votingEvent->status !== VotingEventStatus::Draft) {
            return back()->withErrors(['error' => 'Transisi tidak valid.']);
        }

        $votingEvent->update(['status' => VotingEventStatus::Scheduled]);

        AuditLogger::log(action: 'VOTING_EVENT_SCHEDULED', resourceType: 'VotingEvent', resourceId: $votingEvent->id, metadata: ['name' => $votingEvent->name]);

        return back()->with('success', 'Event dijadwalkan.');
    }

    public function open(VotingEvent $votingEvent)
    {
        Gate::authorize('update', $votingEvent);

        if ($votingEvent->status !== VotingEventStatus::Scheduled) {
            return back()->withErrors(['error' => 'Transisi tidak valid.']);
        }

        // Cegah membuka event yang jadwalnya sudah lewat — kalau dibuka,
        // voter tidak akan pernah bisa masuk karena gerbang waktu.
        if ($votingEvent->ends_at && now()->gt($votingEvent->ends_at)) {
            return back()->withErrors(['error' => 'Tidak bisa membuka event: jadwal berakhir (' . $votingEvent->ends_at->format('d/m/Y H:i') . ') sudah lewat. Ubah jadwal terlebih dahulu.']);
        }

        $votingEvent->update(['status' => VotingEventStatus::Open, 'opened_at' => now()]);

        // Pastikan expiry token ikut jadwal event terbaru.
        $votingEvent->votingEventVoters()
            ->update(['expires_at' => $votingEvent->ends_at]);

        AuditLogger::log(action: 'VOTING_EVENT_OPENED', resourceType: 'VotingEvent', resourceId: $votingEvent->id, metadata: ['name' => $votingEvent->name]);

        return back()->with('success', 'Event dibuka untuk voting.');
    }

    public function close(VotingEvent $votingEvent)
    {
        Gate::authorize('update', $votingEvent);

        if ($votingEvent->status !== VotingEventStatus::Open) {
            return back()->withErrors(['error' => 'Transisi tidak valid.']);
        }

        $votingEvent->update(['status' => VotingEventStatus::Closed, 'closed_at' => now()]);

        AuditLogger::log(action: 'VOTING_EVENT_CLOSED', resourceType: 'VotingEvent', resourceId: $votingEvent->id, metadata: ['name' => $votingEvent->name]);

        return back()->with('success', 'Event ditutup.');
    }

    public function archive(VotingEvent $votingEvent)
    {
        Gate::authorize('update', $votingEvent);

        if ($votingEvent->status !== VotingEventStatus::Closed) {
            return back()->withErrors(['error' => 'Transisi tidak valid.']);
        }

        $votingEvent->update(['status' => VotingEventStatus::Archived]);

        AuditLogger::log(action: 'VOTING_EVENT_ARCHIVED', resourceType: 'VotingEvent', resourceId: $votingEvent->id, metadata: ['name' => $votingEvent->name]);

        return back()->with('success', 'Event diarsipkan.');
    }

    public function bulkCreateElections(Request $request, VotingEvent $votingEvent)
    {
        Gate::authorize('create', \App\Models\Election::class);

        $request->validate([
            'organization_ids' => 'required|array|min:1',
            'organization_ids.*' => 'exists:organizations,id',
        ]);

        $created = 0;
        $skipped = 0;

        foreach ($request->organization_ids as $orgId) {
            $org = \App\Models\Organization::find($orgId);

            // Skip jika sudah ada pemilihan untuk event+organisasi
            $exists = \App\Models\Election::where('voting_event_id', $votingEvent->id)
                ->where('organization_id', $orgId)
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            \App\Models\Election::create([
                'name' => 'Pemilihan ' . $org->name . ' — ' . $votingEvent->name,
                'description' => 'Pemilihan ' . $org->name . ' dalam event ' . $votingEvent->name,
                'voting_event_id' => $votingEvent->id,
                'organization_id' => $orgId,
                'status' => \App\Domain\Elections\Enums\ElectionStatus::Draft,
                'starts_at' => null,
                'ends_at' => null,
                'created_by' => Auth::id(),
            ]);

            $created++;
            AuditLogger::log(action: 'ELECTION_CREATED_BULK', resourceType: 'Election', resourceId: 0, metadata: ['voting_event_id' => $votingEvent->id, 'organization_id' => $orgId]);
        }

        $msg = $created > 0 ? "$created pemilihan berhasil dibuat." : "Tidak ada pemilihan baru.";
        if ($skipped > 0) $msg .= " ($skipped sudah ada, dilewati)";

        return back()->with('success', $msg);
    }
}
