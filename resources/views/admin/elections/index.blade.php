<x-layouts.admin title="Pemilihan">
    <div class="flex flex-col gap-4 mb-6">
        <div class="flex justify-between items-center">
            <div>
                <p class="text-sm text-gray-500">Kelola semua pemilihan — terhubung ke Event & Organisasi</p>
                @if(request('voting_event_id'))<p class="text-xs text-primary-600 mt-1">Filter: Event = {{ $votingEvents->firstWhere('id', request('voting_event_id'))?->name ?? request('voting_event_id') }} <a href="{{ route('admin.elections.index') }}" class="underline">Reset</a></p>@endif
            </div>
            <a href="{{ route('admin.elections.create', request('voting_event_id') ? ['voting_event_id'=>request('voting_event_id')] : []) }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Buat Pemilihan
            </a>
        </div>
        <form method="GET" class="bg-white rounded-2xl shadow-sm border p-4 flex gap-3 flex-wrap">
            <select name="voting_event_id" class="px-3 py-2 border rounded-xl text-sm bg-gray-50"><option value="">Semua Event</option>@foreach($votingEvents as $ev)<option value="{{ $ev->id }}" @selected(request('voting_event_id')==$ev->id)>{{ $ev->name }}</option>@endforeach</select>
            <select name="organization_id" class="px-3 py-2 border rounded-xl text-sm bg-gray-50"><option value="">Semua Organisasi</option>@foreach($organizations as $org)<option value="{{ $org->id }}" @selected(request('organization_id')==$org->id)>{{ $org->name }}</option>@endforeach</select>
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama..." class="px-3 py-2 border rounded-xl text-sm bg-gray-50">
            <button class="bg-primary-600 text-white px-4 py-2 rounded-xl text-sm">Filter</button>
            <a href="{{ route('admin.elections.index') }}" class="px-4 py-2 border rounded-xl text-sm hover:bg-gray-50">Reset</a>
        </form>
    </div>

    @if ($errors->any())
        <div class="mb-4 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
            <div>
                @foreach ($errors->all() as $error)
                    <p class="text-sm">{{ $error }}</p>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Event / Organisasi</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Periode</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($elections as $election)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.elections.show', $election) }}" class="font-semibold text-gray-800 hover:text-primary-600 transition-colors">
                                    {{ $election->name }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-xs">
                                <div class="text-gray-700">
                                    @if($election->votingEvent)<a href="{{ route('admin.elections.index', ['voting_event_id'=>$election->voting_event_id]) }}" class="text-primary-600 hover:underline">{{ $election->votingEvent->name }}</a>@else<span class="text-gray-400">—</span>@endif
                                </div>
                                <div class="text-gray-400">
                                    @if($election->organization)<a href="{{ route('admin.elections.index', ['organization_id'=>$election->organization_id]) }}" class="hover:underline">{{ $election->organization->name }}</a>@else<span class="text-gray-400">Tanpa Organisasi</span>@endif
                                </div>
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
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                @if ($election->starts_at && $election->ends_at)
                                    {{ $election->starts_at->format('d/m/Y') }} - {{ $election->ends_at->format('d/m/Y') }}
                                @else
                                    <span class="text-gray-400">Belum dijadwalkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1">
                                    @if ($election->status->value === 'DRAFT')
                                        <form method="POST" action="{{ route('admin.elections.schedule', $election) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 rounded-lg hover:bg-amber-100 transition-colors ring-1 ring-amber-600/20" title="Jadwalkan">
                                                <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                                                Jadwalkan
                                            </button>
                                        </form>
                                    @endif

                                    @if ($election->status->value === 'SCHEDULED')
                                        <form method="POST" action="{{ route('admin.elections.open', $election) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition-colors ring-1 ring-emerald-600/20" title="Buka">
                                                <i data-lucide="play" class="w-3.5 h-3.5"></i>
                                                Buka
                                            </button>
                                        </form>
                                    @endif

                                    @if ($election->status->value === 'OPEN')
                                        <form method="POST" action="{{ route('admin.elections.close', $election) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors ring-1 ring-red-600/20" title="Tutup">
                                                <i data-lucide="square" class="w-3.5 h-3.5"></i>
                                                Tutup
                                            </button>
                                        </form>
                                    @endif

                                    @if ($election->status->value === 'CLOSED')
                                        <form method="POST" action="{{ route('admin.elections.archive', $election) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-gray-700 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors ring-1 ring-gray-500/20" title="Arsipkan">
                                                <i data-lucide="archive" class="w-3.5 h-3.5"></i>
                                                Arsip
                                            </button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.elections.edit', $election) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors ring-1 ring-gray-200">
                                        Edit
                                    </a>
                                    <a href="{{ route('admin.elections.candidates.index', $election) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-purple-600 hover:bg-gray-50 rounded-lg transition-colors ring-1 ring-gray-200">
                                        Kandidat
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada pemilihan.</p>
                                <a href="{{ route('admin.elections.create') }}" class="mt-3 inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 font-medium">
                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                    Buat Pemilihan Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $elections->links() }}
    </div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>
