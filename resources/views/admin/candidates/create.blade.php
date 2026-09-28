<x-layouts.admin title="Tambah Kandidat">
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="user-plus" class="w-5 h-5 text-purple-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Tambah Kandidat</h3>
                    <p class="text-xs text-gray-500">{{ $election->name }}</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.elections.candidates.store', $election) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nomor Urut</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="hash" class="w-[18px] h-[18px] text-gray-400"></i>
                        </div>
                        <input type="number" name="candidate_number" id="candidate_number" value="{{ old('candidate_number') }}" required min="1"
                            class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                            placeholder="1">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4 items-start">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Kandidat</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="user" class="w-[18px] h-[18px] text-gray-400"></i>
                            </div>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Nama kandidat">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Foto <span class="text-gray-400 font-normal">(opsional, maks 2MB)</span></label>
                        <input type="file" name="photo" id="photo" accept="image/*"
                            class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                    </div>
                </div>

                <div class="border border-dashed border-gray-200 rounded-xl p-4 space-y-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="users" class="w-4 h-4 text-gray-400"></i>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Pasangan (opsional)</p>
                        <span class="text-xs text-gray-400">— isi jika calon berpasangan, kosongkan untuk kandidat tunggal</span>
                    </div>
                    <div class="grid grid-cols-2 gap-4 items-start">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Wakil Pasangan</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <i data-lucide="user-round" class="w-[18px] h-[18px] text-gray-400"></i>
                                </div>
                                <input type="text" name="running_mate_name" id="running_mate_name" value="{{ old('running_mate_name') }}"
                                    class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                    placeholder="Nama wakil">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">Foto Wakil <span class="text-gray-400 font-normal">(opsional, maks 2MB)</span></label>
                            <input type="file" name="running_mate_photo" id="running_mate_photo" accept="image/*"
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Visi <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <textarea name="vision" id="vision" rows="3"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all resize-none"
                        placeholder="Visi kandidat...">{{ old('vision') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Misi <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <textarea name="mission" id="mission" rows="3"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all resize-none"
                        placeholder="Misi kandidat...">{{ old('mission') }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('admin.elections.candidates.index', $election) }}" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-layouts.admin>
