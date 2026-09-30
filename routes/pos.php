<?php

use App\Http\Controllers\PosController;
use App\Http\Controllers\SaleVoidController;
use App\Http\Controllers\SalesHistoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:sales.view')->group(function () {
    Route::get('/sales', [SalesHistoryController::class, 'index'])->name('sales.index');
    Route::get('/sales/{sale}', [SalesHistoryController::class, 'show'])->name('sales.show');
});

Route::middleware('permission:sales.create')->group(function () {
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos', [PosController::class, 'store'])->name('pos.store');
});

Route::middleware('permission:sales.create,sales.view,sales.print')->group(function () {
    Route::get('/pos/success/{sale}', [PosController::class, 'success'])->name('pos.success');
    Route::get('/pos/receipt/{sale}', [PosController::class, 'receipt'])->name('pos.receipt');
    Route::get('/pos/thermal/{sale}', [PosController::class, 'thermal'])->name('pos.thermal');
});

Route::middleware('permission:sales.void,sales.refund')->group(function () {
    Route::get('/sales/{sale}/void', [SaleVoidController::class, 'edit'])->name('sales.void');
    Route::put('/sales/{sale}/void', [SaleVoidController::class, 'update'])->name('sales.void.update');
});
