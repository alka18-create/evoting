<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - E-Voting</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" media="print" onload="this.media='all'">
    <noscript><link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap"></noscript>
    <style>
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-20px); } }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeIn 0.7s ease-out forwards; }
    </style>
</head>
<body class="bg-gray-50 font-sans antialiased">

    <!-- Mobile Decorative Header (hanya tampil di layar kecil) -->
    <div class="lg:hidden relative overflow-hidden pt-10 pb-16 text-white" style="background: linear-gradient(135deg, #0f172a 0%, #155e75 50%, #164e63 100%);">
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="absolute -top-10 -right-10 w-40 h-40 bg-cyan-500/25 rounded-full blur-2xl"></div>
            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-60 h-60 bg-primary-500/20 rounded-full blur-3xl"></div>
        </div>
        <div class="relative z-10 text-center px-6">
            <div class="w-16 h-16 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center mx-auto mb-4 border border-white/20 shadow-lg shadow-cyan-500/10">
                <i data-lucide="shield-check" class="w-8 h-8 text-cyan-300"></i>
            </div>
            <h1 class="text-2xl font-bold tracking-tight">E-Voting Sekolah</h1>
            <p class="text-cyan-100/80 text-sm mt-1">Panel Admin</p>
        </div>
    </div>

    <div class="flex min-h-screen">
        <!-- Left Panel - Branding -->
        <div class="hidden lg:flex lg:w-1/2 relative overflow-hidden" style="background: linear-gradient(135deg, #0f172a 0%, #155e75 50%, #164e63 100%);">
            <!-- Decorative floating shapes -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none">
                <div class="absolute -top-20 -left-20 w-96 h-96 bg-cyan-500/10 rounded-full blur-3xl animate-[float_8s_ease-in-out_infinite]"></div>
                <div class="absolute top-1/3 right-0 w-80 h-80 bg-primary-500/15 rounded-full blur-3xl animate-[float_10s_ease-in-out_infinite_2s]"></div>
                <div class="absolute bottom-0 left-1/4 w-[500px] h-[500px] bg-sky-500/10 rounded-full blur-3xl animate-[float_12s_ease-in-out_infinite_4s]"></div>
            </div>

            <div class="relative z-10 flex flex-col justify-center px-16 py-10 text-white h-full overflow-y-auto">
                <div class="mb-10 animate-fade-in">
                    <div class="w-16 h-16 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center mb-8 border border-white/20 shadow-lg shadow-cyan-500/10">
                        <i data-lucide="shield-check" class="w-8 h-8 text-cyan-300"></i>
                    </div>
                    <h1 class="text-4xl font-extrabold mb-3 tracking-tight leading-tight">
                        E-Voting Sekolah
                    </h1>
                    <p class="text-cyan-100/80 text-lg max-w-md leading-relaxed">
                        Panel admin untuk mengelola pemilihan ketua OSIS, ketua kelas, dan event voting lainnya dengan aman dan transparan.
                    </p>
                </div>

                <!-- Visual: ilustrasi perangkat + emblem keamanan -->
                <div class="my-6 animate-fade-in" style="animation-delay: 0.05s;">
                    <div class="relative max-w-md">
                        <img src="{{ asset('images/login-devices.webp') }}"
                             alt="Ilustrasi laptop hasil voting, smartphone token, dan emblem keamanan E-Voting"
                             class="w-full rounded-3xl border border-white/20 shadow-2xl shadow-black/40"
                             loading="eager" fetchpriority="high">
                        <div class="absolute -bottom-4 left-6 flex items-center gap-2 rounded-full bg-emerald-500/90 px-4 py-2 text-xs font-bold text-white shadow-lg">
                            <i data-lucide="shield-check" class="w-4 h-4"></i> Aman &amp; Terenkripsi
                        </div>
                    </div>
                </div>

                <div class="space-y-4 mt-2 animate-fade-in" style="animation-delay: 0.1s;">
                    <div class="flex items-center gap-4 bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition-colors">
                        <div class="w-10 h-10 bg-emerald-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i data-lucide="lock" class="w-5 h-5 text-emerald-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Keamanan Terjamin</p>
                            <p class="text-cyan-100/70 text-xs">Enkripsi end-to-end untuk setiap suara</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition-colors">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i data-lucide="eye" class="w-5 h-5 text-blue-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Transparansi Penuh</p>
                            <p class="text-cyan-100/70 text-xs">Audit log real-time untuk semua aktivitas</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/5 backdrop-blur-sm rounded-2xl p-4 border border-white/10 hover:bg-white/10 transition-colors">
                        <div class="w-10 h-10 bg-cyan-500/20 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i data-lucide="zap" class="w-5 h-5 text-cyan-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Hasil Real-time</p>
                            <p class="text-cyan-100/70 text-xs">Pantau partisipasi dan hasil secara langsung</p>
                        </div>
                    </div>
                </div>

                <div class="mt-auto pt-12 text-cyan-200/60 text-sm animate-fade-in" style="animation-delay: 0.2s;">
                    &copy; {{ date('Y') }} E-Voting Sekolah
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-6 sm:p-12 relative -mt-10 lg:mt-0">
            <!-- Subtle background pattern + floating shapes -->
            <div class="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_var(--tw-gradient-stops))] from-cyan-100/50 via-gray-50 to-gray-50"></div>
            <div class="absolute inset-0 overflow-hidden pointer-events-none lg:hidden">
                <div class="absolute top-10 right-4 w-24 h-24 bg-cyan-300/30 rounded-full blur-2xl animate-[float_6s_ease-in-out_infinite]"></div>
                <div class="absolute bottom-20 left-4 w-32 h-32 bg-primary-300/20 rounded-full blur-2xl animate-[float_8s_ease-in-out_infinite_1s]"></div>
            </div>

            <div class="relative w-full max-w-md animate-fade-in">
                <!-- Desktop-only brand text -->
                <div class="hidden lg:block text-center mb-10">
                    <div class="w-16 h-16 bg-gradient-to-br from-primary-600 to-cyan-600 rounded-2xl flex items-center justify-center mx-auto mb-4 shadow-lg shadow-cyan-500/20">
                        <i data-lucide="shield-check" class="w-8 h-8 text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900">E-Voting Sekolah</h1>
                    <p class="text-gray-500 text-sm mt-1">Panel Admin</p>
                </div>

                <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 border border-gray-100 p-8 sm:p-10">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-900 mb-2">Selamat Datang</h2>
                        <p class="text-gray-500">Masuk ke panel admin untuk mengelola voting.</p>
                    </div>

                    @if (session('status'))
                        <div class="mb-6 flex items-start gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl">
                            <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0 mt-0.5"></i>
                            <p class="text-sm">{{ session('status') }}</p>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-6 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                            <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                            <div>
                                @foreach ($errors->all() as $error)
                                    <p class="text-sm">{{ $error }}</p>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="space-y-6">
                        @csrf
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Username atau Email</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i data-lucide="user" class="w-[18px] h-[18px] text-gray-400"></i>
                                </div>
                                <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                                    class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                    placeholder="Masukkan username atau email">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Password</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                    <i data-lucide="lock" class="w-[18px] h-[18px] text-gray-400"></i>
                                </div>
                                <input type="password" name="password" id="password" required
                                    class="w-full pl-11 pr-4 py-3.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                    placeholder="Masukkan password">
                            </div>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input type="checkbox" name="remember" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                                <span class="text-sm text-gray-600">Ingat saya</span>
                            </label>
                            <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:text-primary-700 font-semibold">
                                Lupa password?
                            </a>
                        </div>

                        <button type="submit"
                            class="w-full bg-gradient-to-r from-primary-600 to-cyan-600 text-white font-bold py-3.5 px-4 rounded-xl hover:from-primary-700 hover:to-cyan-700 focus:ring-4 focus:ring-primary-500/20 transition-all shadow-lg shadow-primary-500/25">
                            Masuk
                        </button>
                    </form>

                    <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                        <a href="{{ route('vote.login') }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-primary-600 transition-colors font-medium">
                            <i data-lucide="vote" class="w-4 h-4"></i>
                            Login sebagai Voter
                        </a>
                    </div>
                </div>

                <p class="text-center text-gray-400 text-xs mt-8">
                    &copy; {{ date('Y') }} E-Voting Sekolah. Hak cipta dilindungi.
                </p>
            </div>
        </div>
    </div>
</body>
</html>
