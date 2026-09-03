<x-layouts.admin title="{{ $voter->name }}">
    <div class="max-w-2xl">
        <!-- Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold text-gray-800">Informasi Pemilih</h3>
                    <div class="flex items-center gap-2">
                        @if ($voter->is_active)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Aktif
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-red-50 text-red-700 ring-red-600/20">
                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                Nonaktif
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-6">
                <dl class="space-y-4">
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">NIS / Student ID</dt>
                        <dd class="text-sm font-mono font-medium text-gray-800">{{ $voter->student_id }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">Nama Lengkap</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $voter->name }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2 border-b border-gray-50">
                        <dt class="text-sm text-gray-500">Kelas</dt>
                        <dd class="text-sm font-medium text-gray-800">{{ $voter->class_name }}</dd>
                    </div>
                    <div class="flex items-center justify-between py-2">
                        <dt class="text-sm text-gray-500">Dibuat</dt>
                        <dd class="text-sm text-gray-600">{{ $voter->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        <!-- Eligibilities -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <h3 class="font-semibold text-gray-800">Eligibilitas Pemilihan</h3>
            </div>

            <div class="p-6">
                @forelse ($voter->eligibilities as $eligibility)
                    <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-50' : '' }}">
                        <div>
                            <p class="font-medium text-gray-800">{{ $eligibility->election->name ?? '-' }}</p>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $eligibility->election->starts_at?->format('d/m/Y') ?? '-' }} - {{ $eligibility->election->ends_at?->format('d/m/Y') ?? '-' }}
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            @php
                                $statusColors = [
                                    'ELIGIBLE' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                    'VOTED' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                    'REVOKED' => 'bg-red-50 text-red-700 ring-red-600/20',
                                ];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $statusColors[$eligibility->status] ?? 'bg-gray-50 text-gray-600' }}">
                                {{ $eligibility->status }}
                            </span>
                            @if ($eligibility->credential)
                                @if ($eligibility->credential->revoked_at)
                                    <span class="text-xs text-red-500">Credential dicabut</span>
                                @else
                                    <span class="text-xs text-emerald-500">Credential aktif</span>
                                @endif
                            @else
                                <span class="text-xs text-gray-400">Belum ada credential</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8">
                        <i data-lucide="vote" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                        <p class="text-gray-500 text-sm">Belum ada data eligibilitas.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="mt-4">
            <a href="{{ route('admin.voters.index') }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali ke Daftar Pemilih
            </a>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>
