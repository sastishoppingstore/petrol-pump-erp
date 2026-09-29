<?php

use App\Http\Controllers\Shift\ShiftController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Shifts (Phase 4)
|--------------------------------------------------------------------------
*/

Route::prefix('shifts')->name('shifts.')->group(function () {
    Route::get('/', [ShiftController::class, 'index'])
        ->middleware('permission:shift.view')
        ->name('index');

    Route::get('/open', [ShiftController::class, 'create'])
        ->middleware('permission:shift.create')
        ->name('create');

    Route::post('/', [ShiftController::class, 'store'])
        ->middleware('permission:shift.create')
        ->name('store');

    Route::get('/{shift}', [ShiftController::class, 'show'])
        ->middleware('permission:shift.view')
        ->name('show');

    Route::get('/{shift}/close', [ShiftController::class, 'closeForm'])
        ->name('close.form');

    Route::post('/{shift}/close', [ShiftController::class, 'close'])
        ->name('close');

    Route::post('/{shift}/approve', [ShiftController::class, 'approve'])
        ->name('approve');

    Route::post('/{shift}/cash', [ShiftController::class, 'addCash'])
        ->name('cash');

    Route::get('/{shift}/print', [ShiftController::class, 'print'])
        ->name('print');
});
