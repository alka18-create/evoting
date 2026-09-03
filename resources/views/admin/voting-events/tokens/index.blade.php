<x-layouts.admin title="Token Event">
    <div class="mb-6"><h3 class="text-lg font-semibold">{{ $votingEvent->name }} — Token</h3><p class="text-sm text-gray-500">1 token untuk semua pemilihan dalam event ({{ $votingEvent->elections->count() }} organisasi)</p></div>
    <div class="flex gap-2 mb-6">
        <form method="POST" action="{{ route('admin.voting-events.tokens.assign-all',$votingEvent) }}">@csrf<button class="bg-gray-800 text-white px-4 py-2 rounded-xl text-sm">Assign Semua Siswa Aktif</button></form>
        <form method="POST" action="{{ route('admin.voting-events.tokens.issue-all',$votingEvent) }}">@csrf<button class="bg-emerald-600 text-white px-4 py-2 rounded-xl text-sm">Generate Token Semua</button></form>
        <a href="{{ route('admin.voting-events.tokens.print-bulk',$votingEvent) }}" class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm">Print Bulk</a>
    </div>
    @if(session('issued_tokens'))
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-xl mb-4 text-sm"><b>{{ count(session('issued_tokens')) }} token baru:</b> @foreach(session('issued_tokens') as $t)<span class="inline-block bg-white border px-2 py-1 rounded-lg m-1">{{ $t['student_id'] }} - {{ $t['token'] }}</span>@endforeach</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-500"><tr><th class="px-4 py-3 text-left">Siswa</th><th class="px-4 py-3 text-left">Kelas</th><th class="px-4 py-3 text-left">Token</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
            <tbody>
            @forelse($eventVoters as $ev)
                <tr class="border-t hover:bg-gray-50"><td class="px-4 py-3"><p class="font-medium">{{ $ev->voter->name }}</p><p class="text-xs text-gray-400">{{ $ev->voter->student_id }}</p></td><td class="px-4 py-3">{{ $ev->voter->class_name }}</td><td class="px-4 py-3">@if($ev->hasToken())<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20">● Diterbitkan</span>@else<span class="text-xs text-gray-400">—</span>@endif</td><td class="px-4 py-3 text-right flex justify-end gap-2">@if($ev->hasToken())<a href="{{ route('admin.voting-events.tokens.print-card',[$votingEvent,$ev]) }}" class="text-primary-600 text-xs hover:underline">Print</a>@endif<form method="POST" action="{{ route('admin.voting-events.tokens.destroy',[$votingEvent,$ev]) }}" onsubmit="return confirm('Hapus token?')">@csrf @method('DELETE')<button class="text-red-600 text-xs">Hapus</button></form></td></tr>
            @empty
                <tr><td colspan="4" class="text-center py-8 text-gray-400">Belum ada voter untuk event ini. Klik Assign Semua Siswa Aktif.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $eventVoters->links() }}</div>
    </div>

    @if($availableVoters->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-sm border p-6 mt-6">
        <h4 class="font-semibold mb-3">Tambah Manual</h4>
        <form method="POST" action="{{ route('admin.voting-events.tokens.issue',$votingEvent) }}">
            @csrf
            <div class="grid grid-cols-2 gap-2 max-h-64 overflow-y-auto border rounded-xl p-3">
                @foreach($availableVoters as $v)<label class="flex items-center gap-2 text-sm p-1 hover:bg-gray-50 rounded"><input type="checkbox" name="voter_ids[]" value="{{ $v->id }}"> {{ $v->name }} ({{ $v->student_id }}) — {{ $v->class_name }}</label>@endforeach
            </div>
            <button class="mt-3 bg-primary-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold">Generate Token Terpilih</button>
        </form>
    </div>
    @endif
    <script>lucide.createIcons();</script>
</x-layouts.admin>
