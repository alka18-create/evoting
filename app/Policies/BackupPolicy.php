<?php

namespace App\Policies;

use App\Models\User;

class BackupPolicy
{
    /**
     * Backup berisi token hash + ballot + data pribadi.
     * Hanya SUPER_ADMIN (P0-02).
     */
    public function viewAny(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function download(User $user): bool
    {
        return $user->isSuperAdmin();
    }

    public function delete(User $user): bool
    {
        return $user->isSuperAdmin();
    }
}
