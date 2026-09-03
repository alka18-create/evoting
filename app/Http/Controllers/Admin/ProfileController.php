<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        return view('admin.profile.edit', compact('user'));
    }

    public function update(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $user->update($request->only('name', 'email'));

        AuditLogger::log(
            action: 'PROFILE_UPDATED',
            resourceType: 'User',
            resourceId: $user->id,
            metadata: ['fields' => array_keys($request->only('name', 'email'))]
        );

        return back()->with('success', 'Profile berhasil diperbarui.');
    }

    public function changePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = auth()->user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Password lama salah.'],
            ]);
        }

        $user->update([
            'password' => $request->input('password'),
        ]);

        AuditLogger::log(
            action: 'PASSWORD_CHANGED',
            resourceType: 'User',
            resourceId: $user->id
        );

        return back()->with('success', 'Password berhasil diubah.');
    }
}
