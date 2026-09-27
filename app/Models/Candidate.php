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
        'running_mate_name',
        'photo_path',
        'running_mate_photo_path',
        'vision',
        'mission',
    ];

    /** Kandidat berpasangan (ada wakil). */
    public function isPair(): bool
    {
        return filled($this->running_mate_name);
    }

    /** Nama untuk ditampilkan di hasil/ekspor: "Ketua - Wakil". */
    public function display_name(): string
    {
        return $this->isPair()
            ? $this->name . ' - ' . $this->running_mate_name
            : $this->name;
    }

    public function election(): BelongsTo
    {
        return $this->belongsTo(Election::class);
    }

    public function ballots(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Ballot::class);
    }
}