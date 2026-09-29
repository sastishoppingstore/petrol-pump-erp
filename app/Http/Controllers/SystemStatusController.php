<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class SystemStatusController extends Controller
{
    /**
     * Minimal liveness probe. Reports reachability only — never schema,
     * credentials, versions or any other internal detail.
     */
    public function health(): JsonResponse
    {
        $dbOk = $this->databaseReachable();

        return response()->json([
            'status' => $dbOk ? 'ok' : 'degraded',
            'database' => $dbOk ? 'up' : 'down',
        ], $dbOk ? 200 : 503);
    }

    private function databaseReachable(): bool
    {
        try {
            DB::connection()->getPdo();

            return true;
        } catch (Throwable) {
            return false;
        }
    }
}
