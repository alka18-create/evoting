<x-layouts.admin title="Hasil: {{ $election->name }}">
    <div class="flex items-center justify-between mb-6">
        <div>
            <p class="text-sm text-gray-500">{{ $election->description ?? 'Detail hasil pemilihan' }}</p>
            <div class="flex gap-2 mt-2">
                @if($election->votingEvent)<span class="text-xs bg-blue-50 text-blue-700 px-2 py-1 rounded-lg border">Event: {{ $election->votingEvent->name }}</span>@endif
                @if($election->organization)<span class="text-xs bg-emerald-50 text-emerald-700 px-2 py-1 rounded-lg border">Organisasi: {{ $election->organization->name }}</span>@endif
                <span class="text-xs bg-gray-50 text-gray-600 px-2 py-1 rounded-lg border">Status: {{ $election->status->label() }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.results.export-excel', $election) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-emerald-600 border border-emerald-600 rounded-xl hover:bg-emerald-700 transition-all">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                Export Excel
            </a>
            <a href="{{ route('admin.results.export-pdf', $election) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-red-600 border border-red-600 rounded-xl hover:bg-red-700 transition-all">
                <i data-lucide="file-text" class="w-4 h-4"></i>
                Export PDF
            </a>
            <a href="{{ route('admin.results.export', $election) }}" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                <i data-lucide="download" class="w-4 h-4"></i>
                Export CSV
            </a>
            <a href="{{ route('admin.results.index') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700 transition-colors">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Kembali
            </a>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="vote" class="w-5 h-5 text-blue-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Total Suara</p>
                    <p class="text-xl font-bold text-gray-800">{{ $results['total_votes'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="users" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Hak Pilih</p>
                    <p class="text-xl font-bold text-gray-800">{{ $results['total_eligible'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Sudah Vote</p>
                    <p class="text-xl font-bold text-gray-800">{{ $results['total_voted'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="percent" class="w-5 h-5 text-purple-600"></i>
                </div>
                <div>
                    <p class="text-xs text-gray-500">Partisipasi</p>
                    <p class="text-xl font-bold text-gray-800">{{ $results['turnout'] }}%</p>
                </div>
            </div>
        </div>
    </div>

    @if ($results['winner'])
        <div class="bg-gradient-to-r from-amber-50 to-yellow-50 border border-amber-200 rounded-2xl p-6 mb-6">
            <div class="flex items-center gap-4">
                <div class="w-16 h-16 bg-amber-100 rounded-2xl flex items-center justify-center">
                    <i data-lucide="trophy" class="w-8 h-8 text-amber-600"></i>
                </div>
                <div>
                    <p class="text-sm font-medium text-amber-700 mb-1">Pemenang</p>
                    <p class="text-2xl font-bold text-gray-800">
                        <span class="text-amber-600">No. {{ $results['winner']['candidate_number'] }}</span>
                        &mdash; {{ $results['winner']['candidate_name'] }}
                    </p>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $results['winner']['vote_count'] }} suara ({{ $results['winner']['percentage'] }}%)
                    </p>
                </div>
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6">
        <h3 class="font-semibold text-gray-800 mb-4">Grafik Hasil</h3>
        <canvas id="resultChart" height="300"></canvas>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h3 class="font-semibold text-gray-800">Detail Hasil</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kandidat</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Suara</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Persentase</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($results['results'] as $index => $result)
                        <tr class="hover:bg-gray-50/50 transition-colors {{ $results['winner'] && $results['winner']['candidate_id'] === $result['candidate_id'] ? 'bg-amber-50/50' : '' }}">
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if ($index === 0 && $results['winner'])
                                    <span class="inline-flex items-center justify-center w-7 h-7 bg-amber-100 rounded-full text-amber-700 font-bold text-xs">1</span>
                                @else
                                    {{ $index + 1 }}
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($result['candidate_photo'])
                                        <img src="{{ asset('storage/' . $result['candidate_photo']) }}" alt="{{ $result['candidate_name'] }}" class="w-10 h-10 rounded-xl object-cover">
                                    @else
                                        <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400 text-sm font-bold">{{ $result['candidate_number'] }}</div>
                                    @endif
                                    <div>
                                        <p class="font-semibold text-gray-800">{{ $result['candidate_name'] }}</p>
                                        <p class="text-xs text-gray-500">No. {{ $result['candidate_number'] }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $result['vote_count'] }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-32 bg-gray-200 rounded-full h-2">
                                        <div class="bg-primary-500 h-2 rounded-full transition-all" style="width: {{ $result['percentage'] }}%"></div>
                                    </div>
                                    <span class="text-sm font-medium text-gray-600">{{ $result['percentage'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const ctx = document.getElementById('resultChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: @json(collect($results['results'])->pluck('candidate_name')),
                datasets: [{
                    label: 'Jumlah Suara',
                    data: @json(collect($results['results'])->pluck('vote_count')),
                    backgroundColor: [
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(16, 185, 129, 0.8)',
                        'rgba(245, 158, 11, 0.8)',
                        'rgba(239, 68, 68, 0.8)',
                    ],
                    borderRadius: 8,
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 },
                        grid: { color: 'rgba(0,0,0,0.05)' },
                    },
                    x: {
                        grid: { display: false },
                    },
                },
            },
        });
    </script>
</x-layouts.admin>
