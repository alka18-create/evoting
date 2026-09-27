<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'E-Voting Sekolah') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Font dimuat non-blocking (media=print): jaringan yang memblokir Google Fonts
         tidak menunda first paint; fallback system font tetap rapi. --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"></noscript>
    <style>
        [x-cloak] { display: none !important; }

        html { scroll-behavior: smooth; }
        /* Konten tidak tertutup navbar fixed saat pakai anchor (#features dll). */
        section[id] { scroll-margin-top: 5.5rem; }

        /* Fokus keyboard terlihat untuk semua link/tombol. */
        a:focus-visible, button:focus-visible {
            outline: 2px solid #a5b4fc;
            outline-offset: 3px;
            border-radius: 6px;
        }

        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: .6; } }

        /* Gradient teks — varian terang untuk latar gelap (kontras aman). */
        .gradient-text { background: linear-gradient(120deg, #2563eb 0%, #06b6d4 50%, #059669 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .gradient-text-hero { background: linear-gradient(120deg, #93c5fd 0%, #67e8f9 50%, #6ee7b7 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        /* Emas: di latar gelap pakai .gradient-gold; di latar terang pakai .gradient-gold-ink
           (emas terang di atas putih tidak lolos kontras). */
        .gradient-gold { background: linear-gradient(120deg, #fbbf24 0%, #f59e0b 50%, #d97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
        .gradient-gold-ink { background: linear-gradient(120deg, #92400e 0%, #b45309 50%, #d97706 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }

        /* Navbar: putih di atas hero gelap secara default (tetap terbaca tanpa Alpine);
           kelas nav-solid ditambahkan skrip vanilla saat scroll. */
        .nav-solid { background: rgba(255,255,255,.92) !important; backdrop-filter: blur(12px); box-shadow: 0 10px 25px -12px rgba(0,0,0,.15); border-bottom: 1px solid #f3f4f6; }
        .nav-solid .brand-text { color: #111827 !important; }
        .nav-solid .nav-link { color: #4b5563 !important; }
        .nav-solid .nav-link:hover { color: #4f46e5 !important; }
        .nav-solid .admin-link { color: #4f46e5 !important; }
        .nav-solid .admin-link:hover { background: #eef2ff !important; }
        /* Ikon hamburger: putih di hero gelap, gelap saat navbar menyala (nav-solid). */
        .nav-solid .nav-toggle { color: #111827 !important; background: #f3f4f6 !important; }

        .glass { background: rgba(255,255,255,0.1); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); }
        .hero-gradient { background: linear-gradient(135deg, #0f172a 0%, #155e75 50%, #164e63 100%); }
        .card-hover { transition: all 0.3s ease; }
        .card-hover:hover { transform: translateY(-8px); box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25); }

        /* Hormati preferensi kurangi-gerak dari sistem. */
        @media (prefers-reduced-motion: reduce) {
            html { scroll-behavior: auto; }
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body id="top" class="bg-white font-sans antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-4 focus:left-4 focus:z-[60] focus:px-4 focus:py-2 focus:bg-white focus:text-primary-700 focus:font-semibold focus:rounded-xl focus:shadow-lg">Lewati ke konten utama</a>

    {{-- Navbar --}}
    <nav id="lp-nav" aria-label="Navigasi utama" x-data="{ open: false }" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 lg:h-20">
                <a href="#top" class="flex items-center gap-2.5" aria-label="E-Voting — ke atas">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-cyan-600 flex items-center justify-center shadow-lg shadow-primary-500/25">
                        <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                    </div>
                    <span class="brand-text text-lg font-bold tracking-tight text-white">E-Voting</span>
                </a>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#features" class="nav-link text-sm font-medium transition-colors text-white/70 hover:text-white">Fitur</a>
                    <a href="#how-it-works" class="nav-link text-sm font-medium transition-colors text-white/70 hover:text-white">Cara Kerja</a>
                    <a href="#security" class="nav-link text-sm font-medium transition-colors text-white/70 hover:text-white">Keamanan</a>
                </div>
                {{-- CTA hanya tampil di md+; di bawah itu semua akses lewat menu mobile
                     (di layar sm–md tombol + hamburger berdesakan) --}}
                <div class="hidden md:flex items-center gap-3">
                    <a href="{{ route('login') }}" class="admin-link inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition-all text-white/90 hover:text-white hover:bg-white/10">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        Masuk Admin
                    </a>
                    <a href="{{ route('vote.login') }}" class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold rounded-xl transition-all bg-gradient-to-r from-primary-500 to-cyan-600 text-white shadow-lg shadow-primary-500/25 hover:shadow-xl hover:shadow-primary-500/30 hover:-translate-y-0.5">
                        <i data-lucide="vote" class="w-4 h-4"></i>
                        Masuk Pemilih
                    </a>
                </div>
                {{-- Tombol menu mobile: SATU ikon morphing. Posisi dasar & animasi pakai
                     inline style/:style (bukan class Tailwind) agar tetap benar walau
                     file CSS hasil build belum tersinkron. --}}
                <button type="button"
                        @click="open = !open; $el.closest('#lp-nav').dataset.menu = open ? '1' : '0'; $el.closest('#lp-nav').dispatchEvent(new Event('lp:menu'))"
                        :aria-expanded="open.toString()"
                        aria-controls="lp-mobile-menu"
                        class="nav-toggle md:hidden inline-flex items-center justify-center w-11 h-11 rounded-xl text-white hover:bg-white/10 transition-colors"
                        style="background: rgba(255,255,255,.08);">
                    <span class="sr-only">Buka menu navigasi</span>
                    <span class="relative block w-5 h-5" aria-hidden="true">
                        <span class="absolute left-0 h-0.5 w-5 rounded-full bg-current transition-all duration-200" style="top:4px" :style="open ? 'top:9px;transform:rotate(45deg)' : ''"></span>
                        <span class="absolute left-0 h-0.5 w-5 rounded-full bg-current transition-all duration-200" style="top:9px" :style="open ? 'opacity:0' : ''"></span>
                        <span class="absolute left-0 h-0.5 w-5 rounded-full bg-current transition-all duration-200" style="top:14px" :style="open ? 'top:9px;transform:rotate(-45deg)' : ''"></span>
                    </span>
                </button>
            </div>
        </div>
        {{-- Panel menu mobile: navigasi & aksi dipisah label, target sentuh besar, transisi halus --}}
        <div id="lp-mobile-menu" x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-200 origin-top"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150 origin-top"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             @click.outside="if (!$event.target.closest('#lp-nav')) open = false"
             @keydown.escape.window="open = false"
             class="md:hidden px-4 pb-4">
            <div class="rounded-2xl bg-white shadow-2xl shadow-black/10 border border-gray-100 overflow-hidden">
                <p class="px-5 pt-4 pb-1 text-[11px] font-bold uppercase tracking-widest text-gray-400">Navigasi</p>
                <a href="#features" @click="open = false" class="flex items-center gap-3 px-5 py-3 text-[15px] font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors">
                    <i data-lucide="sparkles" class="w-4 h-4 text-primary-500 shrink-0"></i>
                    Fitur
                </a>
                <a href="#how-it-works" @click="open = false" class="flex items-center gap-3 px-5 py-3 text-[15px] font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors">
                    <i data-lucide="route" class="w-4 h-4 text-primary-500 shrink-0"></i>
                    Cara Kerja
                </a>
                <a href="#security" @click="open = false" class="flex items-center gap-3 px-5 py-3 text-[15px] font-medium text-gray-700 hover:bg-primary-50 hover:text-primary-700 transition-colors">
                    <i data-lucide="shield" class="w-4 h-4 text-primary-500 shrink-0"></i>
                    Keamanan
                </a>
                <div class="mx-5 my-2 h-px bg-gray-100"></div>
                <p class="px-5 pb-1 text-[11px] font-bold uppercase tracking-widest text-gray-400">Masuk</p>
                <div class="px-5 pt-1 pb-5 space-y-2.5">
                    <a href="{{ route('vote.login') }}" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl text-sm font-bold bg-gradient-to-r from-primary-500 to-cyan-600 text-white shadow-lg shadow-primary-500/25 active:scale-[0.98] transition-transform">
                        <i data-lucide="vote" class="w-4 h-4"></i>
                        Masuk Pemilih
                    </a>
                    <a href="{{ route('login') }}" class="flex items-center justify-center gap-2 w-full py-3 rounded-xl text-sm font-bold text-primary-700 border border-primary-200 bg-primary-50/50 hover:bg-primary-50 transition-colors">
                        <i data-lucide="log-in" class="w-4 h-4"></i>
                        Masuk Admin
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <main id="main" tabindex="-1">

    {{-- Hero --}}
    <section class="hero-gradient relative min-h-screen flex items-center overflow-hidden">
        <div class="absolute inset-0 overflow-hidden">
            <div class="absolute top-20 left-10 w-72 h-72 bg-primary-500/20 rounded-full blur-3xl animate-pulse-slow"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-cyan-500/20 rounded-full blur-3xl animate-pulse-slow" style="animation-delay: 2s;"></div>
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
                        Voting <span class="gradient-text-hero">Aman</span>,<br>
                        <span class="text-white/90">Transparan</span>, &<br>
                        <span class="text-white/80">Modern</span>
                    </h1>
                    <p class="text-lg lg:text-xl text-white/60 max-w-lg mx-auto lg:mx-0 mb-10 leading-relaxed">
                        Platform e-voting berbasis web untuk pemilihan ketua OSIS, ketua kelas, dan berbagai kontes sekolah dengan keamanan kriptografi tingkat tinggi.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4 justify-center lg:justify-start">
                        <a href="{{ route('vote.login') }}" class="inline-flex items-center justify-center gap-2 px-8 py-4 text-base font-bold rounded-2xl bg-gradient-to-r from-primary-500 to-cyan-600 text-white shadow-2xl shadow-primary-500/30 hover:shadow-primary-500/50 hover:-translate-y-1 transition-all">
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
                            <div class="w-full h-full rounded-2xl bg-gradient-to-br from-primary-500/20 to-cyan-500/20 flex items-center justify-center">
                                <div class="text-center">
                                    <div class="w-20 h-20 mx-auto rounded-2xl bg-gradient-to-br from-primary-500 to-cyan-600 flex items-center justify-center mb-4 shadow-xl">
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
                                        <div class="w-8 h-8 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                                            <i data-lucide="eye" class="w-4 h-4 text-cyan-400"></i>
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
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-blue-600 via-cyan-600 to-emerald-600 text-white text-sm font-semibold mb-6 shadow-lg shadow-cyan-500/25">
                    <i data-lucide="sparkles" class="w-4 h-4"></i>
                    Fitur Unggulan
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black mb-6 leading-tight">
                    <span class="gradient-text">Semua Yang Kamu Butuhkan</span><br>
                    <span class="gradient-gold-ink">Untuk Voting Digital</span>
                </h2>
                <p class="text-lg text-gray-600">
                    Sistem e-voting lengkap dengan keamanan kriptografi, real-time results, dan antarmuka yang elegan.
                </p>
            </div>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @php
                    // Kelas warna statis (jangan interpolasi bg-{{color}} — akan ke-purge di build Tailwind).
                    $colorClasses = [
                        'emerald' => ['hover_border' => 'hover:border-emerald-200', 'hover_bg' => 'hover:bg-emerald-50/30', 'bg' => 'bg-emerald-100', 'text' => 'text-emerald-600', 'bar' => 'from-emerald-400 to-teal-500'],
                        'blue' => ['hover_border' => 'hover:border-blue-200', 'hover_bg' => 'hover:bg-blue-50/30', 'bg' => 'bg-blue-100', 'text' => 'text-blue-600', 'bar' => 'from-blue-400 to-indigo-500'],
                        'amber' => ['hover_border' => 'hover:border-amber-200', 'hover_bg' => 'hover:bg-amber-50/30', 'bg' => 'bg-amber-100', 'text' => 'text-amber-600', 'bar' => 'from-amber-300 to-yellow-500'],
                        'cyan' => ['hover_border' => 'hover:border-cyan-200', 'hover_bg' => 'hover:bg-cyan-50/30', 'bg' => 'bg-cyan-100', 'text' => 'text-cyan-600', 'bar' => 'from-cyan-400 to-sky-500'],
                        'rose' => ['hover_border' => 'hover:border-rose-200', 'hover_bg' => 'hover:bg-rose-50/30', 'bg' => 'bg-rose-100', 'text' => 'text-rose-600', 'bar' => 'from-rose-400 to-pink-500'],
                        'indigo' => ['hover_border' => 'hover:border-indigo-200', 'hover_bg' => 'hover:bg-indigo-50/30', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-600', 'bar' => 'from-indigo-400 to-blue-500'],
                    ];
                    $features = [
                        ['icon' => 'shield-check', 'title' => 'Keamanan Kriptografi', 'desc' => 'Enkripsi RSA-2048 dan hash SHA-256 memastikan setiap suara terenkripsi dan tidak dapat diubah.', 'color' => 'emerald'],
                        ['icon' => 'eye', 'title' => 'Transparansi Penuh', 'desc' => 'Audit trail lengkap untuk setiap suara. Setiap pemilih dapat memverifikasi suaranya.', 'color' => 'blue'],
                        ['icon' => 'zap', 'title' => 'Hasil Real-time', 'desc' => 'Monitoring suara masuk secara langsung dengan dashboard visual yang interaktif.', 'color' => 'amber'],
                        ['icon' => 'users', 'title' => 'Multi-Event', 'desc' => 'Buat multiple pemilihan sekaligus: ketua OSIS, ketua kelas, kontes, dan lainnya.', 'color' => 'cyan'],
                        ['icon' => 'key-round', 'title' => 'Kredensial Unik', 'desc' => 'Setiap pemilih mendapat credential unik yang hanya bisa digunakan sekali.', 'color' => 'rose'],
                        ['icon' => 'bar-chart-3', 'title' => 'Export Laporan', 'desc' => 'Unduh hasil voting dalam format PDF atau Excel untuk dokumentasi resmi.', 'color' => 'indigo'],
                    ];
                @endphp
                @foreach ($features as $i => $f)
                    @php $cc = $colorClasses[$f['color']]; @endphp
                    <div class="group p-8 rounded-3xl border border-gray-100 bg-white card-hover {{ $cc['hover_border'] }} {{ $cc['hover_bg'] }} overflow-hidden" style="animation-delay: {{ $i * 100 }}ms;">
                        <div class="h-1.5 -mx-8 -mt-8 mb-6 bg-gradient-to-r {{ $cc['bar'] }}"></div>
                        <div class="w-14 h-14 rounded-2xl {{ $cc['bg'] }} flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                            <i data-lucide="{{ $f['icon'] }}" class="w-7 h-7 {{ $cc['text'] }}"></i>
                        </div>
                        <h3 class="text-xl font-bold {{ $cc['text'] }} mb-3">{{ $f['title'] }}</h3>
                        <p class="text-gray-600 leading-relaxed">{{ $f['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- How It Works --}}
    <section id="how-it-works" class="py-24 lg:py-32 bg-gradient-to-b from-gray-50 to-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-16">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-gradient-to-r from-blue-600 to-cyan-600 text-white text-sm font-semibold mb-6 shadow-lg shadow-blue-500/25">
                    <i data-lucide="route" class="w-4 h-4"></i>
                    Cara Kerja
                </div>
                <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black mb-6">
                    <span class="gradient-text">Tiga Langkah</span> <span class="gradient-gold-ink">Mudah</span>
                </h2>
                <p class="text-lg text-gray-600">
                    Proses voting yang sederhana namun aman untuk semua pemilih.
                </p>
            </div>
            <div class="grid md:grid-cols-3 gap-8 lg:gap-12">
                @php
                    $steps = [
                        ['num' => '01', 'icon' => 'key-round', 'title' => 'Dapatkan Credential', 'desc' => 'Admin mengaktifkan akun dan mengirimkan credential voting unik ke setiap pemilih via WhatsApp/Email.', 'tile' => 'from-blue-500 to-indigo-600', 'shadow' => 'shadow-blue-500/30', 'title_color' => 'text-blue-700'],
                        ['num' => '02', 'icon' => 'vote', 'title' => 'Pilih Kandidat', 'desc' => 'Login dengan credential, lihat profil kandidat, dan pilih kandidat pilihanmu dengan satu klik.', 'tile' => 'from-cyan-500 to-sky-600', 'shadow' => 'shadow-cyan-500/30', 'title_color' => 'text-cyan-700'],
                        ['num' => '03', 'icon' => 'check-circle-2', 'title' => 'Verifikasi & Selesai', 'desc' => 'Verifikasi pilihanmu sebelum submit. Setelah itu, suara terenkripsi dan tidak bisa diubah.', 'tile' => 'from-emerald-500 to-teal-600', 'shadow' => 'shadow-emerald-500/30', 'title_color' => 'text-emerald-700'],
                    ];
                @endphp
                @foreach ($steps as $i => $step)
                    <div class="relative text-center group">
                        @if ($i < 2)
                            <div class="hidden md:block absolute top-16 left-[calc(50%_+_4rem)] w-[calc(100%_-_5rem)] h-px bg-gradient-to-r from-blue-300 to-cyan-300"></div>
                        @endif
                        <div class="relative z-10 inline-flex items-center justify-center w-32 h-32 rounded-3xl bg-gradient-to-br {{ $step['tile'] }} mb-8 group-hover:scale-110 transition-all shadow-xl {{ $step['shadow'] }}">
                            <i data-lucide="{{ $step['icon'] }}" class="w-12 h-12 text-white"></i>
                        </div>
                        <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-3 py-1 rounded-full bg-gradient-to-r from-amber-400 to-yellow-500 text-white text-xs font-bold z-20 shadow-md shadow-amber-500/30">
                            {{ $step['num'] }}
                        </div>
                        <h3 class="text-xl font-bold {{ $step['title_color'] }} mb-3">{{ $step['title'] }}</h3>
                        <p class="text-gray-600 leading-relaxed max-w-xs mx-auto">{{ $step['desc'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Security --}}
    <section id="security" class="py-24 lg:py-32 hero-gradient relative overflow-hidden">
        <div class="absolute inset-0">
            <div class="absolute top-0 right-0 w-96 h-96 bg-primary-500/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl"></div>
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
                        <span class="gradient-gold">Yang Terjamin</span>
                    </h2>
                    <p class="text-lg text-white/50 mb-10 leading-relaxed">
                        Setiap suara terenkripsi dengan RSA-2048 dan di-hash dengan SHA-256. Tidak ada yang bisa mengubah atau memanipulasi suara setelah dikirim.
                    </p>
                    <div class="space-y-6">
                        @php
                            $security = [
                                ['icon' => 'lock', 'title' => 'Enkripsi End-to-End', 'desc' => 'Data terenkripsi dari pemilih hingga database', 'accent' => 'text-emerald-300'],
                                ['icon' => 'fingerprint', 'title' => 'Autentikasi Kuat', 'desc' => 'Credential unik per pemilih, hanya bisa digunakan sekali', 'accent' => 'text-blue-300'],
                                ['icon' => 'database', 'title' => 'Immutable Ledger', 'desc' => 'Log audit permanen, setiap perubahan tercatat', 'accent' => 'text-cyan-300'],
                                ['icon' => 'timer', 'title' => 'Auto-Expire', 'desc' => 'Kredensial otomatis kedaluwarsa setelah voting selesai', 'accent' => 'text-amber-300'],
                            ];
                        @endphp
                        @foreach ($security as $s)
                            <div class="flex items-start gap-4 group">
                                <div class="w-12 h-12 rounded-xl glass flex items-center justify-center shrink-0 group-hover:bg-white/20 transition-colors">
                                    <i data-lucide="{{ $s['icon'] }}" class="w-5 h-5 text-white"></i>
                                </div>
                                <div>
                                    <h4 class="{{ $s['accent'] }} font-bold mb-1">{{ $s['title'] }}</h4>
                                    <p class="text-white/60 text-sm">{{ $s['desc'] }}</p>
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
                                    <div class="w-10 h-10 rounded-lg bg-cyan-500/20 flex items-center justify-center">
                                        <i data-lucide="eye" class="w-5 h-5 text-cyan-400"></i>
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
                    <div class="absolute bottom-0 left-0 w-64 h-64 bg-cyan-500/20 rounded-full blur-3xl"></div>
                </div>
                <div class="relative">
                    <h2 class="text-3xl sm:text-4xl lg:text-5xl font-black text-white mb-6">
                        Siap Memulai<br><span class="gradient-gold">Voting Digital?</span>
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

    </main>

    {{-- Footer --}}
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-2.5">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-br from-primary-500 to-cyan-600 flex items-center justify-center">
                        <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                    </div>
                    <span class="text-lg font-bold">E-Voting Sekolah</span>
                </div>
                <div class="text-gray-400 text-sm">
                    &copy; {{ date('Y') }} E-Voting Sekolah. Dibuat untuk pemilihan yang lebih baik.
                </div>
                <div class="flex items-center gap-4">
                    <a href="#top" class="inline-flex items-center gap-2 text-sm text-gray-400 hover:text-white transition-colors">
                        <i data-lucide="arrow-up" class="w-4 h-4"></i>
                        Kembali ke atas
                    </a>
                </div>
            </div>
        </div>
    </footer>

    {{-- Scroll-state navbar: skrip vanilla agar tidak andalkan Alpine
         (nav-solid saat scroll ATAU saat menu mobile terbuka). --}}
    <script>
        (function () {
            var nav = document.getElementById('lp-nav');
            if (!nav) return;
            function upd() {
                var menuOpen = nav.dataset.menu === '1';
                nav.classList.toggle('nav-solid', window.scrollY > 20 || menuOpen);
            }
            upd();
            window.addEventListener('scroll', upd, { passive: true });
            window.addEventListener('pageshow', upd);
            nav.addEventListener('lp:menu', upd);
        })();
        // Fallback render ikon Lucide: bundle terbaru me-render otomatis via
        // refreshIcons(); listener ini menutup celah bila bundle lama yang ter-load.
        document.addEventListener('DOMContentLoaded', function () {
            if (window.lucide && window.lucide.icons) window.lucide.createIcons({ icons: window.lucide.icons });
        });
        window.addEventListener('load', function () {
            if (window.lucide && window.lucide.icons) window.lucide.createIcons({ icons: window.lucide.icons });
        });
    </script>

</body>
</html>
