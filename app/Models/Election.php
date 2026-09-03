<?php

namespace App\Models;

use App\Domain\Elections\Enums\ElectionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Election extends Model
{
    /** @use HasFactory<\Database\Factories\ElectionFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'status',
        'starts_at',
        'ends_at',
        'settings',
        'created_by',
        'opened_at',
        'closed_at',
        'voting_event_id',
        'organization_id',
    ];

    protected $casts = [
        'status' => ElectionStatus::class,
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
        'settings' => 'array',
    ];

    public function votingEvent(): BelongsTo
    {
        return $this->belongsTo(VotingEvent::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(Candidate::class);
    }

    public function eligibilities(): HasMany
    {
        return $this->hasMany(VoterEligibility::class);
    }

    public function ballots(): HasMany
    {
        return $this->hasMany(Ballot::class);
    }

    public function effectiveStartsAt(): ?\Illuminate\Support\Carbon
    {
        return $this->starts_at ?? $this->votingEvent?->starts_at;
    }

    public function effectiveEndsAt(): ?\Illuminate\Support\Carbon
    {
        return $this->ends_at ?? $this->votingEvent?->ends_at;
    }
}