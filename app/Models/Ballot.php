<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ballot extends Model
{
    /** @use HasFactory<\Database\Factories\BallotFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'election_id',
        'candidate_id',
        'ballot_hash',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Candidate::class);
    }
}