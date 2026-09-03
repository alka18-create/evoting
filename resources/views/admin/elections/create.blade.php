<x-layouts.admin title="Buat Pemilihan">
    <div class="max-w-2xl">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 bg-primary-100 rounded-xl flex items-center justify-center">
                    <i data-lucide="vote" class="w-5 h-5 text-primary-600"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-gray-800">Informasi Pemilihan</h3>
                    <p class="text-xs text-gray-500">Isi detail pemilihan yang akan dibuat</p>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.elections.store') }}" class="space-y-5">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Nama Pemilihan <span class="text-gray-400 font-normal">(otomatis jika pilih Event+Organisasi)</span></label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <i data-lucide="tag" class="w-[18px] h-[18px] text-gray-400"></i>
                        </div>
                        <input type="text" name="name" id="election-name" value="{{ old('name') }}" required
                            class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                            placeholder="Contoh: Pemilihan OSIS — Serentak 2026">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Kosongkan awal, akan terisi "Pemilihan {Organisasi} — {Event}" — bisa diedit manual</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Deskripsi <span class="text-gray-400 font-normal">(opsional)</span></label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all resize-none"
                        placeholder="Deskripsi singkat tentang pemilihan ini...">{{ old('description') }}</textarea>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Event <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <select name="voting_event_id" id="voting-event-select" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <option value="">-- Tanpa Event (Legacy) --</option>
                            @foreach($votingEvents as $ev)<option value="{{ $ev->id }}" data-name="{{ $ev->name }}" @selected((old('voting_event_id') ?? $preselectedEventId ?? null)==$ev->id)>{{ $ev->name }} ({{ $ev->status->label() }})</option>@endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Organisasi <span class="text-gray-400 font-normal">(opsional)</span></label>
                        <select name="organization_id" id="organization-select" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500">
                            <option value="">-- Tanpa Organisasi --</option>
                            @foreach($organizations as $org)<option value="{{ $org->id }}" data-name="{{ $org->name }}" @selected(old('organization_id')==$org->id)>{{ $org->name }}</option>@endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Mulai <span class="text-gray-400 font-normal">(kosongkan = ikut event)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="calendar" class="w-[18px] h-[18px] text-gray-400"></i>
                            </div>
                            <input type="datetime-local" name="starts_at" value="{{ old('starts_at') }}"
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Tanggal Selesai <span class="text-gray-400 font-normal">(kosongkan = ikut event)</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="calendar-check" class="w-[18px] h-[18px] text-gray-400"></i>
                            </div>
                            <input type="datetime-local" name="ends_at" value="{{ old('ends_at') }}"
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
                        Simpan
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        const nameInput = document.getElementById('election-name');
        const eventSelect = document.getElementById('voting-event-select');
        const orgSelect = document.getElementById('organization-select');
        function autoFill(){
            const orgName = orgSelect.selectedOptions[0]?.dataset?.name || '';
            const evName = eventSelect.selectedOptions[0]?.dataset?.name || '';
            if(!orgName && !evName) return;
            const auto = 'Pemilihan ' + (orgName||'') + (orgName&&evName?' — ':'') + (evName||'');
            if(!nameInput.value || nameInput.value.startsWith('Pemilihan ')){
                nameInput.value = auto.trim();
            }
        }
        eventSelect.addEventListener('change', autoFill);
        orgSelect.addEventListener('change', autoFill);
    </script>
</x-layouts.admin>
