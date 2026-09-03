<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VotingEventVoter extends Model
{
    /** @use HasFactory<\Database\Factories\VotingEventVoterFactory> */
    use HasFactory;

    protected $fillable = [
        'voting_event_id',
        'voter_id',
        'token',
        'token_hash',
        'token_enc',
        'expires_at',
    ];

    protected $hidden = [
        'token',
        'token_hash',
        'token_enc',
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
        return ! is_null($this->token_hash) || ! is_null($this->token);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Dekripsi ciphertext untuk cetak ulang kartu.
     * Return null jika tidak ada salinan terenkripsi (token lama / hash-only).
     */
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
        // TODO: hapus setelah migrasi drop kolom `token`.
        return $this->token;
    }
}
