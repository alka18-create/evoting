<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MfaController extends Controller
{
    /**
     * Halaman kelola MFA: tampilkan status + (jika belum aktif) secret baru
     * sebagai QR + recovery codes pratinjau sekali tampil.
     */
    public function show(Request $request): View
    {
        $user = $request->user();

        $pendingSecret = null;
        $provisioningUri = null;

        if (! $user->hasMfa()) {
            $pendingSecret = $request->session()->get('mfa_pending_secret');

            if (! $pendingSecret) {
                $pendingSecret = Totp::generateSecret();
                $request->session()->put('mfa_pending_secret', $pendingSecret);
            }

            $provisioningUri = Totp::provisioningUri($pendingSecret, (string) $user->email);
        }

        return view('admin.profile.mfa', [
            'user' => $user,
            'pendingSecret' => $pendingSecret,
            'provisioningUri' => $provisioningUri,
        ]);
    }

    /**
     * Aktifkan MFA: verifikasi 1 kode TOTP dari secret pending, lalu simpan
     * secret terenkripsi + recovery codes (hash) sekali tampil.
     */
    public function enable(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'code' => ['required', 'string', 'regex:/^\d{6}$/'],
        ]);

        $secret = $request->session()->get('mfa_pending_secret');

        if (! is_string($secret) || $secret === '') {
            return back()->withErrors(['code' => 'Sesi setup kedaluwarsa. Muat ulang halaman.']);
        }

        if (! Totp::verify($secret, (string) $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => ['Kode salah. Periksa jam perangkat authenticator.'],
            ]);
        }

        $recoveryPlain = collect(range(1, 8))->map(fn () => strtoupper(substr(bin2hex(random_bytes(5)), 0, 5) . '-' . substr(bin2hex(random_bytes(5)), 0, 5)))->all();
        $recoveryHashed = array_map(fn ($c) => Hash::make($c), $recoveryPlain);

        $user->update([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => json_encode($recoveryHashed),
        ]);

        $request->session()->forget('mfa_pending_secret');
        $request->session()->put('mfa_verified_at', now()->toIso8601String());

        AuditLogger::log(action: 'MFA_ENABLED', resourceType: 'User', resourceId: $user->id);

        return redirect()->route('admin.profile.mfa')
            ->with('success', 'MFA aktif. Simpan kode pemulihan di tempat aman.')
            ->with('recovery_codes', $recoveryPlain);
    }

    public function disable(Request $request): RedirectResponse
    {
        $user = $request->user();

        $request->validate([
            'password' => ['required', 'string'],
            'code' => ['required', 'string', 'max:32'],
        ]);

        if (! Hash::check((string) $request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['Password salah.'],
            ]);
        }

        $code = preg_replace('/\s+/', '', (string) $request->input('code'));
        $ok = Totp::verify((string) $user->two_factor_secret, $code);

        if (! $ok) {
            // Izinkan recovery code untuk menonaktifkan (kehilangan authenticator).
            $stored = $user->two_factor_recovery_codes;
            $codes = is_string($stored) ? (json_decode($stored, true) ?: []) : [];
            foreach ($codes as $hash) {
                if (is_string($hash) && Hash::check($code, $hash)) {
                    $ok = true;
                    break;
                }
            }
        }

        if (! $ok) {
            throw ValidationException::withMessages([
                'code' => ['Kode verifikasi salah.'],
            ]);
        }

        $user->update([
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ]);
        $request->session()->forget('mfa_verified_at');

        AuditLogger::log(action: 'MFA_DISABLED', resourceType: 'User', resourceId: $user->id);

        return redirect()->route('admin.profile.mfa')->with('success', 'MFA dinonaktifkan.');
    }
}
