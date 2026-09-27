<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * P1-02: batas idle khusus sesi voter (default 30 menit via
 * VOTER_SESSION_LIFETIME), terpisah dari SESSION_LIFETIME agar sesi
 * admin tidak ikut pendek. Sesi kedaluwarsa → logout + ke login.
 */
class VoterSessionTimeout
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::guard('voter')->check()) {
            $timeoutMinutes = max(1, (int) config('session.voter_timeout', 30));
            $lastActivity = (int) $request->session()->get('voter_last_activity', 0);

            if ($lastActivity > 0 && (now()->timestamp - $lastActivity) > $timeoutMinutes * 60) {
                Auth::guard('voter')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('vote.login')
                    ->withErrors(['error' => 'Sesi berakhir karena tidak ada aktivitas. Silakan login kembali.']);
            }

            $request->session()->put('voter_last_activity', now()->timestamp);
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
