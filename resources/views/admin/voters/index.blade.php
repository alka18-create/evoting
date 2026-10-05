<x-layouts.admin title="Pemilih">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
            <p class="text-sm text-gray-500">Kelola data pemilih</p>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('admin.voters.export') }}" class="inline-flex items-center gap-2 bg-emerald-600 border border-emerald-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-all">
                <i data-lucide="file-spreadsheet" class="w-4 h-4"></i>
                Export Excel
            </a>
            <button id="openImportModal" class="inline-flex items-center gap-2 bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-gray-50 transition-all">
                <i data-lucide="upload" class="w-4 h-4"></i>
                Import Excel
            </button>
            @can('deleteAny', App\Models\Voter::class)
                @if (request('class_name'))
                    <a href="{{ route('admin.voters.delete-class-confirm', ['class_name' => request('class_name')]) }}"
                        class="inline-flex items-center gap-2 bg-red-600 border border-red-600 text-white px-4 py-2.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition-all">
                        <i data-lucide="trash-2" class="w-4 h-4"></i>
                        Hapus Kelas "{{ request('class_name') }}"
                    </a>
                @endif
            @endcan
            <a href="{{ route('admin.voters.create') }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                <i data-lucide="user-plus" class="w-4 h-4"></i>
                Tambah Pemilih
            </a>
        </div>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
        <form method="GET" action="{{ route('admin.voters.index') }}" class="flex flex-col sm:flex-row sm:items-center gap-3">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <i data-lucide="search" class="w-[18px] h-[18px] text-gray-400"></i>
                </div>
                <label for="voter-search" class="sr-only">Cari pemilih</label>
                <input id="voter-search" type="text" name="search" value="{{ request('search') }}"
                    class="w-full pl-11 pr-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                    placeholder="Cari nama, NIS, atau kelas...">
            </div>
            <label for="voter-status" class="sr-only">Filter status</label>
            <select id="voter-status" name="status" class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                <option value="">Semua Status</option>
                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
            </select>
            <label for="voter-class" class="sr-only">Filter kelas</label>
            <select id="voter-class" name="class_name" class="px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                <option value="">Semua Kelas</option>
                @foreach ($classes as $class)
                    <option value="{{ $class }}" {{ request('class_name') === $class ? 'selected' : '' }}>{{ $class }}</option>
                @endforeach
            </select>
            <button type="submit" class="px-4 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold hover:bg-primary-700 transition-all">Filter</button>
            @if (request('search') || request('status') || request('class_name'))
                <a href="{{ route('admin.voters.index') }}" class="px-4 py-2.5 text-gray-600 hover:bg-gray-100 rounded-xl text-sm font-medium transition-all">Reset</a>
            @endif
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full min-w-[720px]">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">NIS</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kelas</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($voters as $voter)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 font-mono text-sm text-gray-600">{{ $voter->student_id }}</td>
                            <td class="px-6 py-4">
                                <a href="{{ route('admin.voters.show', $voter) }}" class="font-semibold text-gray-800 hover:text-primary-600 transition-colors">{{ $voter->name }}</a>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $voter->class_name }}</td>
                            <td class="px-6 py-4">
                                @if ($voter->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-emerald-50 text-emerald-700 ring-emerald-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset bg-red-50 text-red-700 ring-red-600/20">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1 whitespace-nowrap">
                                    <a href="{{ route('admin.voters.edit', $voter) }}" aria-label="Edit {{ $voter->name }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors" title="Edit">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                    </a>
                                    <form method="POST" action="{{ route('admin.voters.toggle-active', $voter) }}" class="inline">
                                        @csrf
                                        <button type="submit" aria-label="{{ $voter->is_active ? 'Nonaktifkan' : 'Aktifkan' }} {{ $voter->name }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold rounded-lg transition-colors {{ $voter->is_active ? 'text-red-600 hover:bg-red-50' : 'text-emerald-600 hover:bg-emerald-50' }}" title="{{ $voter->is_active ? 'Nonaktifkan' : 'Aktifkan' }}">
                                            <i data-lucide="{{ $voter->is_active ? 'user-x' : 'user-check' }}" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    @can('delete', $voter)
                                    <form method="POST" action="{{ route('admin.voters.destroy', $voter) }}" class="inline" onsubmit="return confirm('Yakin hapus pemilih ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" aria-label="Hapus {{ $voter->name }}" class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                        </button>
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <i data-lucide="users" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada pemilih.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $voters->links() }}</div>

    <div id="importModal" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm items-center justify-center z-50" role="dialog" aria-modal="true" aria-labelledby="importTitle">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-md mx-4">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="upload" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <h3 id="importTitle" class="font-semibold text-gray-800">Import Pemilih</h3>
                    <p class="text-xs text-gray-500">Upload file Excel</p>
                </div>
            </div>
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="upload" class="w-5 h-5 text-emerald-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Import Pemilih</h3>
                    <p class="text-xs text-gray-500">Upload file CSV</p>
                </div>
            </div>
            <form method="POST" action="{{ route('admin.voters.import') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-gray-700">Format Excel (.xlsx):</label>
                        <a href="{{ route('admin.voters.template') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-primary-600 hover:text-primary-700 transition-colors">
                            <i data-lucide="download" class="w-3.5 h-3.5"></i> Unduh Template
                        </a>
                    </div>
                    <code class="text-xs bg-gray-50 text-gray-600 p-3 block rounded-xl border border-gray-200 font-mono">student_id,name,class_name</code>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">File Excel</label>
                    <input type="file" name="excel_file" accept=".xlsx,.xls,.csv" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                </div>
                <div class="flex items-center justify-end gap-3 pt-2">
                    <button type="button" id="closeImportModal" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors">Batal</button>
                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-600 to-emerald-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:from-emerald-700 hover:to-emerald-800 transition-all shadow-lg shadow-emerald-500/25">
                        <i data-lucide="upload" class="w-4 h-4"></i> Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
            if (window.lucide && window.lucide.icons) window.lucide.createIcons({ icons: window.lucide.icons });
        (function(){
            const modal = document.getElementById('importModal');
            const openBtn = document.getElementById('openImportModal');
            const closeBtn = document.getElementById('closeImportModal');
            function openModal(){ modal.classList.remove('hidden'); modal.classList.add('flex'); }
            function closeModal(){ modal.classList.add('hidden'); modal.classList.remove('flex'); }
            openBtn.addEventListener('click', openModal);
            closeBtn.addEventListener('click', closeModal);
            modal.addEventListener('click', function(e){ if (e.target === modal) closeModal(); });
            document.addEventListener('keydown', function(e){ if (e.key === 'Escape' && !modal.classList.contains('hidden')) closeModal(); });
        })();
    </script>
</x-layouts.admin>
