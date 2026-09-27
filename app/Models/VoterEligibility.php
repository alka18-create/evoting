<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoterEligibility extends Model
{
    /** @use HasFactory<\Database\Factories\VoterEligibilityFactory> */
    use HasFactory;

    /**
     * Mass assignment minimal: hanya kunci relasi.
     * status/token_hash/expires_at/voted_at wajib via forceFill() internal
     * agar request user tak bisa mengatur status/token.
     */
    protected $fillable = [
        'election_id',
        'voter_id',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'voted_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
    }

    public function hasVoted(): bool
    {
        return $this->status === 'VOTED';
    }

    public function hasToken(): bool
    {
        return ! is_null($this->token_hash);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Hash-only: token tidak dapat dipulihkan dari DB.
     * Selalu null — gunakan rotasi token baru.
     */
    public function plainToken(): ?string
    {
        return null;
    }
}
