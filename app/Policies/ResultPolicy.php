<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Election;
use App\Models\User;

class ResultPolicy
{
    public function view(User $user, Election $election): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function publish(User $user, Election $election): bool
    {
        // Only admin can publish
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function export(User $user, Election $election): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }
}