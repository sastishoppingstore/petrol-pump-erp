<?php

use App\Http\Controllers\Stock\StockController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Stock (Phase 3)
|--------------------------------------------------------------------------
*/

Route::middleware('permission:stock.view')->group(function () {
    Route::get('/stock', [StockController::class, 'index'])->name('stock.index');
    Route::get('/stock/movements', [StockController::class, 'movements'])->name('stock.movements');
    Route::get('/stock/adjustments', [StockController::class, 'adjustments'])->name('stock.adjustments');
});

Route::middleware('permission:stock.stock_adjustment')->group(function () {
    Route::post('/stock/adjustments', [StockController::class, 'storeAdjustment'])->name('stock.adjustments.store');
});

// Approving moves real stock, so it is gated separately from raising a request.
Route::middleware('permission:stock.approve')->group(function () {
    Route::post('/stock/adjustments/{adjustment}/approve', [StockController::class, 'approveAdjustment'])
        ->name('stock.adjustments.approve');
    Route::post('/stock/adjustments/{adjustment}/reject', [StockController::class, 'rejectAdjustment'])
        ->name('stock.adjustments.reject');
});
