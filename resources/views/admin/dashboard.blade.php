<x-layouts.admin title="Dashboard">
    {{-- P3-02: peringatan anomali (threshold config/monitoring.php) --}}
    @php
        $anomalyAlerts = app(\App\Domain\Monitoring\Services\AnomalyService::class)->check();
    @endphp
    @if ($anomalyAlerts->isNotEmpty())
        <div class="space-y-3 mb-6">
            @foreach ($anomalyAlerts as $alert)
                <div class="flex items-start gap-3 px-4 py-3 rounded-2xl border text-sm {{ $alert['level'] === 'danger' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                    <span class="font-bold">⚠ {{ $alert['title'] }}</span>
                    <span class="text-current/80">{{ $alert['detail'] }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <livewire:admin.dashboard-stats />

    <!-- Realtime Monitoring Section -->
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <i data-lucide="radio" class="w-5 h-5 text-primary-500"></i>
            <h3 class="text-lg font-bold text-gray-800">Realtime Monitoring</h3>
        </div>
        <livewire:admin.realtime-monitor />
    </div>

    <!-- Charts Section -->
    @php
        $stats = app(\App\Livewire\Admin\DashboardStats::class);
        $stats->refreshStats();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Election Status Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="pie-chart" class="w-5 h-5 text-gray-400"></i>
                <h3 class="font-semibold text-gray-800">Status Pemilihan</h3>
            </div>
            <canvas id="electionStatusChart" height="200"></canvas>
        </div>

        <!-- Participation by Class Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <div class="flex items-center gap-2 mb-4">
                <i data-lucide="bar-chart-3" class="w-5 h-5 text-gray-400"></i>
                <h3 class="font-semibold text-gray-800">Partisipasi per Kelas (Top 10)</h3>
            </div>
            <canvas id="participationByClassChart" height="200"></canvas>
        </div>
    </div>

    <!-- Recent Elections -->
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <i data-lucide="clock" class="w-5 h-5 text-gray-400"></i>
                <h3 class="font-semibold text-gray-800">Pemilihan Terbaru</h3>
            </div>
            <a href="{{ route('admin.elections.index') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">Lihat Semua &rarr;</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse (\App\Models\Election::latest()->take(5)->get() as $election)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.elections.show', $election) }}" class="font-medium text-gray-800 hover:text-primary-600 transition-colors">{{ $election->name }}</a>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'OPEN' => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
                                        'CLOSED' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'DRAFT' => 'bg-gray-50 text-gray-600 ring-gray-500/20',
                                        'SCHEDULED' => 'bg-blue-50 text-blue-700 ring-blue-600/20',
                                        'ARCHIVED' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                    ];
                                @endphp
                                @php $statusVal = $election->status->value; @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $statusColors[$statusVal] ?? 'bg-gray-50 text-gray-600' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $statusVal === 'OPEN' ? 'bg-emerald-500' : ($statusVal === 'CLOSED' ? 'bg-amber-500' : 'bg-gray-400') }}"></span>
                                    {{ $statusVal }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if ($election->starts_at && $election->ends_at)
                                    {{ $election->starts_at->format('d/m/Y') }} - {{ $election->ends_at->format('d/m/Y') }}
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-6 py-12 text-center">
                                <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada pemilihan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        lucide.createIcons();

        // Election Status Chart
        @if(!empty($stats->electionsByStatus))
        const statusCtx = document.getElementById('electionStatusChart');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: {!! json_encode(array_keys($stats->electionsByStatus)) !!},
                datasets: [{
                    data: {!! json_encode(array_values($stats->electionsByStatus)) !!},
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                        'rgba(168, 85, 247, 0.8)',
                    ],
                    borderWidth: 2,
                    borderColor: '#fff',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
        @endif

        // Participation by Class Chart
        @if(!empty($stats->participationByClass))
        const classCtx = document.getElementById('participationByClassChart');
        new Chart(classCtx, {
            type: 'bar',
            data: {
                labels: {!! json_encode(array_column($stats->participationByClass, 'class')) !!},
                datasets: [{
                    label: 'Sudah Memilih',
                    data: {!! json_encode(array_column($stats->participationByClass, 'voted')) !!},
                    backgroundColor: 'rgba(16, 185, 129, 0.8)',
                    borderRadius: 6,
                }, {
                    label: 'Total Pemilih',
                    data: {!! json_encode(array_column($stats->participationByClass, 'total')) !!},
                    backgroundColor: 'rgba(59, 130, 246, 0.3)',
                    borderRadius: 6,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12 }
                        }
                    }
                }
            }
        });
        @endif
    </script>

    <!-- Laravel Echo for Realtime Updates -->
    @vite(['resources/js/app.js'])
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const activeElections = @json(\App\Models\Election::where('status', 'OPEN')->pluck('id'));
            
            if (!window.Echo) {
                console.warn('Laravel Echo not available. Reverb may not be running.');
                return;
            }

            activeElections.forEach(electionId => {
                // P2-06: private channel ber-auth (hanya admin/operator).
                window.Echo.private(`election.${electionId}.monitoring`)
                    .listen('.vote.casted', (event) => {
                        console.log('Vote casted:', event);
                        
                        // Dispatch Livewire browser event to refresh components
                        if (window.Livewire) {
                            window.Livewire.dispatch('vote-casted');
                        }
                        
                        // Show toast notification
                        const toast = document.createElement('div');
                        toast.className = 'fixed bottom-4 right-4 bg-emerald-500 text-white px-5 py-3 rounded-xl shadow-2xl z-50 transition-all transform translate-y-0 opacity-100';
                        toast.style.maxWidth = '320px';
                        toast.innerHTML = `
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-white/20 rounded-full flex items-center justify-center flex-shrink-0">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                </div>
                                <div>
                                    <p class="font-semibold text-sm">Suara baru masuk!</p>
                                    <p class="text-xs text-emerald-100">Partisipasi: ${event.participation_rate}%</p>
                                </div>
                            </div>
                        `;
                        document.body.appendChild(toast);
                        
                        setTimeout(() => {
                            toast.style.transform = 'translateY(20px)';
                            toast.style.opacity = '0';
                            setTimeout(() => toast.remove(), 300);
                        }, 4000);
                    });
            });
        });
    </script>
</x-layouts.admin>
