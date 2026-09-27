<x-layouts.admin title="Token Event">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <h3 class="text-lg font-semibold text-gray-800">{{ $votingEvent->name }} — Token</h3>
            <p class="text-sm text-gray-500">1 token untuk semua pemilihan dalam event ({{ $votingEvent->elections->count() }} organisasi)</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <form method="POST" action="{{ route('admin.voting-events.tokens.assign-all',$votingEvent) }}">@csrf<button class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-all">Assign Semua Siswa Aktif</button></form>
            <form method="POST" action="{{ route('admin.voting-events.tokens.issue-all',$votingEvent) }}">@csrf<button class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-lg shadow-emerald-500/25">Generate Token Semua</button></form>
            <a href="{{ route('admin.voting-events.tokens.print-bulk',$votingEvent) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all"><i data-lucide="printer" class="w-4 h-4"></i> Cetak Kartu</a>
        </div>
    </div>
    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl mb-4 text-sm">
            @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
        </div>
    @endif
    @if(session('issued_tokens'))
        <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl mb-4 text-sm"><b>{{ count(session('issued_tokens')) }} token baru (tampil sekali):</b> @foreach(session('issued_tokens') as $t)<span class="inline-block bg-white border px-2 py-1 rounded-lg m-1 font-mono">{{ $t['student_id'] }} - {{ $t['token'] }}</span>@endforeach</div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full min-w-[720px]">
                <thead class="bg-gray-50/50"><tr><th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Siswa</th><th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelas</th><th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Token</th><th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                @forelse($eventVoters as $ev)
                    <tr class="hover:bg-gray-50/50 transition-colors">
                        <td class="px-6 py-4"><p class="font-semibold text-gray-800">{{ $ev->voter->name }}</p><p class="text-xs text-gray-500 font-mono">{{ $ev->voter->student_id }}</p></td>
                        <td class="px-6 py-4 text-sm text-gray-500">{{ $ev->voter->class_name }}</td>
                        <td class="px-6 py-4">@if($ev->hasToken())<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Diterbitkan</span>@else<span class="text-xs text-gray-400">—</span>@endif</td>
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 whitespace-nowrap">
                                @if($ev->hasToken())
                                    <form method="POST" action="{{ route('admin.voting-events.tokens.reissue',[$votingEvent,$ev]) }}" class="inline" onsubmit="return confirm('Rotasi token? Token lama tidak berlaku, token baru tampil sekali.')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 rounded-lg hover:bg-amber-100 transition-colors ring-1 ring-amber-600/20">
                                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Rotasi
                                        </button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.voting-events.tokens.destroy',[$votingEvent,$ev]) }}" class="inline" onsubmit="return confirm('Hapus token?')">@csrf @method('DELETE')<button class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors ring-1 ring-red-600/20"><i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus</button></form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-6 py-16 text-center"><i data-lucide="users" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i><p class="text-gray-500 text-sm">Belum ada voter untuk event ini.</p><p class="text-xs text-gray-400 mt-1">Klik Assign Semua Siswa Aktif.</p></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-4">{{ $eventVoters->links() }}</div>

    @if($availableVoters->isNotEmpty())
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mt-4">
        <h4 class="font-semibold text-gray-800 mb-1">Tambah Manual</h4>
        <p class="text-xs text-gray-500 mb-3">Pilih siswa untuk ditambahkan ke event ini</p>
        <form method="POST" action="{{ route('admin.voting-events.tokens.issue',$votingEvent) }}">
            @csrf
            <label for="manual-voters" class="sr-only">Pilih siswa</label>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto border border-gray-200 rounded-xl p-3">
                @foreach($availableVoters as $v)<label class="flex items-center gap-2 text-sm p-1.5 hover:bg-gray-50 rounded truncate"><input type="checkbox" name="voter_ids[]" value="{{ $v->id }}" class="rounded"> <span class="truncate">{{ $v->name }} ({{ $v->student_id }}) — {{ $v->class_name }}</span></label>@endforeach
            </div>
            <button class="mt-3 inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">Generate Token Terpilih</button>
        </form>
    </div>
    @endif
</x-layouts.admin>
