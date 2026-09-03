<div wire:poll.5s="refreshStats">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl p-5 text-white shadow-lg shadow-blue-500/25">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Pemilihan</p>
                    <p class="text-3xl font-bold mt-1">{{ $totalElections }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                    <i data-lucide="vote" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-2xl p-5 text-white shadow-lg shadow-emerald-500/25">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-emerald-100 text-sm font-medium">Pemilihan Aktif</p>
                    <p class="text-3xl font-bold mt-1">{{ $activeElections }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                    <i data-lucide="activity" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl p-5 text-white shadow-lg shadow-purple-500/25">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Total Pemilih</p>
                    <p class="text-3xl font-bold mt-1">{{ $totalVoters }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                    <i data-lucide="users" class="w-6 h-6"></i>
                </div>
            </div>
        </div>

        <div class="bg-gradient-to-br from-amber-500 to-amber-600 rounded-2xl p-5 text-white shadow-lg shadow-amber-500/25">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-amber-100 text-sm font-medium">Total Suara</p>
                    <p class="text-3xl font-bold mt-1">{{ $totalVotes }}</p>
                </div>
                <div class="w-12 h-12 bg-white/20 rounded-2xl flex items-center justify-center backdrop-blur-sm">
                    <i data-lucide="check-circle" class="w-6 h-6"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Participation Stats -->
    @if($totalEligible > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Sudah Memilih</p>
                <i data-lucide="user-check" class="w-5 h-5 text-emerald-500"></i>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ $totalVoted }}</p>
            <p class="text-xs text-gray-400 mt-1">dari {{ $totalEligible }} hak pilih</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Belum Memilih</p>
                <i data-lucide="user-x" class="w-5 h-5 text-gray-400"></i>
            </div>
            <p class="text-2xl font-bold text-gray-800">{{ $totalNotVoted }}</p>
            <p class="text-xs text-gray-400 mt-1">dari {{ $totalEligible }} hak pilih</p>
        </div>

        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-gray-500">Partisipasi</p>
                <i data-lucide="trending-up" class="w-5 h-5 text-primary-500"></i>
            </div>
            <p class="text-2xl font-bold text-primary-600">{{ $participationRate }}%</p>
            <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                <div class="bg-primary-600 h-2 rounded-full transition-all" style="width: {{ $participationRate }}%"></div>
            </div>
        </div>
    </div>
    @endif

    <script>lucide.createIcons();</script>
</div>
