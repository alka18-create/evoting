<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// P0: retensi audit log tidak akan jalan tanpa jadwal — prune harian 02:30.
Schedule::command('audit:prune')->dailyAt('02:30');
