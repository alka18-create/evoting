<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - {{ $election->name }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @include('voter.partials.candidate-style')
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-primary-50 min-h-screen">
    <!-- Header -->
    <div class="bg-gradient-to-r from-primary-700 via-primary-600 to-primary-800 text-white">
        <div class="max-w-5xl mx-auto px-4 py-8 sm:py-10">
            <!-- Voter Identity -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center backdrop-blur-sm">
                        <i data-lucide="vote" class="w-5 h-5"></i>
                    </div>
                    <span class="text-primary-200 text-sm font-medium tracking-wide uppercase">E-Voting</span>
                </div>
                <div class="flex items-center gap-3 bg-white/10 backdrop-blur-sm rounded-2xl px-4 py-2">
                    <div class="w-9 h-9 bg-white/20 rounded-xl flex items-center justify-center">
                        <span class="text-sm font-bold">{{ substr($eligibility->voter->name, 0, 1) }}</span>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold leading-tight">{{ $eligibility->voter->name }}</p>
                        <p class="text-[11px] text-primary-200">{{ $eligibility->voter->class_name }}</p>
                    </div>
                </div>
            </div>

            <h1 class="text-2xl sm:text-3xl font-bold mb-2">{{ $election->name }}</h1>
            <p class="text-primary-200 text-sm sm:text-base">Pilih satu kandidat untuk memberikan suara Anda</p>
            @if ($election->starts_at && $election->ends_at)
                <div class="flex items-center gap-2 mt-3 text-primary-300 text-xs">
                    <i data-lucide="calendar" class="w-3.5 h-3.5"></i>
                    <span>{{ $election->starts_at->format('d M Y') }} - {{ $election->ends_at->format('d M Y') }}</span>
                </div>
            @endif
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 -mt-4 sm:-mt-6 pb-12">
        <x-form-errors />

        <form method="POST" action="{{ route('vote.submit') }}" id="voteForm">
            @csrf

            <!-- Candidates Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
                @forelse ($candidates as $candidate)
                    @include('voter.partials.candidate-card', ['candidate' => $candidate, 'selectedCandidateId' => null])
                @empty
                    <div class="col-span-full bg-white rounded-2xl border-2 border-dashed border-gray-200 p-10 text-center">
                        <p class="font-semibold text-gray-700">Belum ada kandidat</p>
                        <p class="text-sm text-gray-500">Hubungi panitia pemilihan.</p>
                    </div>
                @endforelse
            </div>

            <!-- Submit Button -->
            <div class="text-center">
                <button type="button" id="showConfirm"
                    class="inline-flex items-center gap-2 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-bold py-4 px-12 rounded-2xl hover:from-primary-700 hover:to-primary-800 disabled:opacity-40 disabled:cursor-not-allowed transition-all shadow-lg shadow-primary-500/25 disabled:shadow-none text-sm uppercase tracking-wider"
                    disabled>
                    <i data-lucide="send" class="w-5 h-5"></i>
                    Kirim Suara
                </button>
                <p class="text-sm text-gray-400 mt-4">
                    <i data-lucide="info" class="w-3.5 h-3.5 inline"></i>
                    Suara yang sudah dikirim tidak dapat diubah
                </p>
            </div>
        </form>
    </div>

    <!-- Confirmation Modal -->
    <div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm items-center justify-center z-50 hidden px-4" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
        <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-md transform transition-all">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="vote" class="w-8 h-8 text-primary-600"></i>
                </div>
                <h2 id="confirmTitle" class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Pilihan Anda</h2>
                <p class="text-gray-500 text-sm mb-6">Anda akan memilih:</p>

                <div class="bg-gray-50 rounded-2xl p-5 mb-6">
                    <p class="text-xs text-gray-400 uppercase tracking-wider mb-1">Kandidat Nomor</p>
                    <p class="text-4xl font-bold text-primary-600" id="modalNumber"></p>
                    <p class="text-lg font-semibold text-gray-800 mt-1" id="modalName"></p>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-6">
                    <p class="text-sm text-amber-700 font-medium flex items-center justify-center gap-2">
                        <i data-lucide="alert-triangle" class="w-4 h-4"></i>
                        Suara yang sudah dikirim tidak dapat diubah
                    </p>
                </div>

                <div class="flex gap-3">
                    <button type="button" id="cancelVote"
                        class="flex-1 px-4 py-3.5 border-2 border-gray-200 text-gray-700 font-semibold rounded-2xl hover:bg-gray-50 transition-colors">
                        Batal
                    </button>
                    <button type="button" id="confirmVote"
                        class="flex-1 px-4 py-3.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-2xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25 disabled:opacity-50">
                        Ya, Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        if (window.lucide && window.lucide.icons) window.lucide.createIcons({ icons: window.lucide.icons });

        const cards = document.querySelectorAll('.candidate-card');
        const showConfirmBtn = document.getElementById('showConfirm');
        const confirmModal = document.getElementById('confirmModal');
        const cancelBtn = document.getElementById('cancelVote');
        const confirmBtn = document.getElementById('confirmVote');
        const voteForm = document.getElementById('voteForm');
        const modalNumber = document.getElementById('modalNumber');
        const modalName = document.getElementById('modalName');

        function clearSelection() {
            cards.forEach(card => {
                card.classList.remove('selected');
                card.querySelector('input[type="radio"]').checked = false;
            });
            showConfirmBtn.disabled = true;
        }

        cards.forEach(card => {
            card.addEventListener('click', () => {
                clearSelection();
                card.classList.add('selected');
                card.querySelector('input[type="radio"]').checked = true;
                showConfirmBtn.disabled = false;
            });
        });

        showConfirmBtn.addEventListener('click', () => {
            const selected = document.querySelector('input[name="candidate_id"]:checked');
            if (!selected) return;

            const card = selected.closest('.candidate-card');
            modalNumber.textContent = card.dataset.number;
            modalName.textContent = card.dataset.name;
            confirmModal.classList.remove('hidden');
            confirmModal.classList.add('flex');
        });

        function closeModal() {
            confirmModal.classList.add('hidden');
            confirmModal.classList.remove('flex');
        }

        cancelBtn.addEventListener('click', closeModal);

        confirmModal.addEventListener('click', (e) => {
            if (e.target === confirmModal) closeModal();
        });

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !confirmModal.classList.contains('hidden')) closeModal();
        });

        confirmBtn.addEventListener('click', () => {
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Mengirim...';
            voteForm.submit();
        });
    </script>
</body>
</html>
