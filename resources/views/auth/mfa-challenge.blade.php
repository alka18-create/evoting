<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Dua Langkah - E-Voting</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center p-8">
        <div class="w-full max-w-md bg-white rounded-2xl shadow-sm border p-8">
            <h2 class="text-xl font-bold text-gray-800">Verifikasi Dua Langkah</h2>
            <p class="text-sm text-gray-500 mt-1">Masukkan kode 6 digit dari aplikasi authenticator, atau salah satu kode pemulihan.</p>

            @if ($errors->any())
                <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl">
                    @foreach ($errors->all() as $error)
                        <p class="text-sm">{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            <form method="POST" action="{{ route('mfa.challenge.verify') }}" class="mt-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Kode verifikasi</label>
                    <input type="text" name="code" required autofocus autocomplete="one-time-code" inputmode="numeric"
                        maxlength="32" placeholder="123456 atau XXXX-XXXX"
                        class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono tracking-[0.3em] text-center focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500">
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white font-semibold py-3 rounded-xl hover:bg-indigo-700 transition-all">
                    Verifikasi
                </button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="mt-4 text-center">
                @csrf
                <button class="text-sm text-gray-500 hover:text-gray-700">Batal — keluar</button>
            </form>
        </div>
    </div>
</body>
</html>
