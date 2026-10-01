<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SettingsController;
use App\Support\PermissionList;
use Illuminate\Support\Facades\Route;

// Settings
Route::prefix('settings')->name('settings.')->group(function () {
    Route::get('/', [SettingsController::class, 'index'])
        ->middleware('permission:' . PermissionList::SETTINGS_VIEW)
        ->name('index');

    Route::put('/', [SettingsController::class, 'update'])
        ->middleware('permission:' . PermissionList::SETTINGS_EDIT)
        ->name('update');
});

// Site language toggle — header EN | اردو button flips ui_language site-wide
Route::post('/settings/language', [SettingsController::class, 'setLanguage'])
    ->middleware('permission:' . PermissionList::SETTINGS_EDIT)
    ->name('settings.language');

// Notifications
Route::prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [NotificationController::class, 'index'])->name('index');
    Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
    Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
});

// Audit Logs
Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
    Route::get('/', [AuditLogController::class, 'index'])
        ->middleware('permission:' . PermissionList::AUDIT_VIEW)
        ->name('index');

    Route::get('/export', [AuditLogController::class, 'export'])
        ->middleware('permission:' . PermissionList::AUDIT_EXPORT)
        ->name('export');
});

// Backups
Route::prefix('backups')->name('backups.')->group(function () {
    Route::get('/', [BackupController::class, 'index'])
        ->middleware('permission:' . PermissionList::BACKUP_VIEW)
        ->name('index');

    Route::post('/database', [BackupController::class, 'createDatabase'])
        ->middleware('permission:' . PermissionList::BACKUP_CREATE)
        ->name('database');

    Route::post('/full', [BackupController::class, 'createFull'])
        ->middleware('permission:' . PermissionList::BACKUP_CREATE)
        ->name('full');

    Route::get('/download/{backup}', [BackupController::class, 'download'])
        ->name('download');
});
