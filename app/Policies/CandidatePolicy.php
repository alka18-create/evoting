<?php

namespace App\Policies;

use App\Domain\Elections\Enums\ElectionStatus;
use App\Enums\UserRole;
use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true);
    }

    /**
     * P2-05: tolak di level policy juga agar tidak hanya andalkan controller.
     */
    private function electionEditable(Candidate $candidate): bool
    {
        $status = $candidate->election?->status;

        return in_array($status, [ElectionStatus::Draft, ElectionStatus::Scheduled], true);
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true)
            && $this->electionEditable($candidate);
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin], true)
            && $this->electionEditable($candidate);
    }
}