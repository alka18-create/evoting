<div wire:poll.3s="refreshData" class="space-y-6">
    <!-- Connection Status -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
            <span class="relative flex h-3 w-3">
                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                <span class="relative inline-flex rounded-full h-3 w-3 bg-emerald-500"></span>
            </span>
            <span class="text-sm font-semibold text-emerald-700">LIVE</span>
            <span class="text-xs text-gray-400">Terakhir update: {{ $lastUpdate }}</span>
        </div>
        <button wire:click="refreshData" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-gray-600 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
            Refresh
        </button>
    </div>

    <!-- Big Participation Number -->
    <div class="bg-gradient-to-r from-primary-600 to-primary-700 rounded-2xl p-6 text-white shadow-lg shadow-primary-500/25">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-primary-100 text-sm font-medium mb-1">Total Partisipasi</p>
                <div class="flex items-baseline gap-2">
                    <p class="text-5xl font-bold">{{ $participationRate }}%</p>
                    <p class="text-primary-200 text-sm">{{ $totalVoted }} / {{ $totalEligible }} pemilih</p>
                </div>
            </div>
            <div class="w-20 h-20 bg-white/10 rounded-full flex items-center justify-center backdrop-blur-sm">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path></svg>
            </div>
        </div>
        <div class="w-full bg-white/20 rounded-full h-3 mt-4">
            <div class="bg-white h-3 rounded-full transition-all duration-500" style="width: {{ $participationRate }}%"></div>
        </div>
    </div>

    <!-- Per Organization Stats (simple & clear) -->
    @if(count($perOrganization) > 0)
    <div>
        <h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center gap-2"><i data-lucide="building-2" class="w-4 h-4 text-gray-400"></i> Per Organisasi</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @foreach($perOrganization as $org)
            <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-2">
                    <h5 class="font-semibold text-gray-800 text-sm truncate">{{ $org['name'] }}</h5>
                    <span class="text-xs font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">{{ $org['rate'] }}%</span>
                </div>
                <p class="text-xs text-gray-400 mb-3">{{ $org['election_count'] }} pemilihan</p>
                <div class="grid grid-cols-3 gap-2 text-center text-xs mb-3">
                    <div><p class="text-gray-400">Eligible</p><p class="font-bold text-gray-700">{{ $org['total_eligible'] }}</p></div>
                    <div><p class="text-gray-400">Sudah</p><p class="font-bold text-emerald-600">{{ $org['total_voted'] }}</p></div>
                    <div><p class="text-gray-400">Belum</p><p class="font-bold text-amber-600">{{ $org['total_pending'] }}</p></div>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="bg-primary-500 h-2 rounded-full transition-all duration-500" style="width: {{ $org['rate'] }}%"></div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Per Election Stats -->
    @if(count($perElection) > 0)
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($perElection as $election)
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100">
            <div class="flex items-center justify-between mb-3">
                <div class="flex-1 min-w-0"><h4 class="font-semibold text-gray-800 text-sm truncate">{{ $election['name'] }}</h4><p class="text-xs text-gray-400 truncate">{{ $election['org'] }}</p></div>
                <span class="text-xs font-bold text-primary-600 bg-primary-50 px-2 py-0.5 rounded-full flex-shrink-0">{{ $election['rate'] }}%</span>
            </div>
            <div class="flex items-center justify-between text-sm mb-2">
                <span class="text-gray-500">Sudah memilih</span>
                <span class="font-bold text-emerald-600">{{ $election['total_votes'] }}</span>
            </div>
            <div class="flex items-center justify-between text-sm mb-3">
                <span class="text-gray-500">Total pemilih</span>
                <span class="font-bold text-gray-700">{{ $election['total_eligible'] }}</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="bg-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $election['rate'] }}%"></div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    <!-- Recent Votes Feed -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <h3 class="font-semibold text-gray-800">Suara Masuk Terbaru</h3>
        </div>
        <div class="divide-y divide-gray-50">
            @forelse($recentVotes as $vote)
            <div class="px-6 py-3 flex items-center justify-between hover:bg-gray-50/50 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 bg-emerald-50 rounded-full flex items-center justify-center">
                        <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-800">{{ $vote['candidate'] }}</p>
                        <p class="text-xs text-gray-400">{{ $vote['election'] }}</p>
                    </div>
                </div>
                <span class="text-xs text-gray-400 font-mono">{{ $vote['time'] }}</span>
            </div>
            @empty
            <div class="px-6 py-8 text-center">
                <svg class="w-8 h-8 text-gray-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                <p class="text-gray-400 text-sm">Belum ada suara masuk</p>
            </div>
            @endforelse
        </div>
    </div>
</div>
