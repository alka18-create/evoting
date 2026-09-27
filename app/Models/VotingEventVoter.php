<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VotingEventVoter extends Model
{
    /** @use HasFactory<\Database\Factories\VotingEventVoterFactory> */
    use HasFactory;

    /**
     * Mass assignment minimal: hanya kunci relasi.
     * token_hash/expires_at wajib via forceFill() internal.
     */
    protected $fillable = [
        'voting_event_id',
        'voter_id',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function votingEvent(): BelongsTo
    {
        return $this->belongsTo(VotingEvent::class);
    }

    public function voter(): BelongsTo
    {
        return $this->belongsTo(Voter::class);
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
     * Selalu null — cetak ulang diganti rotasi (terbitkan ulang).
     * Dipertahankan agar view lama tidak fatal.
     */
    public function plainToken(): ?string
    {
        return null;
    }
}
