<div wire:poll.3s="refreshStats">
    <div class="grid grid-cols-3 gap-4">
        <div class="text-center p-3 bg-blue-50 rounded-xl">
            <p class="text-2xl font-bold text-blue-600">{{ $totalCandidates }}</p>
            <p class="text-xs text-blue-600/70 mt-1">Kandidat</p>
        </div>
        <div class="text-center p-3 bg-emerald-50 rounded-xl">
            <p class="text-2xl font-bold text-emerald-600">{{ $totalEligible }}</p>
            <p class="text-xs text-emerald-600/70 mt-1">Terdaftar</p>
        </div>
        <div class="text-center p-3 bg-purple-50 rounded-xl relative">
            <p class="text-2xl font-bold text-purple-600">{{ $totalVoted }}</p>
            <p class="text-xs text-purple-600/70 mt-1">Vote</p>
            @if ($status === 'OPEN')
                <span class="absolute top-2 right-2 w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
            @endif
        </div>
    </div>
    @if ($totalEligible > 0)
        <div class="mt-3">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                <span>Partisipasi</span>
                <span class="font-medium">{{ $turnout }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-1.5">
                <div class="bg-primary-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $turnout }}%"></div>
            </div>
        </div>
    @endif
</div>
