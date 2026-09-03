<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class HealthController extends Controller
{
    /**
     * Health check tanpa membocorkan secret.
     */
    public function __invoke(): JsonResponse
    {
        $dbUp = true;

        try {
            DB::select('select 1');
        } catch (\Throwable) {
            $dbUp = false;
        }

        return response()->json([
            'status' => $dbUp ? 'ok' : 'degraded',
        ], $dbUp ? 200 : 503);
    }
}