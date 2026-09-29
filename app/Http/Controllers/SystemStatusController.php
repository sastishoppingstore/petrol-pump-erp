<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;
use Throwable;

class SystemStatusController extends Controller
{
    /**
     * Phase 0 landing page: real environment + database connectivity facts.
     * Replaced by the login screen in Phase 1.
     */
    public function index()
    {
        return view('system.status', [
            'dbConnected' => $this->databaseReachable(),
            'phpVersion' => PHP_VERSION,
            'laravelVersion' => app()->version(),
            'dbName' => config('database.connections.mysql.database'),
            'dbDriver' => config('database.default'),
        ]);
    }

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
