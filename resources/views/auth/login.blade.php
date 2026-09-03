<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - E-Voting</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 50:'#eef2ff',100:'#e0e7ff',200:'#c7d2fe',300:'#a5b4fc',400:'#818cf8',500:'#6366f1',600:'#4f46e5',700:'#4338ca',800:'#3730a3',900:'#312e81',950:'#1e1b4b' },
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen">
        <!-- Left Panel - Branding -->
        <div class="hidden lg:flex lg:w-1/2 bg-gradient-to-br from-primary-600 via-primary-700 to-primary-900 relative overflow-hidden">
            <!-- Decorative elements -->
            <div class="absolute inset-0">
                <div class="absolute top-20 left-20 w-72 h-72 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute bottom-20 right-20 w-96 h-96 bg-white/5 rounded-full blur-3xl"></div>
                <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary-500/10 rounded-full blur-3xl"></div>
            </div>

            <div class="relative z-10 flex flex-col justify-center px-16 text-white">
                <div class="mb-8">
                    <div class="w-16 h-16 bg-white/10 backdrop-blur-sm rounded-2xl flex items-center justify-center mb-6 border border-white/20">
                        <i data-lucide="shield-check" class="w-8 h-8"></i>
                    </div>
                    <h1 class="text-4xl font-bold mb-3 tracking-tight">E-Voting</h1>
                    <p class="text-primary-200 text-lg">Sistem Pemilihan Digital yang Aman dan Transparan</p>
                </div>

                <div class="space-y-4 mt-8">
                    <div class="flex items-center gap-4 bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="w-10 h-10 bg-emerald-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="lock" class="w-5 h-5 text-emerald-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Keamanan Terjamin</p>
                            <p class="text-primary-200 text-xs">Enkripsi end-to-end untuk setiap suara</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="eye" class="w-5 h-5 text-blue-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Transparansi Penuh</p>
                            <p class="text-primary-200 text-xs">Audit log real-time untuk semua aktivitas</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 bg-white/5 backdrop-blur-sm rounded-xl p-4 border border-white/10">
                        <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="zap" class="w-5 h-5 text-purple-300"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Real-time</p>
                            <p class="text-primary-200 text-xs">Hasil pemilihan langsung terupdate</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Panel - Login Form -->
        <div class="w-full lg:w-1/2 flex items-center justify-center p-8">
            <div class="w-full max-w-md">
                <!-- Mobile brand -->
                <div class="lg:hidden text-center mb-8">
                    <div class="w-14 h-14 bg-primary-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="shield-check" class="w-7 h-7 text-white"></i>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-800">E-Voting</h1>
                </div>

                <div class="mb-8">
                    <h2 class="text-2xl font-bold text-gray-800">Selamat Datang</h2>
                    <p class="text-gray-500 mt-1">Masuk ke panel admin</p>
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

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Username atau Email</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="user" class="w-[18px] h-[18px] text-gray-400"></i>
                            </div>
                            <input type="text" name="login" id="login" value="{{ old('login') }}" required autofocus
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Masukkan username atau email">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="w-[18px] h-[18px] text-gray-400"></i>
                            </div>
                            <input type="password" name="password" id="password" required
                                class="w-full pl-11 pr-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                placeholder="Masukkan password">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-primary-600 border-gray-300 rounded focus:ring-primary-500">
                            <span class="text-sm text-gray-600">Ingat saya</span>
                        </label>
                        <a href="{{ route('password.request') }}" class="text-sm text-primary-600 hover:text-primary-700 font-medium">
                            Lupa password?
                        </a>
                    </div>

                    <button type="submit"
                        class="w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white font-semibold py-3 px-4 rounded-xl hover:from-primary-700 hover:to-primary-800 focus:ring-4 focus:ring-primary-500/20 transition-all shadow-lg shadow-primary-500/25">
                        Masuk
                    </button>
                </form>

                <div class="mt-8 pt-6 border-t border-gray-100 text-center">
                    <a href="/vote/login" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-primary-600 transition-colors">
                        <i data-lucide="vote" class="w-4 h-4"></i>
                        Login sebagai Voter
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script>lucide.createIcons();</script>
</body>
</html>
