<?php

namespace App\Http\Middleware;

use App\Enums\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(403, 'Forbidden.');
        }

        // Check if user has ANY of the required permissions
        $hasPermission = false;
        foreach ($permissions as $perm) {
            $permissionEnum = Permission::tryFrom($perm);
            if ($permissionEnum && $user->hasPermission($permissionEnum)) {
                $hasPermission = true;
                break;
            }
        }

        if (! $hasPermission) {
            abort(403, 'Anda tidak memiliki izin untuk mengakses halaman ini.');
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
