<x-layouts.admin title="Hasil Pemilihan">
    <div class="flex justify-between items-center mb-4">
        <div>
            <p class="text-sm text-gray-500">Lihat hasil dan export data pemilihan — publish terpisah per organisasi via status CLOSED</p>
        </div>
    </div>
    <form method="GET" class="bg-white rounded-2xl shadow-sm border p-4 mb-4 flex gap-3 flex-wrap">
        <select name="voting_event_id" class="px-3 py-2 border rounded-xl text-sm bg-gray-50"><option value="">Semua Event</option>@foreach($votingEvents as $ev)<option value="{{ $ev->id }}" @selected(request('voting_event_id')==$ev->id)>{{ $ev->name }}</option>@endforeach</select>
        <select name="organization_id" class="px-3 py-2 border rounded-xl text-sm bg-gray-50"><option value="">Semua Organisasi</option>@foreach($organizations as $org)<option value="{{ $org->id }}" @selected(request('organization_id')==$org->id)>{{ $org->name }}</option>@endforeach</select>
        <button class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
        <a href="{{ route('admin.results.index') }}" class="px-4 py-2 border rounded-xl text-sm hover:bg-gray-50">Reset</a>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Event / Organisasi</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Suara</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Partisipasi</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($elections as $election)
                        @php
                            $stats = $election->ballots()->count();
                            $eligible = $election->eligibilities()->count();
                            $voted = $election->eligibilities()->where('status', 'VOTED')->count();
                            $turnout = $eligible > 0 ? round(($voted / $eligible) * 100, 1) : 0;
                        @endphp
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-semibold text-gray-800">{{ $election->name }}</td>
                            <td class="px-6 py-4 text-xs"><div class="text-gray-700">{{ $election->votingEvent?->name ?? '—' }}</div><div class="text-gray-400">{{ $election->organization?->name ?? 'Tanpa Organisasi' }}</div></td>
                            <td class="px-6 py-4">
                                @php
                                    $statusColors = [
                                        'CLOSED' => 'bg-amber-50 text-amber-700 ring-amber-600/20',
                                        'ARCHIVED' => 'bg-purple-50 text-purple-700 ring-purple-600/20',
                                    ];
                                @endphp
                                @php $statusVal = $election->status->value; @endphp
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $statusColors[$statusVal] ?? 'bg-gray-50 text-gray-600' }}">
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
                            <td class="px-6 py-4 text-sm font-semibold text-gray-800">{{ $stats }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <div class="w-16 bg-gray-200 rounded-full h-1.5">
                                        <div class="bg-blue-500 h-1.5 rounded-full" style="width: {{ $turnout }}%"></div>
                                    </div>
                                    <span class="text-xs font-medium text-gray-600">{{ $turnout }}%</span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.results.show', $election) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-primary-700 bg-primary-50 rounded-lg hover:bg-primary-100 transition-colors ring-1 ring-primary-600/20">
                                        <i data-lucide="bar-chart-3" class="w-3.5 h-3.5"></i>
                                        Lihat
                                    </a>
                                    <a href="{{ route('admin.results.export', $election) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors ring-1 ring-emerald-600/20">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        Export
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <i data-lucide="bar-chart-3" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada pemilihan selesai.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</x-layouts.admin>
