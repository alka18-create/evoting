<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Auditing\Services\AuditLogger;
use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Election;
use App\Models\User;
use App\Models\VotingEvent;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index()
    {
        $this->ensureSuperAdmin();

        $users = User::latest()->paginate(20);
        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        $this->ensureSuperAdmin();

        $roles = [
            UserRole::SuperAdmin->value => UserRole::SuperAdmin->label(),
            UserRole::Admin->value => UserRole::Admin->label(),
            UserRole::Operator->value => UserRole::Operator->label(),
        ];

        return view('admin.users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:SUPER_ADMIN,ADMIN,OPERATOR'],
        ]);

        $user = new User;
        $user->forceFill([
            'name' => $request->input('name'),
            'username' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'role' => $request->input('role'),
            'is_active' => true,
        ])->save();

        AuditLogger::log(
            action: 'USER_CREATED',
            resourceType: 'User',
            resourceId: $user->id,
            metadata: [
                'user_name' => $user->name,
                'user_role' => $user->role->value,
            ]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna \"{$user->name}\" berhasil dibuat.");
    }

    public function edit(User $user)
    {
        $this->ensureSuperAdmin();

        $roles = [
            UserRole::SuperAdmin->value => UserRole::SuperAdmin->label(),
            UserRole::Admin->value => UserRole::Admin->label(),
            UserRole::Operator->value => UserRole::Operator->label(),
        ];

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->ensureSuperAdmin();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'role' => ['required', 'in:SUPER_ADMIN,ADMIN,OPERATOR'],
        ]);

        $data = $request->only('name', 'username', 'email', 'role');

        if ($request->filled('password')) {
            $data['password'] = $request->input('password');
        }

        $user->forceFill($data)->save();

        AuditLogger::log(
            action: 'USER_UPDATED',
            resourceType: 'User',
            resourceId: $user->id,
            metadata: [
                'user_name' => $user->name,
                'user_role' => $user->role->value,
                'fields' => array_keys($request->only('name', 'username', 'email', 'role')),
            ]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna \"{$user->name}\" berhasil diperbarui.");
    }

    public function destroy(User $user)
    {
        $this->ensureSuperAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak dapat menghapus akun sendiri.']);
        }

        $userName = $user->name;

        // FK restrict: pengguna yang tercatat sebagai pembuat data tidak bisa
        // dihapus (dulu melempar 500). Jelaskan + arahkan ke nonaktifkan.
        $elections = Election::where('created_by', $user->id)->count();
        $events = VotingEvent::where('created_by', $user->id)->count();

        if ($elections > 0 || $events > 0) {
            $parts = [];
            if ($elections > 0) {
                $parts[] = "{$elections} pemilihan";
            }
            if ($events > 0) {
                $parts[] = "{$events} event voting";
            }

            return back()->withErrors(['error' => 'Tidak dapat menghapus "' . $userName . '" karena tercatat sebagai pembuat ' . implode(' dan ', $parts) . '. Nonaktifkan akun sebagai gantinya.']);
        }

        try {
            $user->delete();
        } catch (QueryException $e) {
            // Jaring pengaman relasi lain (masa depan): jangan 500.
            report($e);

            return back()->withErrors(['error' => 'Tidak dapat menghapus "' . $userName . '" karena masih terkait data lain. Nonaktifkan akun sebagai gantinya.']);
        }

        AuditLogger::log(
            action: 'USER_DELETED',
            resourceType: 'User',
            resourceId: null,
            metadata: ['user_name' => $userName]
        );

        return redirect()->route('admin.users.index')
            ->with('success', "Pengguna \"{$userName}\" berhasil dihapus.");
    }

    public function toggleActive(User $user)
    {
        $this->ensureSuperAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'Tidak dapat menonaktifkan akun sendiri.']);
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        $action = $user->is_active ? 'USER_ACTIVATED' : 'USER_DEACTIVATED';

        AuditLogger::log(
            action: $action,
            resourceType: 'User',
            resourceId: $user->id,
            metadata: [
                'user_name' => $user->name,
                'user_role' => $user->role->value,
                'is_active' => $user->is_active,
            ]
        );

        $status = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun \"{$user->name}\" berhasil {$status}.");
    }

    private function ensureSuperAdmin(): void
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Hanya Super Admin yang dapat mengelola pengguna.');
        }
    }
}
