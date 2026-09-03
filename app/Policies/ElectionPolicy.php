<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Election;
use App\Models\User;

class ElectionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->role === UserRole::Operator;
    }

    public function view(User $user, Election $election): bool
    {
        return $user->isAdmin() || $user->isSuperAdmin() || $user->role === UserRole::Operator;
    }

    /**
     * Operator hanya punya ViewElections (lihat Permission::forRole).
     * Membuat/mengubah/membuka/menutup pemilihan = Admin ke atas.
     */
    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function update(User $user, Election $election): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function delete(User $user, Election $election): bool
    {
        return $user->isSuperAdmin();
    }

    public function open(User $user, Election $election): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function close(User $user, Election $election): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function archive(User $user, Election $election): bool
    {
        return $user->isSuperAdmin();
    }
}