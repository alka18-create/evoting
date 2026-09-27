<x-layouts.admin title="Detail Catatan Aktivitas">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">Detail log aktivitas #{{ $log->id }}</p>
        </div>
        <a href="{{ route('admin.audit-logs.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <i data-lucide="info" class="w-5 h-5 text-gray-400"></i>
                    <h3 class="font-semibold text-gray-800">Informasi Umum</h3>
                </div>
                <dl class="space-y-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">ID</dt>
                        <dd class="text-sm font-mono font-medium text-gray-800">#{{ $log->id }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">Waktu</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $log->created_at->format('d/m/Y H:i:s') }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">Aksi</dt>
                        <dd>
                            @php
                                $actionColors = [
                                    'LOGIN' => 'bg-blue-50 text-blue-700',
                                    'LOGOUT' => 'bg-gray-50 text-gray-600',
                                    'VOTER_LOGIN' => 'bg-emerald-50 text-emerald-700',
                                    'VOTE_CAST' => 'bg-purple-50 text-purple-700',
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
                        </dd>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">Oleh</dt>
                        <dd class="text-sm font-medium text-gray-800">
                            @if ($log->actor)
                                <span>{{ $log->actor->name }}</span>
                                <span class="text-gray-400 ml-1">({{ $log->actor->role->label() }})</span>
                            @else
                                <span class="text-gray-400">System/Voter</span>
                            @endif
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Metadata -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <i data-lucide="file-json" class="w-5 h-5 text-gray-400"></i>
                    <h3 class="font-semibold text-gray-800">Metadata</h3>
                </div>
                @if ($log->metadata && count($log->metadata) > 0)
                    <div class="bg-gray-50 rounded-xl p-4 font-mono text-sm text-gray-700">
                        <pre class="whitespace-pre-wrap">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </div>
                @else
                    <p class="text-sm text-gray-400">Tidak ada metadata.</p>
                @endif
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-6">
            <!-- Resource -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <i data-lucide="database" class="w-5 h-5 text-gray-400"></i>
                    <h3 class="font-semibold text-gray-800">Resource</h3>
                </div>
                <dl class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">Tipe</dt>
                        <dd class="text-sm font-mono font-medium text-gray-800">{{ $log->resource_type ?? '-' }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">ID</dt>
                        <dd class="text-sm font-mono font-medium text-gray-800">{{ $log->resource_id ?? '-' }}</dd>
                    </div>
                </dl>
            </div>

            <!-- Network -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
                <div class="flex items-center gap-2 mb-5">
                    <i data-lucide="globe" class="w-5 h-5 text-gray-400"></i>
                    <h3 class="font-semibold text-gray-800">Jaringan</h3>
                </div>
                <dl class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">IP Address</dt>
                        <dd class="text-sm font-mono font-medium text-gray-800">{{ $log->ip_address ?? '-' }}</dd>
                    </div>
                    <div class="py-2">
                        <dt class="text-sm text-gray-500 mb-1">User Agent</dt>
                        <dd class="text-xs text-gray-600 break-all leading-relaxed">{{ $log->user_agent ?? '-' }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>

</x-layouts.admin>
