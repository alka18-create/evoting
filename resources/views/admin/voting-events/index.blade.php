<x-layouts.admin title="Event Pemilihan">
    <div class="flex items-center justify-between mb-6">
        <div><h3 class="text-lg font-semibold">Event Pemilihan</h3><p class="text-sm text-gray-500">Paket serentak: 1 token untuk semua organisasi</p></div>
        <a href="{{ route('admin.voting-events.create') }}" class="bg-primary-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-700">+ Buat Event</a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-left">Status</th><th class="px-4 py-3 text-left">Periode</th><th class="px-4 py-3 text-center">Pemilihan</th><th class="px-4 py-3 text-center">Voters</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
            <tbody>
            @forelse($events as $ev)
                <tr class="border-t hover:bg-gray-50"><td class="px-4 py-3 font-medium"><a href="{{ route('admin.voting-events.show',$ev) }}" class="text-primary-600 hover:underline">{{ $ev->name }}</a><br><span class="text-xs text-gray-400">{{ $ev->slug }}</span></td><td class="px-4 py-3"><span class="px-2 py-1 rounded-lg text-xs font-semibold {{ $ev->status->value=='OPEN'?'bg-emerald-50 text-emerald-700':($ev->status->value=='SCHEDULED'?'bg-blue-50 text-blue-700':'bg-gray-100 text-gray-600') }}">{{ $ev->status->label() }}</span></td><td class="px-4 py-3 text-xs">{{ $ev->starts_at?->format('d/m/Y H:i') }} - {{ $ev->ends_at?->format('d/m/Y H:i') }}</td><td class="px-4 py-3 text-center">{{ $ev->elections_count }}</td><td class="px-4 py-3 text-center">{{ $ev->voting_event_voters_count }}</td><td class="px-4 py-3 text-right flex justify-end gap-2"><a href="{{ route('admin.voting-events.show',$ev) }}" class="text-primary-600 hover:underline">Kelola</a><a href="{{ route('admin.voting-events.edit',$ev) }}" class="text-gray-600 hover:underline">Edit</a>@if($ev->status->value=='DRAFT')<form method="POST" action="{{ route('admin.voting-events.destroy',$ev) }}" onsubmit="return confirm('Hapus event ini?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline text-xs">Hapus</button></form>@endif</td></tr>
            @empty
                <tr><td colspan="6" class="text-center py-8 text-gray-400">Belum ada event</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $events->links() }}</div>
    </div>
    <script>lucide.createIcons();</script>
</x-layouts.admin>
