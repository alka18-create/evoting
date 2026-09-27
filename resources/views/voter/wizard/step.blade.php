<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $election->name }} - {{ $votingEvent->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('voter.partials.candidate-style')
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-primary-50 min-h-screen">
    <div class="bg-gradient-to-r from-primary-700 via-primary-600 to-primary-800 text-white">
        <div class="max-w-5xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center backdrop-blur-sm"><i data-lucide="vote" class="w-5 h-5"></i></div>
                    <div>
                        <p class="text-primary-200 text-xs uppercase tracking-wide">E-Voting</p>
                        <p class="text-sm font-semibold">{{ $votingEvent->name }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl px-4 py-2">
                    <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center"><span class="text-sm font-bold">{{ substr($voter->name,0,1) }}</span></div>
                    <div class="text-right"><p class="text-sm font-semibold leading-tight">{{ $voter->name }}</p><p class="text-[11px] text-primary-200">{{ $voter->class_name }}</p></div>
                </div>
            </div>
            <div class="flex items-center gap-2 mb-3">
                @foreach($elections as $idx => $el)
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold {{ $idx+1 < $step ? 'bg-emerald-500 text-white' : ($idx+1 == $step ? 'bg-white text-primary-700' : 'bg-white/20 text-white') }}">{{ $idx+1 < $step ? '✓' : $idx+1 }}</div>
                        <span class="text-xs {{ $idx+1 == $step ? 'text-white font-semibold' : 'text-primary-200' }} hidden sm:inline">{{ $el->organization?->name ?? $el->name }}</span>
                        @if($idx+1 < $totalSteps)<div class="w-6 h-0.5 {{ $idx+1 < $step ? 'bg-emerald-400' : 'bg-white/20' }}"></div>@endif
                    </div>
                @endforeach
                <span class="ml-auto text-xs text-primary-200">{{ $step }}/{{ $totalSteps }}</span>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold">{{ $election->name }}</h1>
            @if($election->organization)<p class="text-primary-200 text-sm">{{ $election->organization->name }}</p>@endif
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 -mt-4 sm:-mt-6 pb-12">
        <x-form-errors />

        <form method="POST" action="{{ route('vote.wizard.storeStep', ['step'=>$step]) }}" id="voteForm">
            @csrf
            @if (($votability ?? 'VOTABLE') !== 'VOTABLE')
                @php
                    $votabilityInfo = [
                        'NOT_OPEN' => ['icon' => 'lock', 'title' => 'Pemilihan belum dibuka', 'desc' => 'Panitia belum membuka pemilihan ini. Silakan coba lagi nanti.'],
                        'NOT_STARTED' => ['icon' => 'clock', 'title' => 'Pemilihan belum dimulai', 'desc' => 'Pemilihan ini akan dibuka sesuai jadwal. Kembali lagi saat waktunya tiba.'],
                        'ENDED' => ['icon' => 'calendar-x', 'title' => 'Pemilihan sudah berakhir', 'desc' => 'Waktu pemilihan ini telah selesai dan tidak dapat diikuti lagi.'],
                    ][$votability] ?? ['icon' => 'alert-circle', 'title' => 'Pemilihan belum bisa dipilih', 'desc' => 'Silakan hubungi panitia pemilihan.'];
                @endphp
                <div class="bg-white rounded-2xl border border-amber-200 bg-amber-50/50 p-8 sm:p-10 text-center mb-8">
                    <div class="w-14 h-14 bg-amber-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="{{ $votabilityInfo['icon'] }}" class="w-7 h-7 text-amber-600"></i>
                    </div>
                    <p class="font-bold text-gray-800 text-lg">{{ $votabilityInfo['title'] }}</p>
                    <p class="text-sm text-gray-600 mt-1 max-w-md mx-auto">{{ $votabilityInfo['desc'] }}</p>
                </div>
            @else
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
                @forelse ($candidates as $candidate)
                    @include('voter.partials.candidate-card', ['candidate' => $candidate, 'selectedCandidateId' => $selectedCandidateId])
                @empty
                    <div class="col-span-full bg-white rounded-2xl border-2 border-dashed border-gray-200 p-10 text-center">
                        <p class="font-semibold text-gray-700">Belum ada kandidat</p>
                        <p class="text-sm text-gray-500">Hubungi panitia pemilihan.</p>
                    </div>
                @endforelse
            </div>
            @endif

            <div class="flex items-center justify-between gap-3">
                @if($step > 1)
                    <a href="{{ route('vote.wizard.step', ['step'=>$step-1]) }}" class="inline-flex items-center gap-2 px-6 py-3.5 border-2 border-gray-200 text-gray-700 font-semibold rounded-2xl hover:bg-gray-50">Kembali</a>
                @else
                    <div></div>
                @endif
                @if (($votability ?? 'VOTABLE') !== 'VOTABLE')
                    @if($step < $totalSteps)
                        <a href="{{ route('vote.wizard.step', ['step'=>$step+1]) }}" class="inline-flex items-center gap-2 bg-gray-800 text-white font-bold py-3.5 px-10 rounded-2xl hover:bg-gray-900 shadow-lg text-sm uppercase tracking-wider">
                            Lewati <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    @else
                        <a href="{{ route('vote.wizard.review') }}" class="inline-flex items-center gap-2 bg-gray-800 text-white font-bold py-3.5 px-10 rounded-2xl hover:bg-gray-900 shadow-lg text-sm uppercase tracking-wider">
                            Ke Review <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        </a>
                    @endif
                @else
                <button type="submit" id="nextBtn" {{ $selectedCandidateId ? '' : 'disabled' }} class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-bold py-3.5 px-10 rounded-2xl hover:from-primary-700 hover:to-primary-800 disabled:opacity-40 disabled:cursor-not-allowed shadow-lg shadow-primary-500/25 text-sm uppercase tracking-wider">
                    {{ $step < $totalSteps ? 'Lanjut' : 'Lanjut ke Review' }} <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
                @endif
            </div>
        </form>
    </div>

    <script>
        if (window.lucide && window.lucide.icons) window.lucide.createIcons({ icons: window.lucide.icons });
        const cards=document.querySelectorAll('.candidate-card');
        const nextBtn=document.getElementById('nextBtn');
        cards.forEach(c=>{
            c.addEventListener('click',()=>{
                cards.forEach(x=>{x.classList.remove('selected'); x.querySelector('input').checked=false});
                c.classList.add('selected'); c.querySelector('input').checked=true;
                if(nextBtn) nextBtn.disabled=false;
            });
        });
    </script>
</body>
</html>
