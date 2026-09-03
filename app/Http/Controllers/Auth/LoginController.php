<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $request->input('login');
        $password = $request->input('password');

        // Cari user berdasarkan email atau username
        $user = \App\Models\User::where('email', $login)
            ->orWhere('username', $login)
            ->first();

        if (! $user || ! Auth::attempt(['email' => $user->email, 'password' => $password], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => ['Username atau password salah.'],
            ]);
        }

        // Hanya admin/operato yang boleh login via form
        if (! $user->isAdmin() && ! $user->isSuperAdmin()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => ['Akun ini tidak memiliki akses admin.'],
            ]);
        }

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'login' => ['Akun belum diaktifkan.'],
            ]);
        }

        // Regenerate session untuk mencegah session fixation
        $request->session()->regenerate();

        // P3-01: MFA wajib diverifikasi ulang setiap sesi login.
        $request->session()->forget('mfa_verified_at');
        if (! $user->hasMfa()) {
            // Tanpa MFA: anggap terverifikasi agar alur lama tidak berubah.
            $request->session()->put('mfa_verified_at', now()->toIso8601String());
        }

        // Update last login
        $user->update(['last_login_at' => now()]);

        // Audit log
        \App\Domain\Auditing\Services\AuditLogger::log(
            action: 'LOGIN',
            resourceType: 'User',
            resourceId: $user->id,
            metadata: ['method' => 'admin_form']
        );

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        $user = $request->user();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($user) {
            \App\Domain\Auditing\Services\AuditLogger::log(
                action: 'LOGOUT',
                resourceType: 'User',
                resourceId: $user->id
            );
        }

        return redirect()->route('login');
    }
}