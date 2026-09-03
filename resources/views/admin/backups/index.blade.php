<x-layouts.admin title="Backup Database">
    <div class="flex justify-between items-center mb-6">
        <div>
            <p class="text-sm text-gray-500">Buat, unduh, dan hapus backup database</p>
        </div>
        <form action="{{ route('admin.backups.create') }}" method="POST">
            @csrf
            <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-colors shadow-sm">
                <i data-lucide="plus" class="w-4 h-4"></i>
                Buat Backup Sekarang
            </button>
        </form>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama File</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Tanggal</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Ukuran</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($backups as $backup)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                                        <i data-lucide="hard-drive" class="w-5 h-5 text-emerald-600"></i>
                                    </div>
                                    <span class="font-semibold text-gray-800 text-sm">{{ $backup['name'] }}</span>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                {{ \Carbon\Carbon::createFromTimestamp($backup['date'])->translatedFormat('d M Y, H:i') }}
                            </td>
                            <td class="px-6 py-4 text-sm font-medium text-gray-700">
                                {{ round($backup['size'] / 1024, 2) }} KB
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.backups.download', $backup['name']) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-blue-700 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors ring-1 ring-blue-600/20">
                                        <i data-lucide="download" class="w-3.5 h-3.5"></i>
                                        Unduh
                                    </a>
                                    <form action="{{ route('admin.backups.destroy', $backup['name']) }}" method="POST" onsubmit="return confirm('Yakin ingin menghapus backup ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-700 bg-red-50 rounded-lg hover:bg-red-100 transition-colors ring-1 ring-red-600/20">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <i data-lucide="hard-drive" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada backup.</p>
                                <p class="text-gray-400 text-xs mt-1">Klik "Buat Backup Sekarang" untuk membuat backup pertama.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</x-layouts.admin>
