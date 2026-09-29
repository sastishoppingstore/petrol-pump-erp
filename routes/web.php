<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\BranchSwitchController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SystemStatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Health probe
|--------------------------------------------------------------------------
| Unauthenticated liveness check for monitoring / uptime. Returns
| reachability only — no schema, versions or internal detail.
|
*/

Route::get('/health', [SystemStatusController::class, 'health'])->name('system.health');

/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])
        ->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])
        ->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])
        ->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'reset'])
        ->name('password.update');
});

/*
|--------------------------------------------------------------------------
| Authenticated routes
|--------------------------------------------------------------------------
|
| Business modules are registered by their own phase files:
|   routes/fuel.php, routes/stock.php, routes/shift.php, routes/pos.php,
|   routes/purchase.php, routes/accounts.php, routes/reports.php,
|   routes/settings.php
|
| Each of those is included from here so the middleware stack stays identical
| across every module.
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/branch/switch', BranchSwitchController::class)->name('branch.switch');

    Route::post('/logout', LogoutController::class)->name('logout');

    // Administration (Phase 1)
    require __DIR__.'/admin.php';

    // Remaining modules, as they land.
    foreach (['fuel', 'stock', 'shift', 'pos', 'purchase', 'accounts', 'reports', 'settings'] as $module) {
        $path = __DIR__.'/'.$module.'.php';

        if (is_file($path)) {
            require $path;
        }
    }
});
