<x-layouts.admin title="Credential - {{ $election->name }}">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Kelola credential untuk pemilihan ini</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.elections.credentials.export', $election) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                <i data-lucide="download" class="w-4 h-4"></i>
                Export CSV
            </a>
            <a href="{{ route('admin.elections.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali
            </a>
        </div>
    </div>

    @if (session('issued_credentials'))
        <div class="mb-4 bg-emerald-50 border border-emerald-200 rounded-2xl p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="check-circle" class="w-5 h-5 text-emerald-600"></i>
                <h3 class="font-semibold text-emerald-800">Credential Berhasil Diterbitkan</h3>
            </div>
            <p class="text-sm text-emerald-700 mb-4">Bagikan PIN berikut kepada pemilih. PIN hanya ditampilkan sekali!</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach (session('issued_credentials') as $cred)
                    <div class="bg-white rounded-2xl border-2 border-emerald-200 p-5 text-center">
                        <p class="text-sm font-medium text-gray-600 mb-1">{{ $cred['name'] }}</p>
                        <p class="text-xs text-gray-400 mb-3">NIS: {{ $cred['student_id'] }}</p>
                        <div class="bg-gradient-to-r from-emerald-500 to-emerald-600 rounded-xl py-3 px-4">
                            <p class="text-3xl font-bold text-white tracking-[0.3em]">{{ $cred['credential'] }}</p>
                        </div>
                        <p class="text-xs text-emerald-600 mt-2 font-medium">PIN Voter</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Issue Credential Section -->
    @if ($availableVoters->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-4">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="key-round" class="w-5 h-5 text-gray-400"></i>
                <h3 class="font-semibold text-gray-800">Terbitkan Credential</h3>
            </div>
            <form method="POST" action="{{ route('admin.elections.credentials.issue', $election) }}" class="space-y-4">
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
                    Terbitkan Credential
                </button>
            </form>
        </div>
    @endif

    <!-- Credentials Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Credential Diterbitkan ({{ $credentials->total() }})</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Voter</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Diterbitkan</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Expires</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($credentials as $credential)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $credential->eligibility->voter->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500 font-mono">{{ $credential->eligibility->voter->student_id ?? '-' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @if ($credential->revoked_at)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-red-50 text-red-700 ring-red-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Dicabut
                                    </span>
                                @elseif ($credential->expires_at && $credential->expires_at->isPast())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-amber-50 text-amber-700 ring-amber-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Expired
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $credential->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $credential->expires_at?->format('d/m/Y') ?? '-' }}</td>
                            <td class="px-6 py-4">
                                @if (! $credential->revoked_at)
                                    <form method="POST" action="{{ route('admin.credentials.revoke', $credential) }}" class="inline" onsubmit="return confirm('Yakin mencabut credential ini?')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors ring-1 ring-red-600/20">
                                            <i data-lucide="ban" class="w-3.5 h-3.5"></i> Cabut
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">Dicabut {{ $credential->revoked_at->format('d/m/Y') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <i data-lucide="key-round" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada credential diterbitkan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $credentials->links() }}</div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>
