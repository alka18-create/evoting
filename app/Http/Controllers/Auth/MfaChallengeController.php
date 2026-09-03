<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Auditing\Services\AuditLogger;
use App\Http\Controllers\Controller;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class MfaChallengeController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user || ! $user->hasMfa()) {
            return redirect()->route('admin.dashboard');
        }

        if (session('mfa_verified_at')) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.mfa-challenge');
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = auth()->user();

        if (! $user || ! $user->hasMfa()) {
            return redirect()->route('admin.dashboard');
        }

        $request->validate([
            'code' => ['required', 'string', 'max:32'],
        ]);

        $code = preg_replace('/\s+/', '', (string) $request->input('code'));

        // 1) Coba TOTP 6 digit
        if (Totp::verify((string) $user->two_factor_secret, $code)) {
            $request->session()->put('mfa_verified_at', now()->toIso8601String());

            AuditLogger::log(action: 'MFA_VERIFIED', resourceType: 'User', resourceId: $user->id);

            return redirect()->intended(route('admin.dashboard'));
        }

        // 2) Coba recovery code (sekali pakai, format XXXX-XXXX-...)
        $stored = $user->two_factor_recovery_codes;
        $codes = is_string($stored) ? (json_decode($stored, true) ?: []) : (is_array($stored) ? $stored : []);

        foreach ($codes as $i => $hash) {
            if (is_string($hash) && Hash::check($code, $hash)) {
                unset($codes[$i]);
                $user->update(['two_factor_recovery_codes' => json_encode(array_values($codes))]);
                $request->session()->put('mfa_verified_at', now()->toIso8601String());

                AuditLogger::log(action: 'MFA_RECOVERY_USED', resourceType: 'User', resourceId: $user->id);

                return redirect()->intended(route('admin.dashboard'))
                    ->with('warning', 'Kode pemulihan terpakai (' . count($codes) . ' tersisa). Segera generate ulang di Profil.');
            }
        }

        AuditLogger::log(action: 'MFA_FAILED', resourceType: 'User', resourceId: $user->id);

        throw ValidationException::withMessages([
            'code' => ['Kode verifikasi salah.'],
        ]);
    }
}
