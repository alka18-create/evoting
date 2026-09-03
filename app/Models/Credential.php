<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Credential extends Model
{
    /** @use HasFactory<\Database\Factories\CredentialFactory> */
    use HasFactory;

    protected $fillable = [
        'voter_eligibility_id',
        'credential_hash',
        'expires_at',
        'last_used_at',
        'revoked_at',
    ];

    protected $hidden = [
        'credential_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'last_used_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    public function eligibility(): BelongsTo
    {
        return $this->belongsTo(VoterEligibility::class, 'voter_eligibility_id');
    }

    public function isActive(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}