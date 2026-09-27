<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api/v1',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi('api');

        // Deploy: aplikasi di belakang reverse proxy (aaPanel/nginx di VPS,
        // nginx container di lokal). Tanpa ini Laravel mengira semua request
        // HTTP sehingga asset()/redirect memakai skema http → diblokir
        // browser sebagai mixed-content di halaman https.
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
            'permission' => \App\Http\Middleware\EnsureUserHasPermission::class,
            'mfa' => \App\Http\Middleware\EnsureMfaVerified::class,
            'voter.timeout' => \App\Http\Middleware\VoterSessionTimeout::class,
        ]);

        // P1-02: headers keamanan untuk semua response web.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
