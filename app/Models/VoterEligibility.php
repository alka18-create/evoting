<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VoterEligibility extends Model
{
    /** @use HasFactory<\Database\Factories\VoterEligibilityFactory> */
    use HasFactory;

    protected $fillable = [
        'election_id',
        'voter_id',
        'status',
        'token',
        'token_hash',
        'token_enc',
        'expires_at',
        'voted_at',
    ];

    protected $hidden = [
        'token',
        'token_hash',
        'token_enc',
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
        return ! is_null($this->token_hash) || ! is_null($this->token);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function plainToken(): ?string
    {
        if ($this->token_enc) {
            try {
                return \Illuminate\Support\Facades\Crypt::decryptString($this->token_enc);
            } catch (\Throwable) {
                return null;
            }
        }

        // Fallback transisi: izinkan baca plain lama sampai rotasi selesai.
        return $this->token;
    }
}
