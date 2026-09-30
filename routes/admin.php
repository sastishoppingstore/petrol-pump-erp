<?php

use App\Http\Controllers\BranchController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Administration routes (Phase 1)
|--------------------------------------------------------------------------
| Every route below is permission-guarded by middleware. A hidden button is
| never the control — the middleware rejects the request even if the URL is
| typed directly.
|
*/

Route::middleware('auth')->group(function () {

    // --- Branches ---
    Route::middleware('permission:branch.view')->group(function () {
        Route::get('/branches', [BranchController::class, 'index'])->name('branches.index');
        Route::get('/branches/{branch}/edit', [BranchController::class, 'edit'])->name('branches.edit');
    });

    Route::middleware('permission:branch.create')->group(function () {
        Route::get('/branches/create', [BranchController::class, 'create'])->name('branches.create');
        Route::post('/branches', [BranchController::class, 'store'])->name('branches.store');
    });

    Route::middleware('permission:branch.edit')->group(function () {
        Route::put('/branches/{branch}', [BranchController::class, 'update'])->name('branches.update');
    });

    Route::middleware('permission:branch.delete')->group(function () {
        Route::delete('/branches/{branch}', [BranchController::class, 'destroy'])->name('branches.destroy');
    });

    // --- Users ---
    Route::middleware('permission:user.view')->group(function () {
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    });

    Route::middleware('permission:user.create')->group(function () {
        Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
    });

    Route::middleware('permission:user.edit')->group(function () {
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::middleware('permission:user.delete')->group(function () {
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // --- Roles ---
    Route::middleware('permission:role.view')->group(function () {
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::get('/roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    });

    Route::middleware('permission:role.create')->group(function () {
        Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
        Route::post('/roles', [RoleController::class, 'store'])->name('roles.store');
    });

    Route::middleware('permission:role.edit')->group(function () {
        Route::put('/roles/{role}', [RoleController::class, 'update'])->name('roles.update');
    });

    Route::middleware('permission:role.delete')->group(function () {
        Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
    });

    // --- Permissions (read-only catalogue) ---
    Route::middleware('permission:permission.view')->group(function () {
        Route::get('/permissions', [PermissionController::class, 'index'])->name('permissions.index');
    });
    
    // --- System Settings (Super Admin Only) ---
    Route::middleware('auth')->group(function () {
        Route::get('/admin/settings', [SettingsController::class, 'index'])->name('admin.settings.index');
        Route::post('/admin/settings/{key}', [SettingsController::class, 'update'])->name('admin.settings.update');
        Route::post('/admin/settings-bulk', [SettingsController::class, 'updateBulk'])->name('admin.settings.bulk-update');
        Route::get('/admin/settings/audit-log', [SettingsController::class, 'auditLog'])->name('admin.settings.audit');
        
        // Notification Preferences
        Route::get('/admin/notifications/preferences', [\App\Http\Controllers\Admin\NotificationPreferencesController::class, 'show'])->name('admin.notifications.preferences');
        Route::post('/admin/notifications/update', [\App\Http\Controllers\Admin\NotificationPreferencesController::class, 'update'])->name('admin.notifications.update');
    });
});
