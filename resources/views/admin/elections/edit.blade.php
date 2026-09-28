<x-layouts.admin title="Edit Pemilihan">
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="pencil" class="w-5 h-5 text-primary-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Edit: {{ $election->name }}</h3>
                    <p class="text-xs text-gray-500">Perbarui detail pemilihan</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.elections.update', $election) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Pemilihan</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="tag" class="w-[18px] h-[18px] text-gray-400"></i>
                        </div>
                        <input type="text" name="name" value="{{ old('name', $election->name) }}" required
                            class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                            placeholder="Nama pemilihan">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all resize-none"
                        placeholder="Deskripsi pemilihan...">{{ old('description', $election->description) }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Event</label>
                        <select name="voting_event_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                            <option value="">-- Tanpa Event — pemilihan tunggal --</option>
                            @foreach($votingEvents as $ev)<option value="{{ $ev->id }}" @selected(old('voting_event_id', $election->voting_event_id)==$ev->id)>{{ $ev->name }}</option>@endforeach
                        </select>
                        <p class="text-xs text-gray-400 mt-1">Pindah mode hanya saat DRAFT. Melepas event wajib isi tanggal sendiri.</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Organisasi</label>
                        <select name="organization_id" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                            <option value="">-- Tanpa Organisasi --</option>
                            @foreach($organizations as $org)<option value="{{ $org->id }}" @selected(old('organization_id', $election->organization_id)==$org->id)>{{ $org->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Mulai <span class="text-gray-400 font-normal">(wajib jika tanpa event)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="calendar" class="w-[18px] h-[18px] text-gray-400"></i>
                            </div>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $election->starts_at?->format('Y-m-d\TH:i')) }}"
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Selesai <span class="text-gray-400 font-normal">(wajib jika tanpa event)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="calendar-check" class="w-[18px] h-[18px] text-gray-400"></i>
                            </div>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $election->ends_at?->format('Y-m-d\TH:i')) }}"
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-gray-100">
                    <a href="{{ route('admin.elections.index') }}" class="px-5 py-2.5 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-xl transition-colors">
                        Batal
                    </a>
                    <button type="submit" class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                        <i data-lucide="save" class="w-4 h-4"></i>
                        Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>

</x-layouts.admin>
