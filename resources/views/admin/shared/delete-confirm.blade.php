{{--
    Halaman konfirmasi hapus bertingkat (dipakai voter, election, event, org).
    Variabel: $title, $itemType, $itemName, $impacts [label => jumlah],
    $warnings [teks], $action (route), $cancelUrl.
--}}
<x-layouts.admin title="Konfirmasi Hapus">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-2xl shadow-sm border border-red-200 overflow-hidden">
            <div class="bg-red-600 px-6 py-4 flex items-center gap-3">
                <i data-lucide="alert-triangle" class="w-6 h-6 text-white"></i>
                <div>
                    <h3 class="font-bold text-white">{{ $title }}</h3>
                    <p class="text-xs text-red-100">Tindakan ini permanen dan tidak bisa dibatalkan</p>
                </div>
            </div>

            <div class="p-6">
                <p class="text-sm text-gray-600 mb-1">Anda akan menghapus {{ $itemType }}:</p>
                <p class="font-bold text-gray-900 mb-4 break-words">{{ $itemName }}</p>

                <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-4">
                    <p class="text-sm font-semibold text-red-800 mb-2">Data yang ikut terhapus:</p>
                    <ul class="space-y-1">
                        @forelse ($impacts as $label => $count)
                            @if ($count > 0)
                                <li class="flex items-center justify-between text-sm text-red-700">
                                    <span>{{ $label }}</span>
                                    <span class="font-bold">{{ $count }}</span>
                                </li>
                            @endif
                        @empty
                            <li class="text-sm text-red-700">Tidak ada data terkait.</li>
                        @endforelse
                    </ul>
                </div>

                @if (!empty($warnings))
                    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-4">
                        <ul class="space-y-1">
                            @foreach ($warnings as $warning)
                                <li class="flex items-start gap-2 text-sm text-amber-800">
                                    <i data-lucide="info" class="w-4 h-4 mt-0.5 flex-shrink-0"></i>
                                    <span>{{ $warning }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

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

                <form method="POST" action="{{ $action }}" class="space-y-4">
                    @csrf
                    @method('DELETE')
                    @foreach (($hiddenFields ?? []) as $field => $value)
                        <input type="hidden" name="{{ $field }}" value="{{ $value }}">
                    @endforeach
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">
                            Ketik <code class="bg-gray-100 px-1.5 py-0.5 rounded font-mono text-red-700">{{ $itemName }}</code> untuk konfirmasi
                        </label>
                        <input type="text" name="confirmation" required autocomplete="off"
                            class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-red-500/20 focus:border-red-500 transition-all font-mono"
                            placeholder="Ketik nama persis seperti di atas">
                    </div>
                    <div class="flex items-center justify-end gap-3">
                        <a href="{{ $cancelUrl }}" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="inline-flex items-center gap-2 bg-red-600 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:bg-red-700 transition-all shadow-lg shadow-red-500/25">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                            Hapus Permanen
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
