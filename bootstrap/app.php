<?php

use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\AdminOnlyMiddleware;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function ($schedule) {
        // Generate auto-reports every hour
        $schedule->command('reports:generate')->hourly();
        
        // Send scheduled reports at configured time (default 23:00)
        // Use lambda to defer settings lookup until schedule runs
        $schedule->command('reports:send-scheduled')
            ->dailyAt('23:00')
            ->runInBackground();

        // System notification sync (low stock / overdue credit /
        // reconciliation + cheque due alerts) — roz subah 08:00
        $schedule->command('erp:sync-notifications')->dailyAt('08:00');

        // Document Vault expiry escalation scan (30d / 7d / expired
        // tiers) — roz subah 08:05
        $schedule->command('documents:scan-expiry')->dailyAt('08:05');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'admin' => AdminOnlyMiddleware::class,
        ]);

        // Admin-chosen site language (en/ur) for every web request.
        $middleware->web(append: [
            SetLocale::class,
        ]);

        $middleware->redirectGuestsTo(fn (Request $request) => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Never leak stack traces or raw SQL to the browser. Technical detail
        // goes to storage/logs; the user gets a friendly message.
        $exceptions->dontFlash([
            'current_password',
            'password',
            'password_confirmation',
        ]);
    })->create();
