<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Voter - E-Voting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        .pin-input { letter-spacing: 0.5em; text-align: center; font-size: 1.5rem; font-weight: 700; }
        .pin-input::placeholder { letter-spacing: normal; font-size: 0.875rem; font-weight: 400; }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-4px); box-shadow: 0 20px 40px -12px rgba(0,0,0,0.15); }
    </style>
</head>
<body class="bg-gradient-to-br from-emerald-50 via-white to-blue-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-lg">
        <!-- Header Card -->
        <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-emerald-500 to-emerald-700 rounded-3xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-emerald-500/30">
                <i data-lucide="vote" class="w-10 h-10 text-white"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-800">E-Voting</h1>
            <p class="text-gray-500 mt-1">Masukkan credential untuk memberikan suara</p>
        </div>

        <!-- Login Card -->
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 p-8 card-hover">
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

            <form method="POST" action="{{ route('vote.login.submit') }}" class="space-y-6">
                @csrf

                <!-- NIS Input -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">NIS / Student ID</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i data-lucide="hash" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="text" name="student_id" id="student_id" value="{{ old('student_id') }}" required autofocus
                            class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm focus:outline-none focus:ring-0 focus:border-emerald-500 transition-all"
                            placeholder="Masukkan NIS Anda">
                    </div>
                </div>

                <!-- PIN Input -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Token / PIN</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <i data-lucide="key-round" class="w-5 h-5 text-gray-400"></i>
                        </div>
                        <input type="text" name="token" id="token" value="{{ old('token') }}" required
                            maxlength="16" minlength="6" autocomplete="one-time-code" spellcheck="false"
                            style="text-transform:uppercase"
                            class="w-full pl-12 pr-4 py-3.5 bg-gray-50 border-2 border-gray-100 rounded-2xl text-sm focus:outline-none focus:ring-0 focus:border-emerald-500 transition-all pin-input tracking-[0.2em] font-mono"
                            placeholder="Masukkan token dari kartu pemilih">
                    </div>
                    <p class="text-xs text-gray-400 mt-2 text-center">Token dari kartu pemilih (terima token lama 6 digit &amp; token baru)</p>
                </div>

                <!-- Voting Event Select -->
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Event Pemilihan</label>
                    <div class="space-y-2">
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
                <button type="submit"
                    class="w-full bg-gradient-to-r from-emerald-600 to-emerald-700 text-white font-bold py-4 px-6 rounded-2xl hover:from-emerald-700 hover:to-emerald-800 focus:ring-4 focus:ring-emerald-500/20 transition-all shadow-lg shadow-emerald-500/25 text-sm uppercase tracking-wider">
                    Masuk untuk Memilih
                </button>
            </form>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center">
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-emerald-600 transition-colors">
                <i data-lucide="shield-check" class="w-4 h-4"></i>
                Login sebagai Admin
            </a>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
