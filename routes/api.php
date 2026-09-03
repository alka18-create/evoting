<?php

use Illuminate\Support\Facades\Route;

Route::prefix('health')->group(function () {
    Route::get('/', function () {
        $dbUp = true;

        try {
            app('db')->select('select 1');
        } catch (\Throwable) {
            $dbUp = false;
        }

        return response()->json([
            'status' => $dbUp ? 'ok' : 'degraded',
            'database' => $dbUp ? 'ok' : 'error',
        ], $dbUp ? 200 : 503);
    })->name('api.health');
});