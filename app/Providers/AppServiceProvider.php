<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // P1-03: dua lapis — ketat per kredensial, longgar global per IP agar
        // 1 kelas (40 siswa) tetap bisa login bergantian tapi brute force diblok.
        RateLimiter::for('login', function (Request $request) {
            return [
                Limit::perMinute(5)->by('login:' . $request->input('login') . '|' . $request->ip()),
                Limit::perMinute(30)->by('login-ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('vote-login', function (Request $request) {
            return [
                Limit::perMinute(5)->by('vote:' . $request->input('student_id') . '|' . $request->ip()),
                Limit::perMinute(30)->by('vote-ip:' . $request->ip()),
            ];
        });

        RateLimiter::for('scan-verify', function (Request $request) {
            return Limit::perMinute(60)->by('scan:' . $request->ip());
        });
    }
}
