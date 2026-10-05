<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin' }} - E-Voting</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .sidebar-link { transition: all 0.2s ease; }
        /* Elemen Alpine (dropdown, sidebar) disembunyikan sebelum Alpine init. */
        [x-cloak] { display: none !important; }
        .sidebar-link:hover { transform: translateX(4px); }
        .sidebar-link.active { background: linear-gradient(135deg, rgba(99,102,241,0.2), rgba(99,102,241,0.05)); border-right: 3px solid #6366f1; }
        .stat-card { transition: all 0.3s ease; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1); }
        .fade-in { animation: fadeIn 0.3s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .sidebar-transition { transition: width 0.3s ease, transform 0.3s ease, opacity 0.3s ease; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex min-h-screen" x-data="{ sidebarOpen: localStorage.getItem('sidebarOpen') !== 'false' }">
        <!-- Sidebar (lebar penuh dikontrol Alpine agar tidak bentrok dengan class statis) -->
        <aside
            class="sidebar-transition bg-gradient-to-b from-gray-900 via-gray-800 to-gray-900 text-white flex flex-col shadow-xl fixed top-0 left-0 h-full z-30"
            :class="sidebarOpen ? 'w-64' : 'w-0 -translate-x-full opacity-0 overflow-hidden'"
        >
            <div class="w-64 flex flex-col h-full">
                <!-- Brand (tombol hide/show hanya ada di topbar) -->
                <div class="p-5 border-b border-gray-700/50 flex items-center gap-3">
                    <div class="w-10 h-10 bg-gradient-to-br from-primary-400 to-primary-600 rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                        <i data-lucide="shield-check" class="w-5 h-5 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold tracking-tight">E-Voting</h1>
                        <p class="text-[11px] text-gray-300 uppercase tracking-widest">Admin Panel</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
                    <a href="{{ route('admin.dashboard') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.dashboard') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="layout-dashboard" class="w-[18px] h-[18px]"></i>
                        Dashboard
                    </a>
                    <a href="{{ route('admin.voting-events.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.voting-events.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="calendar-range" class="w-[18px] h-[18px]"></i>
                        Event Pemilihan
                    </a>
                    <a href="{{ route('admin.organizations.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.organizations.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="building-2" class="w-[18px] h-[18px]"></i>
                        Organisasi
                    </a>
                    <a href="{{ route('admin.elections.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.elections.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="vote" class="w-[18px] h-[18px]"></i>
                        Pemilihan
                    </a>
                    <a href="{{ route('admin.voters.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.voters.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="users" class="w-[18px] h-[18px]"></i>
                        Pemilih
                    </a>
                    <a href="{{ route('admin.tokens.overview') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.tokens.overview') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="key-round" class="w-[18px] h-[18px]"></i>
                        Token Pemilih
                    </a>
                    @if (auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.users.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.users.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                            <i data-lucide="settings-2" class="w-[18px] h-[18px]"></i>
                            Pengguna
                        </a>
                    @endif
                    <a href="{{ route('admin.results.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.results.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="bar-chart-3" class="w-[18px] h-[18px]"></i>
                        Hasil
                    </a>
                    <a href="{{ route('admin.audit-logs.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.audit-logs.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="scroll-text" class="w-[18px] h-[18px]"></i>
                        Catatan Aktivitas
                    </a>

                    <div class="pt-3 mt-3 border-t border-gray-700/50">
                        <p class="px-3 mb-2 text-[11px] font-semibold text-gray-400 uppercase tracking-widest">Alat</p>
                    </div>
                    <a href="{{ route('admin.scan.show') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.scan.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                        <i data-lucide="scan-line" class="w-[18px] h-[18px]"></i>
                        Scan QR
                    </a>
                    @if (auth()->user()->isSuperAdmin())
                        <a href="{{ route('admin.backups.index') }}" class="sidebar-link flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium {{ request()->routeIs('admin.backups.*') ? 'active text-white' : 'text-gray-300 hover:text-white hover:bg-white/5' }}">
                            <i data-lucide="hard-drive" class="w-[18px] h-[18px]"></i>
                            Backup
                        </a>
                    @endif
                </nav>

                <!-- User Info + Logout -->
                <div class="p-3 border-t border-gray-700/50">
                    <a href="{{ route('admin.profile.edit') }}" class="flex items-center gap-3 px-3 py-2 mb-2 rounded-lg hover:bg-white/5 transition-colors">
                        <div class="w-8 h-8 bg-primary-500/20 rounded-full flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-primary-300">{{ substr(auth()->user()->name, 0, 1) }}</span>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-gray-300 uppercase">{{ auth()->user()->role->label() }}</p>
                        </div>
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm font-medium text-gray-400 hover:text-white hover:bg-white/5 transition-colors">
                            <i data-lucide="log-out" class="w-[18px] h-[18px]"></i>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <!-- Main Content (margin penuh dikontrol Alpine) -->
        <main class="flex-1 overflow-auto sidebar-transition" :class="sidebarOpen ? 'ml-64' : 'ml-0'">
            <!-- Top bar -->
            <div class="bg-white border-b border-gray-200 px-6 py-4 sticky top-0 z-20">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button
                            @click="sidebarOpen = !sidebarOpen; localStorage.setItem('sidebarOpen', sidebarOpen)"
                            class="p-2 rounded-lg hover:bg-gray-100 transition-colors text-gray-500 hover:text-gray-700"
                            aria-label="Alihkan sidebar"
                            :title="sidebarOpen ? 'Sembunyikan sidebar' : 'Tampilkan sidebar'"
                        >
                            <i data-lucide="panel-left" class="w-5 h-5" x-show="!sidebarOpen"></i>
                            <i data-lucide="panel-left-close" class="w-5 h-5" x-show="sidebarOpen"></i>
                        </button>
                        <h2 class="text-lg font-semibold text-gray-800">{{ $title ?? 'Dashboard' }}</h2>
                    </div>
                    <div class="text-sm text-gray-500">{{ now()->translatedFormat('l, d F Y') }}</div>
                </div>
            </div>

            <!-- Content -->
            <div class="p-6 fade-in">
                @if (session('success'))
                    <div class="mb-4 flex items-center gap-3 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-xl" x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 4000)">
                        <i data-lucide="check-circle" class="w-5 h-5 text-green-500 flex-shrink-0"></i>
                        <p class="flex-1 text-sm font-medium">{{ session('success') }}</p>
                        <button onclick="this.parentElement.remove()" class="text-green-500 hover:text-green-700"><i data-lucide="x" class="w-4 h-4"></i></button>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                        <i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0 mt-0.5"></i>
                        <div class="flex-1">
                            @foreach ($errors->all() as $error)
                                <p class="text-sm">{{ $error }}</p>
                            @endforeach
                        </div>
                        <button onclick="this.parentElement.remove()" class="text-red-500 hover:text-red-700"><i data-lucide="x" class="w-4 h-4"></i></button>
                    </div>
                @endif

                {{ $slot }}
            </div>
        </main>
    </div>

    <script>
        // Inisialisasi Lucide terpusat: refresh juga setelah Livewire morph/navigasi
        // agar ikon di konten dinamis (toast, modal, komponen Livewire) ikut render.
        function refreshIcons(){ if (window.lucide && window.lucide.icons) window.lucide.createIcons({ icons: window.lucide.icons }); }
        refreshIcons();
        document.addEventListener('DOMContentLoaded', refreshIcons);
        document.addEventListener('livewire:navigated', refreshIcons);
        document.addEventListener('livewire:morph-updated', refreshIcons);
        document.addEventListener('alpine:initialized', refreshIcons);
    </script>
</body>
</html>
