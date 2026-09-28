<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Domain\Elections\Enums\ElectionStatus;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\VotingEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ElectionController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Election::class);

        $query = Election::with(['votingEvent', 'organization'])->latest();

        if ($request->input('voting_event_id') === 'none') {
            $query->whereNull('voting_event_id');
        } elseif ($request->filled('voting_event_id')) {
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
            // Mode standalone (tanpa event) wajib punya jadwal sendiri —
            // kalau ikut event, tanggal boleh kosong (= ikut event).
            'starts_at' => [Rule::requiredIf(fn () => blank($request->input('voting_event_id'))), 'nullable', 'date', 'after:now'],
            'ends_at' => [Rule::requiredIf(fn () => blank($request->input('voting_event_id'))), 'nullable', 'date', 'after:starts_at'],
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
        $scheduleWarnings = self::scheduleWarnings($election);

        return view('admin.elections.show', compact('election', 'scheduleWarnings'));
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

        // Aturan pindah mode (standalone <-> dalam event).
        $oldEventId = $election->voting_event_id ? (int) $election->voting_event_id : null;
        $newEventId = ! empty($validated['voting_event_id']) ? (int) $validated['voting_event_id'] : null;
        $validated['voting_event_id'] = $newEventId;

        if ($newEventId !== $oldEventId) {
            // Hanya boleh pindah mode saat DRAFT.
            if ($election->status !== ElectionStatus::Draft) {
                return back()->withErrors(['error' => 'Event hanya bisa diubah saat pemilihan masih DRAFT.']);
            }

            if ($newEventId === null) {
                // Lepas dari event: wajib punya jadwal sendiri agar tidak yatim tanpa tanggal.
                $starts = $validated['starts_at'] ?? $election->starts_at;
                $ends = $validated['ends_at'] ?? $election->ends_at;

                if (! $starts || ! $ends) {
                    return back()->withErrors(['error' => 'Melepas event wajib disertai tanggal mulai & selesai sendiri.']);
                }
            } else {
                // Masuk ke event: jadwal sendiri (bila diisi) harus muat dalam jadwal event.
                $event = VotingEvent::findOrFail($newEventId);
                $starts = $validated['starts_at'] ?? $election->starts_at;
                $ends = $validated['ends_at'] ?? $election->ends_at;
                $starts = $starts ? \Illuminate\Support\Carbon::parse($starts) : null;
                $ends = $ends ? \Illuminate\Support\Carbon::parse($ends) : null;

                if ($event->starts_at && $starts && $starts < $event->starts_at) {
                    return back()->withErrors(['error' => 'Tanggal mulai pemilihan di luar jadwal event (' . $event->starts_at->format('d/m/Y H:i') . ').']);
                }

                if ($event->ends_at && $ends && $ends > $event->ends_at) {
                    return back()->withErrors(['error' => 'Tanggal selesai pemilihan di luar jadwal event (' . $event->ends_at->format('d/m/Y H:i') . ').']);
                }
            }
        }

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

        // Cegah membuka pemilihan yang jadwal efektifnya belum mulai / sudah lewat.
        // Tanpa ini, admin bisa membuka pemilihan yang pemilihnya pasti gagal vote.
        $election->loadMissing('votingEvent');

        $effectiveStarts = $election->effectiveStartsAt();
        $effectiveEnds = $election->effectiveEndsAt();

        if ($effectiveEnds && now()->gte($effectiveEnds)) {
            return back()->withErrors(['error' => 'Tidak bisa membuka pemilihan: jadwal berakhir (' . $effectiveEnds->format('d/m/Y H:i') . ') sudah lewat. Ubah jadwal terlebih dahulu.']);
        }

        if ($effectiveStarts && now()->lt($effectiveStarts)) {
            return back()->withErrors(['error' => 'Tidak bisa membuka pemilihan: jadwal mulai (' . $effectiveStarts->format('d/m/Y H:i') . ') belum tiba. Mundurkan jadwal mulai atau tunggu waktunya.']);
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

    /**
     * Peringatan jadwal: jadwal efektif pemilihan yang berada di luar jendela
     * event membuat pemilih gagal vote walau status OPEN. Dipakai form create/edit.
     *
     * @return list<string>
     */
    public static function scheduleWarnings(Election $election): array
    {
        $event = $election->votingEvent;

        if (! $event || (! $event->starts_at && ! $event->ends_at)) {
            return [];
        }

        $warnings = [];
        $starts = $election->effectiveStartsAt();
        $ends = $election->effectiveEndsAt();

        if ($event->starts_at && $starts && $starts->lt($event->starts_at)) {
            $warnings[] = 'Jadwal mulai pemilihan (' . $starts->format('d/m/Y H:i') . ') lebih awal dari event (' . $event->starts_at->format('d/m/Y H:i') . '). Pemilih tetap tidak bisa vote sebelum event mulai.';
        }

        if ($event->ends_at && $ends && $ends->gt($event->ends_at)) {
            $warnings[] = 'Jadwal berakhir pemilihan (' . $ends->format('d/m/Y H:i') . ') melewati jadwal berakhir event (' . $event->ends_at->format('d/m/Y H:i') . '). Token pemilih akan kedaluwarsa lebih dulu.';
        }

        if ($ends && now()->gte($ends) && $election->status === ElectionStatus::Open) {
            $warnings[] = 'Jadwal pemilihan sudah lewat, tetapi statusnya masih OPEN — pemilih tidak akan bisa vote.';
        }

        return $warnings;
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
