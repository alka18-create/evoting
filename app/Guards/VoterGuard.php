<?php

namespace App\Guards;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Auth\UserProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class VoterGuard implements Guard
{
    protected $request;
    protected $provider;
    protected $user;

    public function __construct(UserProvider $provider, Request $request)
    {
        $this->provider = $provider;
        $this->request = $request;
    }

    public function user()
    {
        if ($this->user !== null) {
            return $this->user;
        }

        $voterId = Session::get('voter_id');

        if ($voterId !== null) {
            $this->user = $this->provider->retrieveById($voterId);
        }

        return $this->user;
    }

    public function id()
    {
        $user = $this->user();
        return $user ? $user->getKey() : null;
    }

    public function guest()
    {
        return ! $this->user();
    }

    public function check()
    {
        return ! $this->guest();
    }

    public function viaRemember()
    {
        return false;
    }

    public function hasUser()
    {
        return ! is_null($this->user);
    }

    public function validate(array $credentials = [])
    {
        return false;
    }

    public function setUser(\Illuminate\Contracts\Auth\Authenticatable $user)
    {
        $this->user = $user;
        return $this;
    }

    public function loginById($id)
    {
        $user = $this->provider->retrieveById($id);
        if ($user) {
            $this->user = $user;
            Session::put('voter_id', $user->getKey());
        }
        return $user;
    }

    public function logout()
    {
        $this->user = null;
        Session::forget('voter_id');
        Session::forget('eligibility_id');
        Session::forget('election_id');
        Session::forget('voting_event_id');
        Session::forget('voting_event_voter_id');
        Session::forget('wizard.selections');
    }
}
