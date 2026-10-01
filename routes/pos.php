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

    // Bill-create customer autocomplete + quick-add (W2)
    Route::get('/pos/customers/search', [PosController::class, 'searchCustomers'])->name('pos.customers.search');
    Route::post('/pos/customers/quick-store', [PosController::class, 'quickStoreCustomer'])->name('pos.customers.quick-store');
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

/*
|--------------------------------------------------------------------------
| Picture-First Cashier UI (4 massive tiles + Keypad + Banknotes)
|--------------------------------------------------------------------------
*/

Route::middleware('permission:sales.create')->group(function () {
    Route::get('/cashier', [\App\Http\Controllers\CashierUiController::class, 'index'])->name('cashier.index');
    
    // AJAX endpoints
    Route::get('/cashier/dashboard/{shift}', [\App\Http\Controllers\CashierUiController::class, 'getDashboard'])->name('cashier.dashboard');
    Route::get('/cashier/nozzle-status/{shift}', [\App\Http\Controllers\CashierUiController::class, 'getNozzleStatus'])->name('cashier.nozzle-status');
    Route::get('/cashier/keypad', [\App\Http\Controllers\CashierUiController::class, 'getKeypad'])->name('cashier.keypad');
    Route::get('/cashier/banknotes', [\App\Http\Controllers\CashierUiController::class, 'getBanknotes'])->name('cashier.banknotes');
    Route::post('/cashier/banknotes/calculate', [\App\Http\Controllers\CashierUiController::class, 'calculateBanknoteTotal'])->name('cashier.banknotes.calculate');
    Route::get('/cashier/payment-methods', [\App\Http\Controllers\CashierUiController::class, 'getPaymentMethods'])->name('cashier.payment-methods');
    Route::get('/cashier/signature-config', [\App\Http\Controllers\CashierUiController::class, 'getSignatureConfig'])->name('cashier.signature-config');
    Route::get('/cashier/theme', [\App\Http\Controllers\CashierUiController::class, 'getTheme'])->name('cashier.theme');
    Route::post('/cashier/refresh-dashboard/{shift}', [\App\Http\Controllers\CashierUiController::class, 'refreshDashboard'])->name('cashier.refresh-dashboard');
});
