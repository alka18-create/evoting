<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VotingEvent;

class VotingEventPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function view(User $user, VotingEvent $event): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function update(User $user, VotingEvent $event): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true);
    }

    public function delete(User $user, VotingEvent $event): bool
    {
        return $user->role === UserRole::SuperAdmin;
    }
}
