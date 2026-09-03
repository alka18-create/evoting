<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * P3-01: step-up auth. User dengan MFA terkonfirmasi wajib verifikasi TOTP
 * setiap sesi login sebelum mengakses area admin.
 */
class EnsureMfaVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && method_exists($user, 'hasMfa') && $user->hasMfa()) {
            $verifiedAt = $request->session()->get('mfa_verified_at');

            if (! $verifiedAt) {
                if ($request->expectsJson()) {
                    abort(403, 'Verifikasi dua langkah diperlukan.');
                }

                return redirect()->route('mfa.challenge');
            }
        }

        return $next($request);
    }
}
