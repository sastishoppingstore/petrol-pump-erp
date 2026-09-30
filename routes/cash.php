<?php

use App\Http\Controllers\CashController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Cash Book / Roznamcha Routes (Phase 4.8)
|--------------------------------------------------------------------------
| Forecourt cash flow, Cash Receipt Vouchers (CRV), Cash Payment Vouchers (CPV),
| Daily Roznamcha register, and strict non-negative till validation.
*/

Route::middleware('permission:cash.view')->group(function () {
    Route::get('/cash', [CashController::class, 'index'])->name('cash.index');
    Route::get('/cash/voucher/{cashEntry}', [CashController::class, 'show'])->name('cash.show');
    Route::get('/cash/voucher/{cashEntry}/print', [CashController::class, 'print'])->name('cash.print');
});

Route::middleware('permission:cash.create')->group(function () {
    Route::get('/cash/in', [CashController::class, 'createIn'])->name('cash.create-in');
    Route::post('/cash/in', [CashController::class, 'storeIn'])->name('cash.store-in');

    Route::get('/cash/out', [CashController::class, 'createOut'])->name('cash.create-out');
    Route::post('/cash/out', [CashController::class, 'storeOut'])->name('cash.store-out');
});
