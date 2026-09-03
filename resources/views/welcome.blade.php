<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'E-Voting Sekolah') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Inter', 'sans-serif'] },
                    colors: {
                        primary: {
                            50: '#eef2ff', 100: '#e0e7ff', 200: '#c7d2fe', 300: '#a5b4fc',
                            400: '#818cf8', 500: '#6366f1', 600: '#4f46e5', 700: '#4338ca',
                            800: '#3730a3', 900: '#312e81', 950: '#1e1b4b',
                        }
                    },
                    animation: {
                        'fade-in-up': 'fadeInUp 0.8s ease-out',
                        'fade-in': 'fadeIn 1s ease-out',
                        'float': 'float 6s ease-in-out infinite',
                        'pulse-slow': 'pulse 4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
                    }
                }
            }
        }
    </script>
    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        .gradient-text { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .glass { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
        .hero-gradient { background: linear-gradient(135deg, #0f0c29 0%, #302b63 50%, #24243e 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-8px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }
    </style>
</head>
<body class="bg-white font-sans antialiased">
    {{-- Navbar --}}
    <nav class="fixed top-0 left-0 right-0 z-50 transition-all duration-300" x-data="{ scrolled: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 20)" :class="scrolled ? 'bg-white/90 backdrop-blur-lg shadow-lg border-b border-gray-100' : 'bg-transparent'">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center shadow-lg shadow-primary-500/25">
                        <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                    </div>
                    <span class="text-lg font-bold tracking-tight" :class="scrolled ? 'text-gray-900' : 'text-white'">E-Voting</span>
                </div>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="text-sm font-medium transition-colors" :class="scrolled ? 'text-gray-600 hover:text-primary-600' : 'text-white/70 hover:text-white'">Fitur</a>
                    <a href="#how-it-works" class="text-sm font-medium transition-colors" :class="scrolled ? 'text-gray-600 hover:text-primary-600' : 'text-white/70 hover:text-white'">Cara Kerja</a>
                    <a href="#security" class="text-sm font-medium transition-colors" :class="scrolled ? 'text-gray-600 hover:text-primary-600' : 'text-white/70 hover:text-white'">Keamanan</a>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition-all" :class="scrolled ? 'text-primary-600 hover:bg-primary-50' : 'text-white/90 hover:text-white hover:bg-white/10'">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        Masuk Admin
                    </a>
                    <a href="{{ route('vote.login') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition-all bg-gradient-to-r from-primary-500 to-purple-600 text-white shadow-lg shadow-primary-500/25 hover:shadow-xl hover:shadow-primary-500/30 hover:-translate-y-0.5">
                        <i data-lucide="vote" class="w-4 h-4"></i>
                        Masuk Pemilih
                    </a>
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero --}}
    <section class="hero-gradient relative min-h-screen flex items-center overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-72 h-72 bg-primary-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-purple-500/20 rounded-full blur-3xl animate-pulse-slow" style="animation-delay: 2s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-32 lg:py-0">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div class="text-center lg:text-left animate-fade-in-up">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass text-white/80 text-sm font-medium mb-8">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Sistem Voting Digital Terpercaya
                    </div>
                    <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black text-white leading-tight mb-6">
                        Voting <span class="gradient-text">Aman</span>,<br>
                        <span class="text-white/90">Transparan</span>, &<br>
                        <span class="text-white/80">Modern</span>
                    </h1>
                    <p class="text-lg lg:text-xl text-white/60 max-w-lg mx-auto lg:mx-0 mb-10 leading-relaxed">
                        Platform e-voting berbasis web untuk pemilihan ketua OSIS, ketua kelas, dan berbagai kontes sekolah dengan keamanan kriptografi tingkat tinggi.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="{{ route('vote.login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-bold rounded-2xl bg-gradient-to-r from-primary-500 to-purple-600 text-white shadow-2xl shadow-primary-500/30 hover:shadow-primary-500/50 hover:-translate-y-1 transition-all">
                            <i data-lucide="rocket" class="w-5 h-5"></i>
                            Mulai Voting Sekarang
                        </a>
                        <a href="#features" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-bold rounded-2xl glass text-white hover:bg-white/20 transition-all">
                            <i data-lucide="info" class="w-5 h-5"></i>
                            Pelajari Lebih Lanjut
                        </a>
                    </div>
                    <div class="flex items-center gap-8 mt-12 justify-center lg:justify-start">
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">100%</div>
                            <div class="text-xs text-white/50 uppercase tracking-wider">Aman</div>
                        </div>
                        <div class="w-px h-10 bg-white/20"></div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">Real-time</div>
                            <div class="text-xs text-white/50 uppercase tracking-wider">Hasil</div>
                        </div>
                        <div class="w-px h-10 bg-white/20"></div>
                        <div class="text-center">
                            <div class="text-2xl font-bold text-white">24/7</div>
                            <div class="text-xs text-white/50 uppercase tracking-wider">Online</div>
                        </div>
                    </div>
                </div>
                <div class="hidden lg:flex justify-center animate-fade-in">
                    <div class="relative">
                        <div class="w-80 h-80 xl:w-96 xl:h-96 rounded-3xl glass p-8 animate-float">
                            <div class="w-full h-full rounded-2xl bg-gradient-to-br from-primary-500/20 to-purple-500/20 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center mb-4 shadow-xl">
                                        <i data-lucide="vote" class="w-10 h-10 text-white"></i>
                                    </div>
                                    <div class="text-white font-bold text-lg">E-Voting</div>
                                    <div class="text-white/50 text-sm">Sistem Digital</div>
                                    <div class="mt-4 flex justify-center gap-2">
                                        <div class="w-8 h-8 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                            <i data-lucide="shield-check" class="w-4 h-4 text-emerald-400"></i>
                                        </div>
                                        <div class="w-8 h-8 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                            <i data-lucide="lock" class="w-4 h-4 text-blue-400"></i>
                                        </div>
                                        <div class="w-8 h-8 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                            <i data-lucide="eye" class="w-4 h-4 text-purple-400"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-24 bg-gradient-to-t from-white to-transparent"></div>
    </section>

    {{-- Features --}}
    <section id="features" class="py-24 lg:py-32 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-primary-50 text-primary-600 text-sm font-semibold mb-6">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    Fitur Unggulan
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-gray-900 mb-6">
                    Semua Yang Kamu Butuhkan<br>
                    <span class="gradient-text">Untuk Voting Digital</span>
                </h2>
                <p class="text-lg text-gray-500">
                    Sistem e-voting lengkap dengan keamanan kriptografi, real-time results, dan antarmuka yang elegan.
                </p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    $features = [
                        ['icon' => 'shield-check', 'title' => 'Keamanan Kriptografi', 'desc' => 'Enkripsi RSA-2048 dan hash SHA-256 memastikan setiap suara terenkripsi dan tidak dapat diubah.', 'color' => 'emerald'],
                        ['icon' => 'eye', 'title' => 'Transparansi Penuh', 'desc' => 'Audit trail lengkap untuk setiap suara. Setiap pemilih dapat memverifikasi suaranya.', 'color' => 'blue'],
                        ['icon' => 'zap', 'title' => 'Hasil Real-time', 'desc' => 'Monitoring suara masuk secara langsung dengan dashboard visual yang interaktif.', 'color' => 'amber'],
                        ['icon' => 'users', 'title' => 'Multi-Event', 'desc' => 'Buat multiple pemilihan sekaligus: ketua OSIS, ketua kelas, kontes, dan lainnya.', 'color' => 'purple'],
                        ['icon' => 'key-round', 'title' => 'Kredensial Unik', 'desc' => 'Setiap pemilih mendapat credential unik yang hanya bisa digunakan sekali.', 'color' => 'rose'],
                        ['icon' => 'bar-chart-3', 'title' => 'Export Laporan', 'desc' => 'Unduh hasil voting dalam format PDF atau Excel untuk dokumentasi resmi.', 'color' => 'indigo'],
                    ];
                @endphp
                @foreach ($features as $i => $f)
                    <div class="group p-8 rounded-3xl border border-gray-100 bg-white card-hover hover:border-{{ $f['color'] }}-200 hover:bg-{{ $f['color'] }}-50/30" style="animation-delay: {{ $i * 100 }}ms;">
                        <div class="w-14 h-14 rounded-2xl bg-{{ $f['color'] }}-100 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i data-lucide="{{ $f['icon'] }}" class="w-7 h-7 text-{{ $f['color'] }}-600"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $f['title'] }}</h3>
                        <p class="text-gray-500 leading-relaxed">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section id="how-it-works" class="py-24 lg:py-32 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-purple-50 text-purple-600 text-sm font-semibold mb-6">
                    <i data-lucide="route" class="w-4 h-4"></i>
                    Cara Kerja
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-gray-900 mb-6">
                    Tiga Langkah <span class="gradient-text">Mudah</span>
                </h2>
                <p class="text-lg text-gray-500">
                    Proses voting yang sederhana namun aman untuk semua pemilih.
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                @php
                    $steps = [
                        ['num' => '01', 'icon' => 'key-round', 'title' => 'Dapatkan Credential', 'desc' => 'Admin mengaktifkan akun dan mengirimkan credential voting unik ke setiap pemilih via WhatsApp/Email.'],
                        ['num' => '02', 'icon' => 'vote', 'title' => 'Pilih Kandidat', 'desc' => 'Login dengan credential, lihat profil kandidat, dan pilih kandidat pilihanmu dengan satu klik.'],
                        ['num' => '03', 'icon' => 'check-circle-2', 'title' => 'Verifikasi & Selesai', 'desc' => 'Verifikasi pilihanmu sebelum submit. Setelah itu, suara terenkripsi dan tidak bisa diubah.'],
                    ];
                @endphp
                @foreach ($steps as $i => $step)
                    <div class="relative text-center group">
                        @if ($i < 2)
                            <div class="hidden md:block absolute top-16 left-[60%] w-[80%] h-px bg-gradient-to-r from-primary-200 to-purple-200"></div>
                        @endif
                        <div class="relative z-10 inline-flex items-center justify-center w-32 h-32 rounded-3xl bg-white border-2 border-primary-100 mb-8 group-hover:scale-110 group-hover:border-primary-300 transition-all shadow-lg shadow-primary-500/5">
                            <i data-lucide="{{ $step['icon'] }}" class="w-12 h-12 text-primary-500"></i>
                        </div>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-gradient-to-r from-primary-500 to-purple-600 text-white text-xs font-bold z-20">
                            {{ $step['num'] }}
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-3">{{ $step['title'] }}</h3>
                        <p class="text-gray-500 leading-relaxed max-w-xs mx-auto">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Security --}}
    <section id="security" class="py-24 lg:py-32 hero-gradient relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-0 right-0 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-purple-500/10 rounded-full blur-3xl"></div>
        </div>
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full glass text-white/80 text-sm font-semibold mb-6">
                        <i data-lucide="shield" class="w-4 h-4"></i>
                        Keamanan Tingkat Tinggi
                    </div>
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white mb-6 leading-tight">
                        Keamanan<br>
                        <span class="text-white/70">Yang Terjamin</span>
                    </h2>
                    <p class="text-lg text-white/50 mb-10 leading-relaxed">
                        Setiap suara terenkripsi dengan RSA-2048 dan di-hash dengan SHA-256. Tidak ada yang bisa mengubah atau memanipulasi suara setelah dikirim.
                    </p>
                    <div class="space-y-6">
                        @php
                            $security = [
                                ['icon' => 'lock', 'title' => 'Enkripsi End-to-End', 'desc' => 'Data terenkripsi dari pemilih hingga database'],
                                ['icon' => 'fingerprint', 'title' => 'Autentikasi Kuat', 'desc' => 'Credential unik per pemilih, hanya bisa digunakan sekali'],
                                ['icon' => 'database', 'title' => 'Immutable Ledger', 'desc' => 'Log audit permanen, setiap perubahan tercatat'],
                                ['icon' => 'timer', 'title' => 'Auto-Expire', 'desc' => 'Kredensial otomatis kedaluwarsa setelah voting selesai'],
                            ];
                        @endphp
                        @foreach ($security as $s)
                            <div class="flex items-start gap-4 group">
                                <div class="w-12 h-12 rounded-xl glass flex items-center justify-center shrink-0 group-hover:bg-white/20 transition-colors">
                                    <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h4 class="text-white font-bold mb-1">{{ $s['title'] }}</h4>
                                    <p class="text-white/50 text-sm">{{ $s['desc'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <div class="hidden lg:flex justify-center">
                    <div class="relative">
                        <div class="w-80 h-80 rounded-3xl glass p-8 animate-float" style="animation-delay: 1s;">
                            <div class="space-y-4">
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/10">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-500/20 flex items-center justify-center">
                                        <i data-lucide="shield-check" class="w-5 h-5 text-emerald-400"></i>
                                    </div>
                                    <div>
                                        <div class="text-white text-sm font-semibold">Enkripsi Aktif</div>
                                        <div class="text-white/50 text-xs">RSA-2048 + AES-256</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/10">
                                    <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                                        <i data-lucide="hash" class="w-5 h-5 text-blue-400"></i>
                                    </div>
                                    <div>
                                        <div class="text-white text-sm font-semibold">Hash Verified</div>
                                        <div class="text-white/50 text-xs">SHA-256 Integrity</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/10">
                                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                        <i data-lucide="eye" class="w-5 h-5 text-purple-400"></i>
                                    </div>
                                    <div>
                                        <div class="text-white text-sm font-semibold">Audit Trail</div>
                                        <div class="text-white/50 text-xs">100% Transparan</div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-white/10">
                                    <div class="w-10 h-10 rounded-lg bg-amber-500/20 flex items-center justify-center">
                                        <i data-lucide="timer" class="w-5 h-5 text-amber-400"></i>
                                    </div>
                                    <div>
                                        <div class="text-white text-sm font-semibold">Time-Locked</div>
                                        <div class="text-white/50 text-xs">Auto-Expire Kredensial</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-24 lg:py-32 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="relative p-12 lg:p-16 rounded-[2rem] hero-gradient overflow-hidden">
                <div class="absolute inset-0">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-primary-500/20 rounded-full blur-3xl"></div>
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-purple-500/20 rounded-full blur-3xl"></div>
                </div>
                <div class="relative">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white mb-6">
                        Siap Memulai<br>Voting Digital?
                    </h2>
                    <p class="text-lg text-white/60 mb-10 max-w-xl mx-auto">
                        Bergabung dengan ratusan sekolah yang sudah menggunakan E-Voting untuk pemilihan yang lebih aman dan transparan.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center">
                        <a href="{{ route('vote.login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-bold rounded-2xl bg-white text-primary-600 shadow-2xl hover:shadow-white/25 hover:-translate-y-1 transition-all">
                            <i data-lucide="vote" class="w-5 h-5"></i>
                            Mulai Voting
                        </a>
                        <a href="{{ route('login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-bold rounded-2xl glass text-white hover:bg-white/20 transition-all">
                            <i data-lucide="settings" class="w-5 h-5"></i>
                            Panel Admin
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-purple-600 flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                    </div>
                    <span class="text-lg font-bold">E-Voting Sekolah</span>
                </div>
                <div class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} E-Voting Sekolah. Dibuat untuk pemilihan yang lebih baik.
                </div>
                <div class="flex items-center gap-4">
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="github" class="w-5 h-5"></i>
                    </a>
                    <a href="#" class="text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="mail" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
