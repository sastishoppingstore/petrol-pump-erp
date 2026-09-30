<?php

use App\Http\Controllers\DailyClosingController;
use App\Http\Controllers\JournalController;
use App\Support\PermissionList;
use Illuminate\Support\Facades\Route;

// Daily Closing Wizard
Route::prefix('closing')->name('closing.')->group(function () {
    Route::get('/', [DailyClosingController::class, 'index'])
        ->middleware('permission:' . PermissionList::CLOSING_VIEW)
        ->name('index');

    Route::post('/', [DailyClosingController::class, 'store'])
        ->middleware('permission:' . PermissionList::CLOSING_CREATE)
        ->name('store');

    Route::post('/{closing}/unlock', [DailyClosingController::class, 'unlock'])
        ->middleware('permission:' . PermissionList::CLOSING_APPROVE)
        ->name('unlock');
});

// General Ledger Journals
Route::prefix('journals')->name('journals.')->group(function () {
    Route::get('/', [JournalController::class, 'index'])
        ->middleware('permission:' . PermissionList::JOURNAL_VIEW)
        ->name('index');

    Route::get('/create', [JournalController::class, 'create'])
        ->middleware('permission:' . PermissionList::JOURNAL_CREATE)
        ->name('create');

    Route::post('/', [JournalController::class, 'store'])
        ->middleware('permission:' . PermissionList::JOURNAL_CREATE)
        ->name('store');

    Route::post('/{entry}/void', [JournalController::class, 'void'])
        ->middleware('permission:' . PermissionList::JOURNAL_CREATE)
        ->name('void');

    Route::get('/trial-balance', [JournalController::class, 'trialBalance'])
        ->middleware('permission:' . PermissionList::JOURNAL_VIEW)
        ->name('trial-balance');
});
