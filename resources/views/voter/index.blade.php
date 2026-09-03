<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vote - {{ $election->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81' },
                    }
                }
            }
        }
    </script>
    <style>
        .candidate-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .candidate-card.selected {
            border-color: #4f46e5;
            background: linear-gradient(135deg, #eef2ff, #e0e7ff);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2), 0 10px 25px -5px rgba(79, 70, 229, 0.15);
            transform: translateY(-2px);
        }
        .candidate-card:hover:not(.selected) {
            border-color: #a5b4fc;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            transform: translateY(-2px);
        }
        .check-badge {
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .candidate-card.selected .check-badge {
            opacity: 1;
            transform: scale(1);
        }
        .photo-wrapper {
            overflow: hidden;
        }
        .candidate-card.selected .photo-wrapper img {
            transform: scale(1.05);
        }
        .photo-wrapper img {
            transition: transform 0.3s ease;
        }
    </style>
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
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl mb-6 flex items-start gap-3 shadow-sm">
                <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                <div>
                    @foreach ($errors->all() as $error)
                        <p class="text-sm font-medium">{{ $error }}</p>
                    @endforeach
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('vote.submit') }}" id="voteForm">
            @csrf

            <!-- Candidates Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 mb-8">
                @foreach ($candidates as $candidate)
                    <label class="candidate-card relative block bg-white border-2 border-gray-200 rounded-2xl overflow-hidden cursor-pointer shadow-sm" data-id="{{ $candidate->id }}" data-number="{{ $candidate->candidate_number }}" data-name="{{ $candidate->name }}">

                        <input type="radio" name="candidate_id" value="{{ $candidate->id }}" class="hidden">

                        <!-- Check badge -->
                        <div class="check-badge absolute top-4 right-4 w-8 h-8 bg-primary-600 rounded-full flex items-center justify-center shadow-lg z-10">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" stroke-width="3" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>

                        <!-- Candidate Number Badge -->
                        <div class="absolute top-4 left-4 w-10 h-10 bg-white/90 backdrop-blur-sm rounded-xl flex items-center justify-center shadow-sm z-10">
                            <span class="text-lg font-bold text-primary-700">{{ $candidate->candidate_number }}</span>
                        </div>

                        <!-- Photo -->
                        @if ($candidate->photo_path)
                            <div class="photo-wrapper h-48 sm:h-56 bg-gray-100">
                                <img src="{{ asset('storage/' . $candidate->photo_path) }}" alt="{{ $candidate->name }}" class="w-full h-full object-cover">
                            </div>
                        @else
                            <div class="h-48 sm:h-56 bg-gradient-to-br from-primary-50 to-primary-100 flex items-center justify-center">
                                <i data-lucide="user" class="w-16 h-16 text-primary-300"></i>
                            </div>
                        @endif

                        <!-- Info -->
                        <div class="p-5">
                            <h3 class="font-bold text-gray-800 text-lg mb-3">{{ $candidate->name }}</h3>

                            @if ($candidate->vision)
                                <div class="mb-3">
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <i data-lucide="eye" class="w-3.5 h-3.5 text-primary-500"></i>
                                        <span class="text-xs font-semibold text-primary-600 uppercase tracking-wider">Visi</span>
                                    </div>
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ Str::limit($candidate->vision, 150) }}</p>
                                </div>
                            @endif

                            @if ($candidate->mission)
                                <div>
                                    <div class="flex items-center gap-1.5 mb-1">
                                        <i data-lucide="target" class="w-3.5 h-3.5 text-emerald-500"></i>
                                        <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Misi</span>
                                    </div>
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ Str::limit($candidate->mission, 150) }}</p>
                                </div>
                            @endif
                        </div>
                    </label>
                @endforeach
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
    <div id="confirmModal" class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-50 hidden px-4">
        <div class="bg-white rounded-3xl shadow-2xl p-8 w-full max-w-md transform transition-all">
            <div class="text-center">
                <div class="w-16 h-16 bg-primary-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                    <i data-lucide="vote" class="w-8 h-8 text-primary-600"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 mb-2">Konfirmasi Pilihan Anda</h2>
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
                        class="flex-1 px-4 py-3.5 bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold rounded-2xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                        Ya, Kirim
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();

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
        });

        cancelBtn.addEventListener('click', () => {
            confirmModal.classList.add('hidden');
        });

        confirmBtn.addEventListener('click', () => {
            confirmModal.classList.add('hidden');
            voteForm.submit();
        });
    </script>
</body>
</html>
