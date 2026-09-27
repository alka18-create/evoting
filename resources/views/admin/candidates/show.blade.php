<x-layouts.admin title="{{ $candidate->name }}">
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="inline-flex items-center justify-center w-10 h-10 bg-primary-100 text-primary-700 rounded-lg text-lg font-bold">
                            {{ $candidate->candidate_number }}
                        </span>
                        <div>
                            <h3 class="font-semibold text-gray-800">{{ $candidate->name }}</h3>
                            @if ($candidate->isPair())
                                <p class="text-xs text-gray-500">Bersama {{ $candidate->running_mate_name }}</p>
                            @endif
                            <p class="text-xs text-gray-500">{{ $election->name }}</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.elections.candidates.edit', [$election, $candidate]) }}"
                            class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:text-primary-600 hover:bg-gray-50 rounded-lg transition-colors">
                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                            Edit
                        </a>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6 space-y-6">
                <!-- Foto -->
                @if ($candidate->photo_path)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Foto</label>
                        <div class="w-48 h-48 rounded-xl overflow-hidden bg-gray-100">
                            <img src="{{ Storage::url($candidate->photo_path) }}" alt="{{ $candidate->name }}"
                                class="w-full h-full object-cover">
                        </div>
                    </div>
                @endif

                <!-- Pasangan -->
                @if ($candidate->isPair())
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Pasangan</label>
                        <div class="p-4 bg-gray-50 rounded-xl flex items-center gap-4">
                            @if ($candidate->running_mate_photo_path)
                                <div class="w-20 h-20 rounded-lg overflow-hidden bg-gray-100 flex-shrink-0">
                                    <img src="{{ Storage::url($candidate->running_mate_photo_path) }}" alt="{{ $candidate->running_mate_name }}"
                                        class="w-full h-full object-cover">
                                </div>
                            @endif
                            <div>
                                <p class="text-sm font-semibold text-gray-800">{{ $candidate->name }} &amp; {{ $candidate->running_mate_name }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">Calon nomor {{ $candidate->candidate_number }} berpasangan</p>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Visi -->
                @if ($candidate->vision)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Visi</label>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $candidate->vision }}</p>
                        </div>
                    </div>
                @endif

                <!-- Misi -->
                @if ($candidate->mission)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Misi</label>
                        <div class="p-4 bg-gray-50 rounded-xl">
                            <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line">{{ $candidate->mission }}</p>
                        </div>
                    </div>
                @endif

                @if (!$candidate->vision && !$candidate->mission && !$candidate->photo_path && !$candidate->isPair())
                    <div class="text-center py-8">
                        <i data-lucide="file-text" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                        <p class="text-gray-500 text-sm">Belum ada detail untuk kandidat ini.</p>
                    </div>
                @endif
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                <a href="{{ route('admin.elections.candidates.index', $election) }}" class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-primary-600 transition-colors">
                    <i data-lucide="arrow-left" class="w-4 h-4"></i>
                    Kembali ke Daftar Kandidat
                </a>
            </div>
        </div>
    </div>

</x-layouts.admin>
