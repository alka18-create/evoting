<?php

namespace App\Providers;

use App\Guards\VoterGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class VoterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Auth::extend('voter', function ($app, $name, array $config) {
            $provider = new VoterUserProvider();

            return new VoterGuard($provider, $app['request']);
        });
    }
}
