<x-layouts.admin title="Kandidat - {{ $election->name }}">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-6">
        <div>
            <p class="text-sm text-gray-500">Kelola kandidat pemilihan</p>
        </div>
        <a href="{{ route('admin.elections.candidates.create', $election) }}" class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
            <i data-lucide="user-plus" class="w-4 h-4"></i>
            Tambah Kandidat
        </a>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full min-w-[720px]">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Nama</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Visi</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($candidates as $candidate)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center justify-center w-8 h-8 bg-primary-100 text-primary-700 rounded-lg text-sm font-bold">
                                    {{ $candidate->candidate_number }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    @if ($candidate->photo_path)
                                        <div class="flex -space-x-2 flex-shrink-0">
                                            <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 ring-2 ring-white">
                                                <img src="{{ Storage::url($candidate->photo_path) }}" alt="{{ $candidate->name }}" class="w-full h-full object-cover">
                                            </div>
                                            @if ($candidate->isPair() && $candidate->running_mate_photo_path)
                                                <div class="w-10 h-10 rounded-lg overflow-hidden bg-gray-100 ring-2 ring-white">
                                                    <img src="{{ Storage::url($candidate->running_mate_photo_path) }}" alt="{{ $candidate->running_mate_name }}" class="w-full h-full object-cover">
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center flex-shrink-0">
                                            <span class="text-sm font-bold text-primary-700">{{ substr($candidate->name, 0, 1) }}</span>
                                        </div>
                                    @endif
                                    @if ($candidate->isPair())
                                        <div>
                                            <span class="font-semibold text-gray-800">{{ $candidate->name }}</span>
                                            <span class="text-gray-400"> &amp; </span>
                                            <span class="font-semibold text-gray-700">{{ $candidate->running_mate_name }}</span>
                                            <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase tracking-wide bg-cyan-50 text-cyan-700 border border-cyan-100">Berpasangan</span>
                                        </div>
                                    @else
                                        <span class="font-semibold text-gray-800">{{ $candidate->name }}</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ Str::limit($candidate->vision, 60) ?: '-' }}</td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('admin.elections.candidates.edit', [$election, $candidate]) }}" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors">
                                        <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.elections.candidates.destroy', [$election, $candidate]) }}" class="inline" onsubmit="return confirm('Yakin hapus kandidat ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg transition-colors">
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
                                <i data-lucide="users" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada kandidat.</p>
                                <a href="{{ route('admin.elections.candidates.create', $election) }}" class="mt-3 inline-flex items-center gap-1 text-sm text-primary-600 hover:text-primary-700 font-medium">
                                    <i data-lucide="plus" class="w-4 h-4"></i>
                                    Tambah Kandidat Pertama
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $candidates->links() }}
        <a href="{{ route('admin.elections.show', $election) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600 transition-colors mt-2">
            <i data-lucide="arrow-left" class="w-4 h-4"></i>
            Kembali ke Detail
        </a>
    </div>

</x-layouts.admin>
