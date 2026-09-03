<?php

use App\Enums\UserRole;
use App\Models\Election;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels (P2-06, Threat Model §33)
|--------------------------------------------------------------------------
| Monitoring realtime hanya untuk admin/operator yang login via guard web.
| Voter (guard voter) dan tamu ditolak. Payload event hanya agregat
| (total_voted/total_eligible/participation_rate) — tanpa candidate/voter.
*/

Broadcast::channel('election.{electionId}.monitoring', function ($user, $electionId) {
    if (! $user instanceof \App\Models\User) {
        return false;
    }

    if (! $user->is_active) {
        return false;
    }

    if (! in_array($user->role, [UserRole::SuperAdmin, UserRole::Admin, UserRole::Operator], true)) {
        return false;
    }

    // Election harus ada agar channel tidak bisa dipakai untuk enumerasi bebas.
    return Election::whereKey($electionId)->exists();
});
