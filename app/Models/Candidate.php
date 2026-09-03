<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Candidate extends Model
{
    /** @use HasFactory<\Database\Factories\CandidateFactory> */
    use HasFactory;

    protected $fillable = [
        'election_id',
        'candidate_number',
        'name',
        'photo_path',
        'vision',
        'mission',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function ballots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ballot::class);
    }
}