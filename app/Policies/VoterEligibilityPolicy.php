<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\User;
use App\Models\VoterEligibility;

class VoterEligibilityPolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function view(User $user, VoterEligibility $eligibility): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function delete(User $user, VoterEligibility $eligibility): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }
}
