<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Daftar Token — {{ $election->name }}</title>
@vite(['resources/css/app.css', 'resources/js/app.js'])
<style>
@media print {
    .no-print { display: none !important; }
    body { background: #fff; }
    .print-card { box-shadow: none !important; border: 1px solid #ddd !important; }
}
</style>
</head>
<body class="bg-gray-50 min-h-screen">
<div class="max-w-6xl mx-auto px-4 py-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
        <div>
            <p class="text-xs text-gray-400 uppercase tracking-wider">Daftar Token Hash-only</p>
            <h1 class="text-xl font-bold text-gray-800">{{ $election->name }}</h1>
            <p class="text-sm text-gray-500">{{ $eligibilities->count() }} token diterbitkan</p>
        </div>
        <div class="flex items-center gap-2 no-print">
            <button onclick="window.print()" class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50">
                <i data-lucide="printer" class="w-4 h-4"></i> Cetak Daftar
            </button>
            <a href="{{ route('admin.elections.tokens.index', $election) }}" class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-gray-700">
                <i data-lucide="arrow-left" class="w-4 h-4"></i> Kembali
            </a>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 mb-6 flex gap-3">
        <i data-lucide="shield-alert" class="w-5 h-5 text-amber-600 flex-shrink-0"></i>
        <p class="text-sm text-amber-800"><b>Hash-only:</b> plaintext token tidak tersimpan di database, sehingga kartu tidak dapat dicetak ulang. Token baru hanya tampil <b>sekali</b> saat diterbitkan — tapi <b>PDF kartu langsung disimpan</b> (bisa diunduh ulang di bawah). Butuh kartu baru? Gunakan <b>Rotasi</b> — token lama otomatis tidak berlaku.</p>
    </div>

    @if (session('saved_card_pdf'))
        <div class="bg-teal-50 border-2 border-teal-200 rounded-2xl px-5 py-4 mb-6 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3">
                <i data-lucide="file-check-2" class="w-5 h-5 text-teal-600"></i>
                <div>
                    <p class="text-sm font-semibold text-teal-800">PDF kartu berhasil disimpan</p>
                    <p class="text-xs text-teal-600">{{ session('saved_card_pdf') }} — tersimpan di penyimpanan privat, bisa diunduh ulang.</p>
                </div>
            </div>
            <a href="{{ route('admin.elections.tokens.card-pdf.download', [$election, session('saved_card_pdf')]) }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-sm font-semibold text-white bg-teal-600 rounded-xl hover:bg-teal-700 transition-all">
                <i data-lucide="download" class="w-4 h-4"></i> Unduh PDF Kartu
            </a>
        </div>
    @endif

    @if (!empty($savedPdfs))
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-6 print-card">
            <div class="flex items-center justify-between gap-3 mb-4">
                <div>
                    <h3 class="font-semibold text-gray-800">PDF Kartu Tersimpan</h3>
                    <p class="text-xs text-gray-500">Salinan kartu berisi token — simpan sampai pemilihan selesai, lalu hapus.</p>
                </div>
                <i data-lucide="files" class="w-5 h-5 text-gray-400"></i>
            </div>
            <ul class="divide-y divide-gray-100">
                @forelse ($savedPdfs as $pdf)
                    <li class="py-3 flex flex-wrap items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-gray-700 truncate">{{ $pdf['file'] }}</p>
                            <p class="text-xs text-gray-400">{{ number_format($pdf['size'] / 1024, 1) }} KB &middot; {{ \Illuminate\Support\Carbon::createFromTimestamp($pdf['modified'])->format('d/m/Y H:i') }}</p>
                        </div>
                        <div class="flex items-center gap-2 no-print">
                            <a href="{{ route('admin.elections.tokens.card-pdf.download', [$election, $pdf['file']]) }}"
                                class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-teal-700 bg-teal-50 rounded-lg hover:bg-teal-100 ring-1 ring-teal-600/20">
                                <i data-lucide="download" class="w-3.5 h-3.5"></i> Unduh
                            </a>
                            <form method="POST" action="{{ route('admin.elections.tokens.card-pdf.destroy', [$election, $pdf['file']]) }}" class="inline" onsubmit="return confirm('Hapus PDF ini? Kartu berisi token tidak akan bisa diunduh lagi.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 rounded-lg hover:bg-red-100 ring-1 ring-red-600/20">
                                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i> Hapus
                                </button>
                            </form>
                        </div>
                    </li>
                @empty
                    <li class="py-4 text-sm text-gray-400">Belum ada PDF tersimpan. PDF dibuat otomatis setiap kali token diterbitkan/dirotasi.</li>
                @endforelse
            </ul>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 px-5 py-4 rounded-2xl mb-6 flex items-start gap-3"><i data-lucide="alert-circle" class="w-5 h-5 text-red-500 flex-shrink-0"></i><div>@foreach ($errors->all() as $error)<p class="text-sm">{{ $error }}</p>@endforeach</div></div>
    @endif
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl mb-6 text-sm">{{ session('success') }}</div>
    @endif

    @if (session('issued_tokens'))
        <div class="mb-6 bg-emerald-50 border-2 border-emerald-200 rounded-2xl p-6 print-card">
            <h3 class="font-semibold text-emerald-800 mb-1">Token Baru — simpan/cetak sekarang</h3>
            <p class="text-sm text-emerald-700 mb-4">Hanya ditampilkan sekali, tidak bisa dibuka lagi.</p>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach (session('issued_tokens') as $item)
                    <div class="bg-white rounded-2xl border-2 border-emerald-200 p-5 text-center">
                        <p class="text-sm font-medium text-gray-600 mb-1">{{ $item['name'] }}</p>
                        <p class="text-xs text-gray-400 mb-3">NIS: {{ $item['student_id'] }}</p>
                        <div class="bg-gray-800 rounded-xl py-3 px-4">
                            <p class="text-3xl font-bold text-white tracking-[0.3em]">{{ $item['token'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden print-card">
        <div class="overflow-x-auto">
            <table class="min-w-full min-w-[720px]">
                <thead class="bg-gray-50/50">
                    <tr>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">No</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Pemilih</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status Token</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Kedaluwarsa</th>
                        <th class="px-6 py-3.5 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider no-print">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($eligibilities as $idx => $eligibility)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-6 py-4 text-sm text-gray-400">{{ $idx + 1 }}</td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-gray-800">{{ $eligibility->voter->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500 font-mono">{{ $eligibility->voter->student_id ?? '-' }} &middot; {{ $eligibility->voter->class_name ?? '' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if ($eligibility->hasVoted())
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700 ring-1 ring-inset ring-purple-600/20">Sudah Vote</span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-600/20">Diterbitkan</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $eligibility->expires_at ? $eligibility->expires_at->format('d/m/Y') : '-' }}</td>
                            <td class="px-6 py-4 no-print">
                                @if (! $eligibility->hasVoted())
                                    <form method="POST" action="{{ route('admin.elections.tokens.reissue', [$election, $eligibility]) }}" class="inline" onsubmit="return confirm('Rotasi token? Token lama tidak berlaku, token baru tampil sekali di halaman ini.')">
                                        @csrf
                                        <button type="submit" class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-semibold text-amber-700 bg-amber-50 rounded-lg hover:bg-amber-100 ring-1 ring-amber-600/20">
                                            <i data-lucide="refresh-cw" class="w-3.5 h-3.5"></i> Rotasi
                                        </button>
                                    </form>
                                @else
                                    <span class="text-xs text-gray-400">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <i data-lucide="key-round" class="w-12 h-12 text-gray-300 mx-auto mb-3"></i>
                                <p class="text-gray-500 text-sm">Belum ada token diterbitkan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</body>
</html>
