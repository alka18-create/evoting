<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Models\AuditLog;
use App\Models\Candidate;
use App\Models\Election;
use App\Models\User;
use App\Models\Voter;
use App\Models\VoterEligibility;
use App\Policies\AuditLogPolicy;
use App\Policies\BackupPolicy;
use App\Policies\CandidatePolicy;
use App\Policies\ElectionPolicy;
use App\Policies\ResultPolicy;
use App\Policies\VoterEligibilityPolicy;
use App\Policies\VoterPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Election::class => ElectionPolicy::class,
        Candidate::class => CandidatePolicy::class,
        Voter::class => VoterPolicy::class,
        VoterEligibility::class => VoterEligibilityPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // P0-05: Gates eksplisit untuk resource tanpa model policy khusus.
        Gate::define('manageBackups', fn (User $user) => (new BackupPolicy)->viewAny($user));
        Gate::define('verifyVoter', fn (User $user) => in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true));
        Gate::define('viewResult', fn (User $user, Election $election) => (new ResultPolicy)->view($user, $election));
        Gate::define('exportResult', fn (User $user, Election $election) => (new ResultPolicy)->export($user, $election));
    }
}
