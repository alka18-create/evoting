<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $election->name }} - {{ $votingEvent->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: { extend: { colors: { primary: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81' } } } }
        }
    </script>
    <style>
        .candidate-card{transition:all .3s cubic-bezier(.4,0,.2,1)}
        .candidate-card.selected{border-color:#4f46e5;background:linear-gradient(135deg,#eef2ff,#e0e7ff);box-shadow:0 0 0 3px rgba(79,70,229,.2),0 10px 25px -5px rgba(79,70,229,.15);transform:translateY(-2px)}
        .candidate-card:hover:not(.selected){border-color:#a5b4fc;box-shadow:0 10px 25px -5px rgba(0,0,0,.1);transform:translateY(-2px)}
        .check-badge{opacity:0;transform:scale(.5);transition:all .3s cubic-bezier(.4,0,.2,1)}
        .candidate-card.selected .check-badge{opacity:1;transform:scale(1)}
        .photo-wrapper{overflow:hidden}
        .candidate-card.selected .photo-wrapper img{transform:scale(1.05)}
        .photo-wrapper img{transition:transform .3s ease}
    </style>
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
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl mb-6 flex items-start gap-3"><i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i><div>@foreach ($errors->all() as $error)<p class="text-sm font-medium">{{ $error }}</p>@endforeach</div></div>
        @endif

        <form method="POST" action="{{ route('vote.wizard.storeStep', ['step'=>$step]) }}" id="voteForm">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
                @foreach ($candidates as $candidate)
                    <label class="candidate-card relative block bg-white border-2 {{ $selectedCandidateId==$candidate->id ? 'selected' : 'border-gray-200' }} rounded-2xl overflow-hidden cursor-pointer shadow-sm" data-id="{{ $candidate->id }}">
                        <input type="radio" name="candidate_id" value="{{ $candidate->id }}" class="hidden" {{ $selectedCandidateId==$candidate->id ? 'checked' : '' }}>
                        <div class="check-badge absolute top-4 right-4 w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center shadow-lg z-10"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                        <div class="absolute top-4 left-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-sm z-10"><span class="text-lg font-bold text-primary-700">{{ $candidate->candidate_number }}</span></div>
                        @if ($candidate->photo_path)
                            <div class="photo-wrapper h-48 sm:h-56 bg-gray-100"><img src="{{ asset('storage/' . $candidate->photo_path) }}" alt="{{ $candidate->name }}" class="w-full h-full object-cover"></div>
                        @else
                            <div class="h-48 sm:h-56 bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center"><i data-lucide="user" class="w-16 h-16 text-primary-300"></i></div>
                        @endif
                        <div class="p-5">
                            <h3 class="font-bold text-gray-800 text-lg mb-3">{{ $candidate->name }}</h3>
                            @if ($candidate->vision)<div class="mb-3"><div class="flex items-center gap-1.5 mb-1"><i data-lucide="eye" class="w-3.5 h-3.5 text-primary-500"></i><span class="text-xs font-semibold text-primary-600 uppercase">Visi</span></div><p class="text-sm text-gray-600 leading-relaxed">{{ \Illuminate\Support\Str::limit($candidate->vision,150) }}</p></div>@endif
                            @if ($candidate->mission)<div><div class="flex items-center gap-1.5 mb-1"><i data-lucide="target" class="w-3.5 h-3.5 text-emerald-500"></i><span class="text-xs font-semibold text-emerald-600 uppercase">Misi</span></div><p class="text-sm text-gray-600 leading-relaxed">{{ \Illuminate\Support\Str::limit($candidate->mission,150) }}</p></div>@endif
                        </div>
                    </label>
                @endforeach
            </div>

            <div class="flex items-center justify-between gap-3">
                @if($step > 1)
                    <a href="{{ route('vote.wizard.step', ['step'=>$step-1]) }}" class="inline-flex items-center gap-2 px-6 py-3.5 border-2 border-gray-200 text-gray-700 font-semibold rounded-2xl hover:bg-gray-50">Kembali</a>
                @else
                    <div></div>
                @endif
                <button type="submit" id="nextBtn" {{ $selectedCandidateId ? '' : 'disabled' }} class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-bold py-3.5 px-10 rounded-2xl hover:from-primary-700 hover:to-primary-800 disabled:opacity-40 disabled:cursor-not-allowed shadow-lg shadow-primary-500/25 text-sm uppercase tracking-wider">
                    {{ $step < $totalSteps ? 'Lanjut' : 'Lanjut ke Review' }} <i data-lucide="arrow-right" class="w-4 h-4"></i>
                </button>
            </div>
        </form>
    </div>

    <script>
        lucide.createIcons();
        const cards=document.querySelectorAll('.candidate-card');
        const nextBtn=document.getElementById('nextBtn');
        cards.forEach(c=>{
            c.addEventListener('click',()=>{
                cards.forEach(x=>{x.classList.remove('selected'); x.querySelector('input').checked=false});
                c.classList.add('selected'); c.querySelector('input').checked=true;
                nextBtn.disabled=false;
            });
        });
    </script>
</body>
</html>
