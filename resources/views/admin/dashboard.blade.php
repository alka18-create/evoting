<x-layouts.admin title="Dashboard">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-900">Dashboard Admin</h1>
                <p class="text-gray-500 text-sm mt-1">Pantau pemilihan, partisipasi, dan aktivitas terkini.</p>
            </div>
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.elections.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white text-sm font-semibold rounded-xl hover:bg-primary-700 transition-colors shadow-lg shadow-primary-500/25">
                    <i data-lucide="plus" class="w-4 h-4"></i>
                    Buat Pemilihan
                </a>
                <a href="{{ route('admin.voting-events.create') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-xl hover:bg-gray-50 transition-colors">
                    <i data-lucide="calendar-plus" class="w-4 h-4"></i>
                    Buat Event
                </a>
            </div>
        </div>
    </div>

    {{-- P3-02: peringatan anomali (threshold config/monitoring.php) --}}
    @php
        $anomalyAlerts = app(\App\Domain\Monitoring\Services\AnomalyService::class)->check();
    @endphp
    @if ($anomalyAlerts->isNotEmpty())
        <div class="space-y-3 mb-6">
            @foreach ($anomalyAlerts as $alert)
                <div class="flex items-start gap-3 px-4 py-3 rounded-2xl border text-sm {{ $alert['level'] === 'danger' ? 'bg-red-50 border-red-200 text-red-800' : 'bg-amber-50 border-amber-200 text-amber-800' }}">
                    <i data-lucide="alert-triangle" class="w-5 h-5 flex-shrink-0 mt-0.5"></i>
                    <div>
                        <span class="font-bold block">{{ $alert['title'] }}</span>
                        <span class="text-current/80">{{ $alert['detail'] }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    <livewire:admin.dashboard-stats />

    <!-- Realtime Monitoring Section -->
    <div class="mb-8">
        <div class="flex items-center gap-2 mb-4">
            <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
                <i data-lucide="radio" class="w-4 h-4 text-primary-500"></i>
            </div>
            <h3 class="text-lg font-bold text-gray-800">Realtime Monitoring</h3>
        </div>
        <livewire:admin.realtime-monitor />
    </div>

    <!-- Charts Section -->
    @php
        $stats = app(\App\Livewire\Admin\DashboardStats::class);
        $stats->refreshStats();
    @endphp

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Election Status Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 bg-blue-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="pie-chart" class="w-4 h-4 text-blue-500"></i>
                </div>
                <h3 class="font-semibold text-gray-800">Status Pemilihan</h3>
            </div>
            <canvas id="electionStatusChart" height="200"></canvas>
        </div>

        <!-- Participation by Class Chart -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 hover:shadow-md transition-shadow">
            <div class="flex items-center gap-2 mb-4">
                <div class="w-8 h-8 bg-emerald-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="bar-chart-3" class="w-4 h-4 text-emerald-500"></i>
                </div>
                <h3 class="font-semibold text-gray-800">Partisipasi per Kelas (Top 10)</h3>
            </div>
            <canvas id="participationByClassChart" height="200"></canvas>
        </div>
    </div>

    <!-- Quick Actions & Recent Elections -->
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- Quick Actions -->
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 h-fit">
            <div class="flex items-center gap-2 mb-5">
                <div class="w-8 h-8 bg-primary-50 rounded-lg flex items-center justify-center">
                    <i data-lucide="zap" class="w-4 h-4 text-primary-500"></i>
                </div>
                <h3 class="font-semibold text-gray-800">Aksi Cepat</h3>
            </div>
            <div class="space-y-3">
                <a href="{{ route('admin.voters.create') }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-primary-200 hover:bg-primary-50/30 transition-colors group">
                    <div class="w-10 h-10 bg-primary-100 rounded-lg flex items-center justify-center group-hover:bg-primary-200 transition-colors">
                        <i data-lucide="user-plus" class="w-5 h-5 text-primary-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Tambah Pemilih</p>
                        <p class="text-xs text-gray-500">Daftarkan pemilih baru</p>
                    </div>
                </a>
                <a href="{{ route('admin.tokens.overview') }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-cyan-200 hover:bg-cyan-50/30 transition-colors group">
                    <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center group-hover:bg-cyan-200 transition-colors">
                        <i data-lucide="key-round" class="w-5 h-5 text-cyan-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Kelola Token</p>
                        <p class="text-xs text-gray-500">Cetak dan distribusikan token</p>
                    </div>
                </a>
                <a href="{{ route('admin.results.index') }}" class="flex items-center gap-3 p-3 rounded-xl border border-gray-100 hover:border-emerald-200 hover:bg-emerald-50/30 transition-colors group">
                    <div class="w-10 h-10 bg-emerald-100 rounded-lg flex items-center justify-center group-hover:bg-emerald-200 transition-colors">
                        <i data-lucide="trophy" class="w-5 h-5 text-emerald-600"></i>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">Lihat Hasil</p>
                        <p class="text-xs text-gray-500">Pantau hasil pemilihan</p>
                    </div>
                </a>
            </div>
        </div>

        <!-- Recent Elections -->
        <div class="xl:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-gray-50 rounded-lg flex items-center justify-center">
                        <i data-lucide="clock" class="w-4 h-4 text-gray-500"></i>
                    </div>
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
                                    <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-3">
                                        <i data-lucide="inbox" class="w-8 h-8 text-gray-300"></i>
                                    </div>
                                    <p class="text-gray-500 text-sm">Belum ada pemilihan.</p>
                                    <a href="{{ route('admin.elections.create') }}" class="inline-flex items-center gap-1 mt-3 text-sm text-primary-600 hover:text-primary-700 font-medium">
                                        <i data-lucide="plus" class="w-4 h-4"></i>
                                        Buat pemilihan pertama
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Chart.js + Echo dimuat dari bundle Vite lokal (resources/js/app.js). --}}
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide && window.lucide.icons) window.lucide.createIcons({ icons: window.lucide.icons });
        if (typeof Chart === 'undefined') return;

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
                        'rgba(6, 182, 212, 0.8)',
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
    });
    </script>

    <!-- Realtime via bundle lokal (Echo/Pusher dari resources/js/app.js). -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Init Echo di sini (bundle module selalu jalan sebelum DOMContentLoaded).
            try {
                if (typeof Echo !== 'undefined' && !window.Echo?.connector) {
                    window.Echo = new Echo({
                        broadcaster: 'reverb',
                        key: '{{ config('reverb.apps.0.key') }}',
                        wsHost: '{{ config('reverb.apps.0.options.host') }}',
                        wsPort: {{ (int) config('reverb.apps.0.options.port', 8080) }},
                        wssPort: {{ (int) config('reverb.apps.0.options.port', 8080) }},
                        forceTLS: '{{ config('reverb.apps.0.options.scheme') }}' === 'https',
                        enabledTransports: ['ws', 'wss'],
                    });
                }
            } catch (e) {
                console.warn('Echo init gagal:', e);
            }

            const activeElections = @json(\App\Models\Election::where('status', 'OPEN')->pluck('id'));

            if (!window.Echo || typeof window.Echo.private !== 'function') {
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
