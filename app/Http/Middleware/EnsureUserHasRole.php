<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $roles = collect($roles)->flatMap(fn($r) => explode(',', $r))->toArray();

        $user = $request->user();

        $userRole = $user->role?->value ?? 'VOTER';

        if (! in_array($userRole, $roles)) {
            abort(403, 'Forbidden.');
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
            return redirect()->route('login')->withErrors([
                'login' => ['Akun telah dinonaktifkan. Hubungi administrator.'],
            ]);
        }

        return $next($request);
    }
}