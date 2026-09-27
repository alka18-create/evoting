<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VotingEventVoter;

class VotingEventVoterPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function view(User $user, VotingEventVoter $voter): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function delete(User $user, VotingEventVoter $voter): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    /**
     * Kelola kartu PDF (unduh/hapus) — kemampuan terpisah dari delete
     * karena targetnya file, bukan VotingEventVoter.
     */
    public function manageCards(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }
}
