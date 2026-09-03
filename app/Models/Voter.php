<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\Authenticatable;

class Voter extends Model implements Authenticatable
{
    /** @use HasFactory<\Database\Factories\VoterFactory> */
    use HasFactory;

    protected $fillable = [
        'student_id',
        'name',
        'class_name',
        'is_active',
    ];

    protected $hidden = [];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function eligibilities(): HasMany
    {
        return $this->hasMany(VoterEligibility::class);
    }

    public function votingEventVoters(): HasMany
    {
        return $this->hasMany(VotingEventVoter::class);
    }

    public function getAuthIdentifierName()
    {
        return $this->getKeyName();
    }

    public function getAuthIdentifier()
    {
        return $this->getKey();
    }

    public function getAuthPassword()
    {
        return '';
    }

    public function getAuthPasswordName(): string
    {
        return 'id';
    }

    public function getRememberToken()
    {
        return null;
    }

    public function setRememberToken($value)
    {
        // Voters don't use remember tokens
    }

    public function getRememberTokenName()
    {
        return '';
    }
}
