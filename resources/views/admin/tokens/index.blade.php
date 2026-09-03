<x-layouts.admin title="Token - {{ $election->name }}">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Kelola token pemilihan untuk {{ $election->name }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.elections.tokens.print-bulk', $election) }}" target="_blank" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                <i data-lucide="printer" class="w-4 h-4"></i>
                Cetak Semua Kartu
            </a>
            <a href="{{ route('admin.elections.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali
            </a>
        </div>
    </div>

    @if (session('issued_tokens'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <h3 class="font-semibold text-emerald-800">Token Berhasil Diterbitkan</h3>
            </div>
            <p class="text-sm text-emerald-700 mb-4">Token hanya ditampilkan sekali! Silakan cetak kartu atau catat token berikut.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach (session('issued_tokens') as $item)
                    <div class="bg-white rounded-2xl border-2 border-emerald-200 p-5 text-center">
                        <p class="text-sm font-medium text-gray-600 mb-1">{{ $item['name'] }}</p>
                        <p class="text-xs text-gray-400 mb-1">NIS: {{ $item['student_id'] }}</p>
                        <p class="text-xs text-gray-400 mb-3">{{ $item['class_name'] }}</p>
                        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl py-3 px-4">
                            <p class="text-3xl font-bold text-white tracking-[0.3em]">{{ $item['token'] }}</p>
                        </div>
                        <p class="text-xs text-emerald-600 mt-2 font-medium">PIN Pemilih</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    @if ($availableVoters->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="key-round" class="w-5 h-5 text-gray-400"></i>
                <h3 class="font-semibold text-gray-800">Terbitkan Token</h3>
            </div>
            <form method="POST" action="{{ route('admin.elections.tokens.issue', $election) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Pilih Pemilih</label>
                    <select name="voter_ids[]" multiple required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all h-40">
                        @foreach ($availableVoters as $voter)
                            <option value="{{ $voter->id }}">{{ $voter->name }} ({{ $voter->student_id }}) - {{ $voter->class_name }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1.5">Tahan Ctrl/Cmd untuk memilih beberapa pemilih</p>
                </div>
                <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-lg shadow-emerald-500/25">
                    <i data-lucide="key-round" class="w-4 h-4"></i>
                    Terbitkan Token
                </button>
            </form>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Token Diterbitkan ({{ $eligibilities->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Voter</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Token</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($eligibilities as $eligibility)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $eligibility->voter->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500 font-mono">{{ $eligibility->voter->student_id ?? '-' }} &middot; {{ $eligibility->voter->class_name ?? '' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($eligibility->hasToken())
                                    <span class="inline-flex items-center px-3 py-1.5 bg-emerald-50 rounded-lg text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20">● Diterbitkan{{ $eligibility->expires_at ? ' · exp '.$eligibility->expires_at->format('d/m/Y') : '' }}</span>
                                @else
                                    <span class="text-xs text-gray-400">Belum diterbitkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                @if ($eligibility->hasVoted())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-purple-50 text-purple-700 ring-purple-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Sudah Vote
                                    </span>
                                @elseif ($eligibility->status === 'REVOKED')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-red-50 text-red-700 ring-red-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Dicabut
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    @if ($eligibility->hasToken() && ! $eligibility->hasVoted())
                                        <a href="{{ route('admin.elections.tokens.print-card', [$election, $eligibility]) }}" target="_blank" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors ring-1 ring-blue-600/20">
                                            <i data-lucide="printer" class="w-3.5 h-3.5"></i> Cetak
                                        </a>
                                        <form method="POST" action="{{ route('admin.elections.tokens.destroy', [$election, $eligibility]) }}" class="inline" onsubmit="return confirm('Yakin menghapus token ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors ring-1 ring-red-600/20">
                                                <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">-</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <i data-lucide="key-round" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada token diterbitkan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $eligibilities->links() }}</div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>
