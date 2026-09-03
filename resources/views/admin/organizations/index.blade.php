<x-layouts.admin title="Organisasi">
    <div class="flex items-center justify-between mb-6">
        <div><h3 class="text-lg font-semibold text-gray-800">Organisasi</h3><p class="text-sm text-gray-500">Kelola organisasi untuk pengelompokan pemilihan</p></div>
        <a href="{{ route('admin.organizations.create') }}" class="inline-flex items-center gap-2 bg-primary-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:bg-primary-700">+ Tambah Organisasi</a>
    </div>
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-500 uppercase text-xs"><tr><th class="px-4 py-3 text-left">Nama</th><th class="px-4 py-3 text-left">Slug</th><th class="px-4 py-3 text-center">Pemilihan</th><th class="px-4 py-3 text-right">Aksi</th></tr></thead>
            <tbody>
            @forelse($organizations as $org)
                <tr class="border-t hover:bg-gray-50"><td class="px-4 py-3 font-medium">{{ $org->name }}</td><td class="px-4 py-3 text-gray-500">{{ $org->slug }}</td><td class="px-4 py-3 text-center"><span class="bg-primary-50 text-primary-700 px-2 py-1 rounded-lg text-xs">{{ $org->elections_count }}</span></td>
                <td class="px-4 py-3 text-right flex justify-end gap-2"><a href="{{ route('admin.organizations.edit', $org) }}" class="text-primary-600 hover:underline">Edit</a><form method="POST" action="{{ route('admin.organizations.destroy', $org) }}" onsubmit="return confirm('Hapus organisasi?')">@csrf @method('DELETE')<button class="text-red-600 hover:underline">Hapus</button></form></td></tr>
            @empty
                <tr><td colspan="4" class="px-4 py-8 text-center text-gray-400">Belum ada organisasi</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-4">{{ $organizations->links() }}</div>
    </div>
    <script>lucide.createIcons();</script>
</x-layouts.admin>
