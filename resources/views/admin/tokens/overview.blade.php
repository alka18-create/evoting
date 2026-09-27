<x-layouts.admin title="Token Pemilih">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Kelola token akses untuk semua pemilihan</p>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="vote" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $elections->total() }}</p>
                    <p class="text-xs text-gray-500">Total Pemilihan</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="key-round" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $elections->getCollection()->sum('total_tokens') }}</p>
                    <p class="text-xs text-gray-500">Total Token</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-50 rounded-xl flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5 text-purple-600"></i>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-800">{{ $elections->getCollection()->sum('voted_tokens') }}</p>
                    <p class="text-xs text-gray-500">Sudah Vote</p>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Daftar Pemilihan</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pemilihan</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Token</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Sudah Vote</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($elections as $election)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-800">{{ $election->name }}</p>
                                    <p class="text-xs text-gray-500">{{ $election->description }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'DRAFT' => 'bg-gray-50 text-gray-700 ring-gray-600/20',
                                        'SCHEDULED' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'OPEN' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'CLOSED' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'ARCHIVED' => 'bg-red-50 text-red-700 ring-red-600/20',
                                    ];
                                @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $statusColors[$election->status->value] ?? '' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusColors[$election->status->value] ?? '' }}"></span>
                                    {{ $election->status->label() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-gray-800">{{ $election->issued_tokens }}</span>
                                    <span class="text-xs text-gray-400">/ {{ $election->total_tokens }}</span>
                                </div>
                                <div class="w-24 bg-gray-100 rounded-full h-1.5 mt-1">
                                    <div class="bg-emerald-500 h-1.5 rounded-full" style="width: {{ $election->total_tokens > 0 ? ($election->issued_tokens / $election->total_tokens) * 100 : 0 }}%"></div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-semibold text-gray-800">{{ $election->voted_tokens }}</span>
                                <span class="text-xs text-gray-400"> / {{ $election->issued_tokens }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.elections.tokens.index', $election) }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-gradient-to-r from-emerald-600 to-emerald-700 rounded-xl hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-lg shadow-emerald-500/25">
                                    <i data-lucide="key-round" class="w-4 h-4"></i>
                                    Kelola Token
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <i data-lucide="key-round" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada pemilihan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $elections->links() }}</div>

</x-layouts.admin>
