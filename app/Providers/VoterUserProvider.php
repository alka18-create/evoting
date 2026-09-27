<?php

namespace App\Providers;

use App\Models\Voter;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Contracts\Auth\Authenticatable;

class VoterUserProvider implements UserProvider
{
    protected $model;

    public function __construct()
    {
        $this->model = Voter::class;
    }

    public function retrieveById($identifier)
    {
        // P0-C2: voter yang dinonaktifkan di tengah sesi langsung dianggap
        // tamu (guard->user() = null), sehingga wizard/vote tertolak.
        return $this->newModelQuery()->where('is_active', true)->find($identifier);
    }

    public function retrieveByToken($identifier, $token)
    {
        return null;
    }

    public function updateRememberToken(Authenticatable $user, $token)
    {
        // Voters don't use remember tokens
    }

    public function retrieveByCredentials(array $credentials)
    {
        return null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials)
    {
        return false;
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials, bool $force = false)
    {
        // Voters don't have password
    }

    protected function newModelQuery()
    {
        return $this->model::query();
    }
}
