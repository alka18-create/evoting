<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sudah Memilih - E-Voting</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-gray-50 via-white to-amber-50 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-3xl shadow-xl shadow-gray-200/50 overflow-hidden text-center">
            <!-- Header -->
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-8 py-10">
                <div class="w-20 h-20 bg-white/20 rounded-3xl flex items-center justify-center mx-auto mb-4 backdrop-blur-sm">
                    <i data-lucide="clock" class="w-10 h-10 text-white"></i>
                </div>
                <h1 class="text-2xl font-bold text-white mb-1">Sudah Memilih</h1>
                <p class="text-amber-100 text-sm">Anda telah memberikan suara</p>
            </div>

            <!-- Content -->
            <div class="px-8 py-8">
                <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 mb-6">
                    <div class="flex items-center justify-center gap-3 mb-3">
                        <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                        <span class="font-semibold text-amber-800">Perhatian</span>
                    </div>
                    <p class="text-sm text-amber-700 leading-relaxed">
                        Anda telah memberikan suara untuk pemilihan ini. Setiap pemilih hanya dapat memberikan suara <strong>satu kali</strong>.
                    </p>
                </div>

                <div class="space-y-3 mb-6">
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="shield-check" class="w-4 h-4 text-gray-500"></i>
                        </div>
                        <span>Suara Anda tercatat aman</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="lock" class="w-4 h-4 text-gray-500"></i>
                        </div>
                        <span>Identitas Anda dirahasiakan</span>
                    </div>
                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <div class="w-8 h-8 bg-gray-100 rounded-lg flex items-center justify-center flex-shrink-0">
                            <i data-lucide="check-circle" class="w-4 h-4 text-gray-500"></i>
                        </div>
                        <span>Hasil akan diumumkan setelah pemilihan selesai</span>
                    </div>
                </div>

                <a href="{{ route('vote.login') }}"
                    class="inline-flex items-center justify-center gap-2 w-full bg-gradient-to-r from-primary-600 to-primary-700 text-white font-bold py-3.5 px-6 rounded-2xl hover:from-primary-700 hover:to-primary-800 transition-all shadow-lg shadow-primary-500/25">
                    <i data-lucide="arrow-left" class="w-5 h-5"></i>
                    Kembali ke Halaman Login
                </a>
            </div>
        </div>
    </div>

</body>
</html>
