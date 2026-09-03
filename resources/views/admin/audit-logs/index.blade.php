<x-layouts.admin title="Catatan Aktivitas">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Log aktivitas sistem dan pengguna</p>
        </div>
        <div class="flex items-center gap-2">
            @can('export', \App\Models\AuditLog::class)
                <a href="{{ route('admin.audit-logs.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                    <i data-lucide="download" class="w-4 h-4"></i>
                    Export CSV
                </a>
            @endcan
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-3">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Cari</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari aksi, resource..."
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Aksi</label>
                <select name="action" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                    <option value="">Semua</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" {{ request('action') === $action ? 'selected' : '' }}>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Resource</label>
                <select name="resource_type" class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                    <option value="">Semua</option>
                    @foreach ($resourceTypes as $type)
                        <option value="{{ $type }}" {{ request('resource_type') === $type ? 'selected' : '' }}>{{ $type }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Dari</label>
                <input type="date" name="from" value="{{ request('from') }}"
                    class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Sampai</label>
                <div class="flex gap-2">
                    <input type="date" name="to" value="{{ request('to') }}"
                        class="w-full px-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                    <button type="submit" class="px-3 py-2 bg-primary-600 text-white text-sm font-semibold rounded-lg hover:bg-primary-700 transition-colors">
                        <i data-lucide="search" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Waktu</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Oleh</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Resource</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 text-sm text-gray-500 whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $actionColors = [
                                        'LOGIN' => 'bg-blue-50 text-blue-700',
                                        'LOGOUT' => 'bg-gray-50 text-gray-600',
                                        'VOTER_LOGIN' => 'bg-emerald-50 text-emerald-700',
                                        'VOTE_CAST' => 'bg-purple-50 text-purple-700',
                                        'VOTE_SESSION_INVALIDATED' => 'bg-amber-50 text-amber-700',
                                        'ELECTION_OPENED' => 'bg-emerald-50 text-emerald-700',
                                        'ELECTION_CLOSED' => 'bg-red-50 text-red-700',
                                        'VOTER_CREATED' => 'bg-blue-50 text-blue-700',
                                        'VOTER_DELETED' => 'bg-red-50 text-red-700',
                                        'CREDENTIAL_ISSUED' => 'bg-amber-50 text-amber-700',
                                        'CREDENTIAL_REVOKED' => 'bg-red-50 text-red-700',
                                    ];
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $actionColors[$log->action] ?? 'bg-gray-50 text-gray-600' }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-800">
                                {{ $log->actor?->name ?? 'System/Voter' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if ($log->resource_type)
                                    <span class="font-mono text-xs">{{ $log->resource_type }}</span>
                                    @if ($log->resource_id)
                                        <span class="text-gray-400">#{{ $log->resource_id }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 font-mono">
                                {{ $log->ip_address ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.audit-logs.show', $log) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors ring-1 ring-primary-600/20">
                                    <i data-lucide="eye" class="w-3.5 h-3.5"></i>
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <i data-lucide="scroll-text" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada catatan aktivitas.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $logs->links() }}</div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>
