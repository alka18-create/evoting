<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Pilihan - {{ $votingEvent->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-primary-50 min-h-screen">
    <div class="bg-gradient-to-r from-primary-700 via-primary-600 to-primary-800 text-white">
        <div class="max-w-5xl mx-auto px-4 py-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3"><div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center"><i data-lucide="vote" class="w-5 h-5"></i></div><div><p class="text-primary-200 text-xs uppercase">E-Voting</p><p class="text-sm font-semibold">{{ $votingEvent->name }}</p></div></div>
                <div class="flex items-center gap-3 bg-white/10 rounded-2xl px-4 py-2"><div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center"><span class="text-sm font-bold">{{ substr($voter->name,0,1) }}</span></div><div class="text-right"><p class="text-sm font-semibold">{{ $voter->name }}</p><p class="text-[11px] text-primary-200">{{ $voter->class_name }}</p></div></div>
            </div>
            <h1 class="text-xl sm:text-2xl font-bold">Review Pilihan Anda</h1>
            <p class="text-primary-200 text-sm">Periksa kembali sebelum kirim — semua suara akan dikirim sekaligus</p>
            <div class="flex items-center gap-2 mt-4" aria-label="Progres pemilihan">
                @foreach($elections as $idx => $el)
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold bg-emerald-500 text-white">✓</div>
                        <span class="text-xs text-white font-semibold hidden sm:inline">{{ $el->organization?->name ?? $el->name }}</span>
                        @if($idx+1 < count($elections))<div class="w-6 h-0.5 bg-emerald-400"></div>@endif
                    </div>
                @endforeach
                <span class="ml-auto text-xs text-primary-200">Review</span>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 -mt-4 sm:-mt-6 pb-12">
        <x-form-errors />

        <div class="space-y-4 mb-8">
            @forelse($reviewData as $idx => $item)
                <div class="bg-white rounded-2xl border-2 border-gray-200 overflow-hidden shadow-sm">
                    <div class="px-5 py-3 bg-gray-50 border-b flex items-center justify-between">
                        <div><p class="text-xs text-gray-400 uppercase tracking-wider">{{ $item['election']->organization?->name ?? 'Pemilihan' }}</p><p class="font-semibold text-gray-800">{{ $item['election']->name }}</p></div>
                        <a href="{{ route('vote.wizard.step', ['step'=>$idx+1]) }}" class="text-xs font-semibold text-primary-600 hover:text-primary-700 flex items-center gap-1"><i data-lucide="pencil" class="w-3.5 h-3.5"></i> Ubah</a>
                    </div>
                    @if($item['candidate'])
                    <div class="p-5 flex gap-4">
                        @if($item['candidate']->photo_path)<img src="{{ asset('storage/'.$item['candidate']->photo_path) }}" alt="{{ $item['candidate']->name }}" class="w-20 h-20 rounded-xl object-cover flex-shrink-0">@else<div class="w-20 h-20 rounded-xl bg-primary-50 flex items-center justify-center flex-shrink-0"><i data-lucide="user" class="w-8 h-8 text-primary-300"></i></div>@endif
                        <div class="flex-1 min-w-0">
                            <div class="inline-flex items-center gap-2 bg-primary-50 text-primary-700 text-xs font-bold px-2.5 py-1 rounded-lg mb-1">No. {{ $item['candidate']->candidate_number }}</div>
                            <p class="font-bold text-gray-800">{{ $item['candidate']->name }}</p>
                            @if($item['candidate']->isPair())<p class="text-xs font-medium text-cyan-700 mt-0.5 inline-flex items-center gap-1"><i data-lucide="users" class="w-3 h-3"></i> Bersama {{ $item['candidate']->running_mate_name }}</p>@endif
                            @if($item['candidate']->vision)<p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ \Illuminate\Support\Str::limit($item['candidate']->vision,100) }}</p>@endif
                        </div>
                        <div class="w-8 h-8 bg-emerald-500 rounded-full flex items-center justify-center flex-shrink-0"><svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg></div>
                    </div>
                    @else
                    <div class="p-5 flex gap-3 items-center bg-red-50">
                        <i data-lucide="alert-triangle" class="w-6 h-6 text-red-500 flex-shrink-0"></i>
                        <div>
                            <p class="font-semibold text-red-700 text-sm">Kandidat tidak tersedia</p>
                            <p class="text-xs text-red-500">Kandidat pilihan Anda sudah tidak ada. Silakan kembali dan pilih ulang.</p>
                        </div>
                    </div>
                    @endif
                </div>
            @empty
                <div class="bg-white rounded-2xl border-2 border-dashed border-gray-200 p-10 text-center">
                    <i data-lucide="inbox" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                    <p class="font-semibold text-gray-700">Belum ada pilihan</p>
                    <p class="text-sm text-gray-500 mb-4">Sesi Anda mungkin kedaluwarsa. Mulai lagi dari langkah pertama.</p>
                    <a href="{{ route('vote.wizard.step', ['step'=>1]) }}" class="inline-flex items-center gap-2 bg-primary-600 text-white font-semibold px-6 py-3 rounded-2xl text-sm">Ke Langkah 1</a>
                </div>
            @endforelse
        </div>

        @if(count($reviewData) > 0)
        <form method="POST" action="{{ route('vote.wizard.submit') }}" id="submitForm">
            @csrf
            <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-6 flex gap-3"><i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600 flex-shrink-0"></i><p class="text-sm text-amber-800"><b>Perhatian:</b> Semua suara akan dikirim sekaligus dan <b>tidak dapat diubah</b> setelah dikirim.</p></div>
            <div class="flex gap-3">
                <a href="{{ route('vote.wizard.step', ['step'=>count($elections)]) }}" class="flex-1 px-6 py-4 border-2 border-gray-200 rounded-2xl font-semibold text-gray-700 hover:bg-gray-50 text-center">Kembali</a>
                <button type="button" id="showConfirm" class="flex-1 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-bold py-4 rounded-2xl shadow-lg shadow-primary-500/25 hover:from-primary-700 hover:to-primary-800 text-sm uppercase tracking-wider">Kirim Semua Suara</button>
            </div>
        </form>
        @endif
    </div>

    <div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm hidden items-center justify-center z-50 px-4" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-md">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4"><i data-lucide="vote" class="w-8 h-8 text-primary-600"></i></div>
                <h2 id="confirmTitle" class="text-xl font-bold text-gray-800 mb-2">Kirim Semua Suara?</h2>
                <p class="text-gray-500 text-sm mb-6">Anda akan mengirim {{ count($reviewData) }} suara sekaligus untuk event <b>{{ $votingEvent->name }}</b>. Lanjutkan?</p>
                <div class="flex gap-3">
                    <button type="button" id="cancelBtn" class="flex-1 border-2 border-gray-200 py-3 rounded-2xl font-semibold hover:bg-gray-50">Batal</button>
                    <button type="button" id="confirmBtn" class="flex-1 bg-gradient-to-r from-primary-600 to-primary-700 text-white py-3 rounded-2xl font-bold shadow-lg disabled:opacity-50">Ya, Kirim</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        if (window.lucide && window.lucide.icons) window.lucide.createIcons({ icons: window.lucide.icons });
        const modal=document.getElementById('confirmModal');
        const showBtn=document.getElementById('showConfirm');
        const cancelBtn=document.getElementById('cancelBtn');
        const confirmBtn=document.getElementById('confirmBtn');
        function openModal(){modal.classList.remove('hidden');modal.classList.add('flex');cancelBtn.focus();}
        function closeModal(){modal.classList.add('hidden');modal.classList.remove('flex');if(showBtn)showBtn.focus();}
        if(showBtn){showBtn.addEventListener('click',openModal);}
        if(cancelBtn){cancelBtn.addEventListener('click',closeModal);}
        modal.addEventListener('click',function(e){if(e.target===modal)closeModal();});
        document.addEventListener('keydown',function(e){if(e.key==='Escape'&&!modal.classList.contains('hidden'))closeModal();});
        if(confirmBtn){confirmBtn.addEventListener('click',function(){confirmBtn.disabled=true;confirmBtn.textContent='Mengirim...';document.getElementById('submitForm').submit();});}
    </script>
</body>
</html>
