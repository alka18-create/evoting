<x-layouts.admin title="{{ $election->name }}">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            @php
                $statusColors = [
                    'OPEN' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                    'CLOSED' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                    'DRAFT' => 'bg-gray-50 text-gray-600 ring-gray-500/20',
                    'SCHEDULED' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                    'ARCHIVED' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                ];
                $dotColors = [
                    'OPEN' => 'bg-emerald-500',
                    'CLOSED' => 'bg-amber-500',
                    'DRAFT' => 'bg-gray-400',
                    'SCHEDULED' => 'bg-blue-500',
                    'ARCHIVED' => 'bg-purple-500',
                ];
            @endphp
            @php $statusVal = $election->status->value; @endphp
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $statusColors[$statusVal] ?? 'bg-gray-50 text-gray-600' }}">
                <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$statusVal] ?? 'bg-gray-400' }}"></span>
                {{ $statusVal }}
            </span>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.elections.candidates.index', $election) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-purple-700 bg-purple-50 rounded-xl hover:bg-purple-100 transition-colors ring-1 ring-purple-600/20">
                <i data-lucide="user-check" class="w-4 h-4"></i>
                Kandidat
            </a>
            <a href="{{ route('admin.elections.tokens.index', $election) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-orange-700 bg-orange-50 rounded-xl hover:bg-orange-100 transition-colors ring-1 ring-orange-600/20">
                <i data-lucide="key-round" class="w-4 h-4"></i>
                Token
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Info Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-2 mb-5">
                <i data-lucide="info" class="w-5 h-5 text-gray-400"></i>
                <h3 class="font-semibold text-gray-800">Informasi</h3>
            </div>
            <dl class="space-y-4">
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <dt class="text-sm text-gray-500">Mulai</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $election->starts_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-gray-50">
                    <dt class="text-sm text-gray-500">Selesai</dt>
                    <dd class="text-sm font-medium text-gray-800">{{ $election->ends_at?->format('d/m/Y H:i') ?? '-' }}</dd>
                </div>
            </dl>
        </div>

        <!-- Stats Card -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-2 mb-5">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-gray-400"></i>
                <h3 class="font-semibold text-gray-800">Statistik</h3>
            </div>
            <livewire:admin.election-stats :electionId="$election->id" />
        </div>

        @if ($election->description)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 md:col-span-2">
                <div class="flex items-center gap-2 mb-4">
                    <i data-lucide="file-text" class="w-5 h-5 text-gray-400"></i>
                    <h3 class="font-semibold text-gray-800">Deskripsi</h3>
                </div>
                <p class="text-gray-600 text-sm leading-relaxed">{{ $election->description }}</p>
            </div>
        @endif
    </div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>
