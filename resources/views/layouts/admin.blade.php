<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Admin' }} - E-Voting</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h1 class="text-xl font-bold">E-Voting Admin</h1>
            </div>
            <nav class="mt-4">
                <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700' : '' }}">
                    Dashboard
                </a>
                <a href="{{ route('admin.elections.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.elections.*') ? 'bg-gray-700' : '' }}">
                    Pemilihan
                </a>
                <a href="{{ route('admin.voters.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.voters.*') ? 'bg-gray-700' : '' }}">
                    Pemilih
                </a>
                <a href="{{ route('admin.results.index') }}" class="block px-4 py-2 hover:bg-gray-700 {{ request()->routeIs('admin.results.*') ? 'bg-gray-700' : '' }}">
                    Hasil
                </a>
            </nav>
            <div class="absolute bottom-0 w-64 p-4">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-700">
                        Logout
                    </button>
                </form>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-6">
            @if (session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-4">
                    @foreach ($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>