<x-layouts.admin title="Keamanan — MFA">
    <div class="max-w-2xl space-y-6">
        <div class="bg-white rounded-2xl shadow-sm border p-6">
            <h3 class="text-lg font-semibold text-gray-800">Verifikasi Dua Langkah (TOTP)</h3>
            <p class="text-sm text-gray-500 mt-1">Sangat disarankan untuk SUPER_ADMIN &amp; ADMIN. Berlaku per sesi login.</p>

            @if (session('success'))
                <div class="mt-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl text-sm">{{ session('success') }}</div>
            @endif
            @if (session('warning'))
                <div class="mt-4 bg-amber-50 border border-amber-200 text-amber-700 px-4 py-3 rounded-xl text-sm">{{ session('warning') }}</div>
            @endif
            @if ($errors->any())
                <div class="mt-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl text-sm">
                    @foreach ($errors->all() as $error)<p>{{ $error }}</p>@endforeach
                </div>
            @endif
            @if (session('recovery_codes'))
                <div class="mt-4 bg-gray-900 text-emerald-300 rounded-xl p-4">
                    <p class="text-sm font-semibold mb-2">Kode pemulihan — simpan sekarang (sekali tampil):</p>
                    <ul class="font-mono text-sm space-y-1">
                        @foreach (session('recovery_codes') as $c)<li>{{ $c }}</li>@endforeach
                    </ul>
                </div>
            @endif

            @if ($user->hasMfa())
                <div class="mt-4 flex items-center gap-2 text-sm">
                    <span class="inline-flex px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20 font-semibold">● MFA AKTIF sejak {{ $user->two_factor_confirmed_at?->format('d/m/Y H:i') }}</span>
                </div>
                <form method="POST" action="{{ route('admin.profile.mfa.disable') }}" class="mt-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Password saat ini</label>
                        <input type="password" name="password" required class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Kode TOTP / pemulihan</label>
                        <input type="text" name="code" required maxlength="32" class="w-full px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono">
                    </div>
                    <button class="bg-red-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold" onclick="return confirm('Nonaktifkan MFA?')">Nonaktifkan MFA</button>
                </form>
            @else
                <div class="mt-4 text-sm text-gray-600 space-y-2">
                    <p>1. Buka aplikasi authenticator (Google/Microsoft Authenticator, 1Password, dsb).</p>
                    <p>2. Pindai QR di bawah atau masukkan secret manual.</p>
                    <p>3. Masukkan kode 6 digit untuk mengaktifkan.</p>
                </div>
                @if ($provisioningUri)
                    <div class="mt-4 flex flex-col items-center gap-3 bg-gray-50 border rounded-2xl p-6">
                        {!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(180)->margin(1)->generate($provisioningUri) !!}
                        <p class="font-mono text-xs tracking-[0.2em] bg-white border px-3 py-2 rounded-lg">{{ $pendingSecret }}</p>
                    </div>
                @endif
                <form method="POST" action="{{ route('admin.profile.mfa.enable') }}" class="mt-4 flex gap-2">
                    @csrf
                    <input type="text" name="code" required pattern="[0-9]{6}" maxlength="6" inputmode="numeric" placeholder="123456"
                        class="flex-1 px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-mono tracking-[0.3em] text-center">
                    <button class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-semibold">Aktifkan</button>
                </form>
            @endif
        </div>
    </div>
</x-layouts.admin>
