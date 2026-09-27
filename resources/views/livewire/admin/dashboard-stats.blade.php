<div wire:poll.5s="refreshStats">
    {{-- Stat Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        @php
            $cards = [
                ['label' => 'Total Pemilihan', 'value' => $totalElections, 'icon' => 'vote', 'from' => 'from-blue-500', 'to' => 'to-blue-600', 'shadow' => 'shadow-blue-500/25'],
                ['label' => 'Pemilihan Aktif', 'value' => $activeElections, 'icon' => 'activity', 'from' => 'from-emerald-500', 'to' => 'to-emerald-600', 'shadow' => 'shadow-emerald-500/25'],
                ['label' => 'Total Pemilih', 'value' => $totalVoters, 'icon' => 'users', 'from' => 'from-cyan-500', 'to' => 'to-cyan-600', 'shadow' => 'shadow-cyan-500/25'],
                ['label' => 'Total Suara', 'value' => $totalVotes, 'icon' => 'check-circle', 'from' => 'from-amber-500', 'to' => 'to-amber-600', 'shadow' => 'shadow-amber-500/25'],
            ];
        @endphp

        @foreach ($cards as $card)
            <div class="bg-gradient-to-br {{ $card['from'] }} {{ $card['to'] }} rounded-2xl p-5 text-white shadow-lg {{ $card['shadow'] }} group hover:-translate-y-0.5 transition-transform duration-300">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-white/80 text-sm font-medium">{{ $card['label'] }}</p>
                        <p class="text-3xl font-bold mt-1">{{ $card['value'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm group-hover:scale-110 transition-transform duration-300">
                        <i data-lucide="{{ $card['icon'] }}" class="w-6 h-6"></i>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Participation Stats --}}
    @if($totalEligible > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Sudah Memilih</p>
                <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="user-check" class="w-4 h-4 text-emerald-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ $totalVoted }}</p>
            <p class="text-xs text-gray-400 mt-1">dari {{ $totalEligible }} hak pilih</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Belum Memilih</p>
                <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="user-x" class="w-4 h-4 text-gray-400"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ $totalNotVoted }}</p>
            <p class="text-xs text-gray-400 mt-1">dari {{ $totalEligible }} hak pilih</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Partisipasi</p>
                <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="trending-up" class="w-4 h-4 text-primary-500"></i>
                </div>
            </div>
            <p class="text-2xl font-bold text-primary-600">{{ $participationRate }}%</p>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-3">
                <div class="bg-gradient-to-r from-primary-500 to-cyan-500 h-2 rounded-full transition-all" style="width: {{ $participationRate }}%"></div>
            </div>
        </div>
    </div>
    @endif
</div>
