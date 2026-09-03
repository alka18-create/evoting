<x-layouts.admin title="Detail Event">
    <div class="flex items-center justify-between mb-6">
        <div><h3 class="text-lg font-semibold">{{ $votingEvent->name }}</h3><p class="text-sm text-gray-500">{{ $votingEvent->status->label() }} • {{ $votingEvent->starts_at?->format('d/m/Y H:i') }} - {{ $votingEvent->ends_at?->format('d/m/Y H:i') }}</p></div>
        <div class="flex gap-2 flex-wrap">
            @if($votingEvent->status->value=='DRAFT')<form method="POST" action="{{ route('admin.voting-events.schedule',$votingEvent) }}">@csrf<button class="bg-blue-600 text-white px-4 py-2 rounded-xl text-sm">Jadwalkan</button></form>@endif
            @if($votingEvent->status->value=='SCHEDULED')<form method="POST" action="{{ route('admin.voting-events.open',$votingEvent) }}">@csrf<button class="bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm">Buka Event</button></form>@endif
            @if($votingEvent->status->value=='OPEN')<form method="POST" action="{{ route('admin.voting-events.close',$votingEvent) }}">@csrf<button class="bg-amber-600 text-white px-4 py-2 rounded-xl text-sm">Tutup</button></form>@endif
            @if($votingEvent->status->value=='CLOSED')<form method="POST" action="{{ route('admin.voting-events.archive',$votingEvent) }}">@csrf<button class="bg-gray-600 text-white px-4 py-2 rounded-xl text-sm">Arsipkan</button></form>@endif
            @if($votingEvent->status->value=='DRAFT')
                <form method="POST" action="{{ route('admin.voting-events.destroy', $votingEvent) }}" onsubmit="return confirm('Hapus event ini? Hanya event DRAFT tanpa data yang bisa dihapus.')">
                    @csrf @method('DELETE')
                    <button class="bg-red-50 text-red-700 border border-red-200 px-4 py-2 rounded-xl text-sm hover:bg-red-100">Hapus Event</button>
                </form>
            @endif
            <a href="{{ route('admin.voting-events.index') }}" class="px-4 py-2 rounded-xl text-sm bg-gray-100 hover:bg-gray-200">Kembali</a>
        </div>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border"><p class="text-xs text-gray-400 uppercase">Pemilihan</p><p class="text-2xl font-bold">{{ $votingEvent->elections->count() }}</p></div>
        <div class="bg-white rounded-xl p-4 border"><p class="text-xs text-gray-400 uppercase">Voters Terdaftar</p><p class="text-2xl font-bold">{{ $votingEvent->voting_event_voters_count }}</p></div>
        <div class="bg-white rounded-xl p-4 border"><p class="text-xs text-gray-400 uppercase">Token Terbit</p><p class="text-2xl font-bold">{{ $votingEvent->votingEventVoters->whereNotNull('token')->count() }}</p></div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between mb-4"><h4 class="font-semibold">Pemilihan dalam Event</h4><a href="{{ route('admin.elections.create') }}?voting_event_id={{ $votingEvent->id }}" class="text-sm text-primary-600 hover:underline">+ Tambah Manual</a></div>
        <div class="space-y-2 mb-4">
            @forelse($votingEvent->elections as $el)
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border">
                    <div><p class="font-medium text-sm">{{ $el->name }} @if($el->organization)<span class="text-xs bg-primary-50 text-primary-700 px-2 py-0.5 rounded-lg">{{ $el->organization->name }}</span>@endif</p><p class="text-xs text-gray-500">{{ $el->status->value }} • {{ $el->candidates_count }} kandidat • {{ $el->ballots_count }} suara</p></div>
                    <a href="{{ route('admin.elections.show',$el) }}" class="text-xs text-primary-600 hover:underline">Kelola</a>
                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-2">Belum ada pemilihan.</p>
            @endforelse
        </div>
        @php
            $existingOrgIds = $votingEvent->elections->pluck('organization_id')->filter()->toArray();
            $availableOrgs = \App\Models\Organization::whereNotIn('id', $existingOrgIds)->orderBy('name')->get();
        @endphp
        @if($availableOrgs->isNotEmpty())
        <div class="border-t pt-4 mt-4">
            <p class="text-sm font-medium text-gray-700 mb-2">Buat Sekaligus — Klik Organisasi (tanpa ketik nama):</p>
            <form method="POST" action="{{ route('admin.voting-events.elections.bulk', $votingEvent) }}" class="flex flex-wrap gap-2">
                @csrf
                @foreach($availableOrgs as $org)
                    <label class="inline-flex items-center gap-2 px-3 py-2 bg-primary-50 border border-primary-200 rounded-xl text-sm cursor-pointer hover:bg-primary-100">
                        <input type="checkbox" name="organization_ids[]" value="{{ $org->id }}" checked class="rounded">
                        {{ $org->name }}
                    </label>
                @endforeach
                <button class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm font-semibold hover:bg-primary-700">Buat Pemilihan Terpilih</button>
            </form>
            <p class="text-xs text-gray-400 mt-2">Nama otomatis: "Pemilihan {Organisasi} — {Event}". Tanggal ikut event, organisasi otomatis terisi.</p>
        </div>
        @else
            <p class="text-xs text-gray-400 border-t pt-3 mt-4">Semua organisasi sudah punya pemilihan di event ini.</p>
        @endif
    </div>

    <div class="bg-white rounded-2xl shadow-sm border p-6 mb-6">
        <div class="flex items-center justify-between mb-4"><h4 class="font-semibold">Token & Voter</h4><div class="flex gap-2"><a href="{{ route('admin.voting-events.tokens.index',$votingEvent) }}" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm">Kelola Token</a></div></div>
        <form method="POST" action="{{ route('admin.voting-events.tokens.assign-all',$votingEvent) }}" class="inline">@csrf<button class="text-sm bg-gray-100 hover:bg-gray-200 px-4 py-2 rounded-xl">Assign Semua Siswa Aktif</button></form>
        <form method="POST" action="{{ route('admin.voting-events.tokens.issue-all',$votingEvent) }}" class="inline ml-2">@csrf<button class="text-sm bg-emerald-50 text-emerald-700 hover:bg-emerald-100 px-4 py-2 rounded-xl">Generate Token Semua (1 token untuk semua)</button></form>
    </div>

    <div class="mb-2">
        <h4 class="font-semibold text-gray-800 mb-3 flex items-center gap-2"><i data-lucide="activity" class="w-4 h-4"></i> Monitoring Per Organisasi (Realtime)</h4>
        <livewire:admin.realtime-monitor :votingEventId="$votingEvent->id" />
    </div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>
