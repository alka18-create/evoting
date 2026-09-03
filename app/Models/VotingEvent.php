<?php

namespace App\Models;

use App\Domain\Elections\Enums\VotingEventStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VotingEvent extends Model
{
    /** @use HasFactory<\Database\Factories\VotingEventFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'created_by',
        'opened_at',
        'closed_at',
    ];

    protected $casts = [
        'status' => VotingEventStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function elections(): HasMany
    {
        return $this->hasMany(Election::class);
    }

    public function votingEventVoters(): HasMany
    {
        return $this->hasMany(VotingEventVoter::class);
    }

    public function voters(): BelongsToMany
    {
        return $this->belongsToMany(Voter::class, 'voting_event_voters')
            ->withPivot('token_hash', 'expires_at')
            ->withTimestamps();
    }
}
