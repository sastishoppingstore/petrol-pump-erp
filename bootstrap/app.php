<?php

use App\Http\Middleware\PermissionMiddleware;
use App\Http\Middleware\AdminOnlyMiddleware;
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
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'admin' => AdminOnlyMiddleware::class,
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
