<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Voter - E-Voting</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"></noscript>
    <style>
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.7s ease-out forwards; }

        .pin-input { letter-spacing: 0.5em; text-align: center; font-size: 1.5rem; font-weight: 700; }
        .pin-input::placeholder { letter-spacing: normal; font-size: 0.875rem; font-weight: 400; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -12px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="min-h-screen font-sans antialiased" style="background: linear-gradient(135deg, #064e3b 0%, #065f46 40%, #0e7490 100%);">

    <!-- Decorative floating shapes -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-10 left-10 w-64 h-64 bg-emerald-500/15 rounded-full blur-3xl animate-[float_10s_ease-in-out_infinite]"></div>
        <div class="absolute top-1/3 right-0 w-80 h-80 bg-teal-500/10 rounded-full blur-3xl animate-[float_12s_ease-in-out_infinite_2s]"></div>
        <div class="absolute bottom-0 left-1/4 w-[500px] h-[500px] bg-cyan-500/10 rounded-full blur-3xl animate-[float_14s_ease-in-out_infinite_4s]"></div>
    </div>

    <div class="relative z-10 min-h-screen flex flex-col items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-lg animate-fade-in">

            <!-- Header -->
            <div class="text-center mb-8">
                <div class="w-20 h-20 bg-white/10 backdrop-blur-sm rounded-3xl flex items-center justify-center mx-auto mb-4 border border-white/20 shadow-lg shadow-emerald-500/20">
                    <i data-lucide="vote" class="w-10 h-10 text-emerald-300"></i>
                </div>
                <h1 class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight">E-Voting Sekolah</h1>
                <p class="text-emerald-100/80 mt-2 text-sm sm:text-base">Masukkan credential Anda untuk memberikan suara</p>
            </div>

            <!-- Login Card -->
            <div class="bg-white rounded-3xl shadow-2xl shadow-black/10 p-6 sm:p-8 card-hover">
                <div class="mb-6 text-center">
                    <h2 class="text-xl font-bold text-gray-900">Login Pemilih</h2>
                    <p class="text-gray-500 text-sm">Pastikan Anda memiliki kartu pemilih resmi</p>
                </div>

                @if ($errors->any())
                    <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-2xl">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                        <div>
                            @foreach ($errors->all() as $error)
                                <p class="text-sm">{{ $error }}</p>
                            @endforeach
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('vote.login.submit') }}" class="space-y-5">
                    @csrf

                    <!-- NIS Input -->
                    <div>
                        <label for="student_id" class="block text-sm font-semibold text-gray-700 mb-2">NIS / Student ID</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="hash" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <input type="text" name="student_id" id="student_id" value="{{ old('student_id') }}" required autofocus autocomplete="username"
                                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm focus:outline-none focus:ring-0 focus:border-emerald-500 transition-all"
                                placeholder="Masukkan NIS Anda">
                        </div>
                    </div>

                    <!-- PIN Input -->
                    <div>
                        <label for="token" class="block text-sm font-semibold text-gray-700 mb-2">Token / PIN</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                <i data-lucide="key-round" class="w-5 h-5 text-gray-400"></i>
                            </div>
                            <input type="text" name="token" id="token" required
                                maxlength="16" minlength="6" autocomplete="one-time-code" spellcheck="false"
                                style="text-transform:uppercase"
                                class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm focus:outline-none focus:ring-0 focus:border-emerald-500 transition-all pin-input tracking-[0.2em] font-mono"
                                placeholder="Masukkan token dari kartu pemilih">
                        </div>
                        <p class="text-xs text-gray-400 mt-2 text-center">Token tertera pada kartu pemilih Anda</p>
                    </div>

                    <!-- Voting Event Select -->
                    <div>
                        <span id="event-label" class="block text-sm font-semibold text-gray-700 mb-2">Event Pemilihan</span>
                        <div class="space-y-2" role="radiogroup" aria-labelledby="event-label">
                            @forelse ($votingEvents as $event)
                                <label class="flex items-center gap-3 p-3 bg-gray-50 border-2 border-gray-100 rounded-2xl cursor-pointer hover:border-emerald-300 transition-all has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                                    <input type="radio" name="voting_event_id" value="{{ $event->id }}" required
                                        {{ old('voting_event_id') == $event->id ? 'checked' : '' }}
                                        class="w-4 h-4 text-emerald-600 focus:ring-emerald-500 border-gray-300">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-800">{{ $event->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $event->starts_at?->format('d/m/Y') }} - {{ $event->ends_at?->format('d/m/Y') }} · {{ $event->elections_count ?? $event->elections->count() }} pemilihan</p>
                                    </div>
                                </label>
                            @empty
                                <div class="text-center py-6 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200">
                                    <p class="text-sm text-gray-400">Tidak ada event pemilihan aktif</p>
                                    <p class="text-xs text-gray-400 mt-1">Hubungi panitia</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" @if($votingEvents->isEmpty()) disabled @endif
                        class="w-full bg-gradient-to-r from-emerald-600 to-teal-600 text-white font-bold py-4 px-6 rounded-2xl hover:from-emerald-700 hover:to-teal-700 focus:ring-4 focus:ring-emerald-500/20 transition-all shadow-lg shadow-emerald-500/25 text-sm uppercase tracking-wider disabled:opacity-40 disabled:cursor-not-allowed">
                        Masuk untuk Memilih
                    </button>
                </form>
            </div>

            <!-- Footer -->
            <div class="mt-6 text-center">
                <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm text-emerald-100/80 hover:text-white transition-colors bg-white/5 hover:bg-white/10 px-4 py-2 rounded-full backdrop-blur-sm border border-white/10">
                    <i data-lucide="shield-check" class="w-4 h-4"></i>
                    Login sebagai Admin
                </a>
            </div>

            <p class="text-center text-emerald-200/50 text-xs mt-8">
                &copy; {{ date('Y') }} E-Voting Sekolah. Hak cipta dilindungi.
            </p>
        </div>
    </div>
</body>
</html>
