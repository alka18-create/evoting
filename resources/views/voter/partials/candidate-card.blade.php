{{-- Kartu kandidat bersama (wizard step + legacy single vote).
     Variabel: $candidate, $selectedCandidateId (nullable).
     Radio memakai sr-only (bukan hidden) agar bisa fokus keyboard. --}}
<label class="candidate-card relative block bg-white border-2 {{ ($selectedCandidateId ?? null) == $candidate->id ? 'selected' : 'border-gray-200' }} rounded-2xl overflow-hidden cursor-pointer shadow-sm" data-id="{{ $candidate->id }}" data-number="{{ $candidate->candidate_number }}" data-name="{{ $candidate->display_name() }}">
    <input type="radio" name="candidate_id" value="{{ $candidate->id }}" class="sr-only" {{ ($selectedCandidateId ?? null) == $candidate->id ? 'checked' : '' }}>
    <div class="check-badge absolute top-4 right-4 w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center shadow-lg z-10" aria-hidden="true"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
    <div class="absolute top-4 left-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-sm z-10"><span class="text-lg font-bold text-primary-700">{{ $candidate->candidate_number }}</span></div>
    @if ($candidate->photo_path && $candidate->running_mate_photo_path)
        <div class="photo-wrapper h-48 sm:h-56 bg-gray-100 grid grid-cols-2 divide-x divide-white/60">
            <img src="{{ asset('storage/' . $candidate->photo_path) }}" alt="Foto {{ $candidate->name }}" class="w-full h-full object-cover" loading="lazy">
            <img src="{{ asset('storage/' . $candidate->running_mate_photo_path) }}" alt="Foto {{ $candidate->running_mate_name }}" class="w-full h-full object-cover" loading="lazy">
        </div>
    @elseif ($candidate->photo_path)
        <div class="photo-wrapper h-48 sm:h-56 bg-gray-100"><img src="{{ asset('storage/' . $candidate->photo_path) }}" alt="Foto {{ $candidate->name }}" class="w-full h-full object-cover" loading="lazy"></div>
    @elseif ($candidate->running_mate_photo_path)
        <div class="photo-wrapper h-48 sm:h-56 bg-gray-100"><img src="{{ asset('storage/' . $candidate->running_mate_photo_path) }}" alt="Foto {{ $candidate->running_mate_name }}" class="w-full h-full object-cover" loading="lazy"></div>
    @else
        <div class="h-48 sm:h-56 bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center"><i data-lucide="{{ $candidate->isPair() ? 'users' : 'user' }}" class="w-16 h-16 text-primary-300"></i></div>
    @endif
    <div class="p-5">
        <h3 class="font-bold text-gray-800 text-lg mb-1">{{ $candidate->name }}</h3>
        @if ($candidate->isPair())
            <p class="text-sm font-medium text-cyan-700 mb-3 inline-flex items-center gap-1"><i data-lucide="users" class="w-3.5 h-3.5"></i> {{ $candidate->name }} &amp; {{ $candidate->running_mate_name }}</p>
        @else
            <div class="mb-3"></div>
        @endif
        @if ($candidate->vision)<div class="mb-3"><div class="flex items-center gap-1.5 mb-1"><i data-lucide="eye" class="w-3.5 h-3.5 text-primary-500"></i><span class="text-xs font-semibold text-primary-600 uppercase">Visi</span></div><p class="text-sm text-gray-600 leading-relaxed">{{ \Illuminate\Support\Str::limit($candidate->vision,150) }}</p></div>@endif
        @if ($candidate->mission)<div><div class="flex items-center gap-1.5 mb-1"><i data-lucide="target" class="w-3.5 h-3.5 text-emerald-500"></i><span class="text-xs font-semibold text-emerald-600 uppercase">Misi</span></div><p class="text-sm text-gray-600 leading-relaxed">{{ \Illuminate\Support\Str::limit($candidate->mission,150) }}</p></div>@endif
    </div>
</label>
