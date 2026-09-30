<?php

use App\Http\Controllers\PurchaseController;
use App\Support\PermissionList;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Fuel Purchases & Decantations (Tiles 8 & 9)
|--------------------------------------------------------------------------
*/

Route::middleware('permission:' . PermissionList::PURCHASE_VIEW)->group(function () {
    Route::get('/purchases', [PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('/purchases/{purchase}', [PurchaseController::class, 'show'])->name('purchases.show');
});

Route::middleware('permission:' . PermissionList::PURCHASE_CREATE)->group(function () {
    Route::get('/purchases/create', [PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('/purchases', [PurchaseController::class, 'store'])->name('purchases.store');
});

Route::middleware('permission:' . PermissionList::PURCHASE_APPROVE)->group(function () {
    Route::post('/purchases/{purchase}/approve', [PurchaseController::class, 'approve'])->name('purchases.approve');
    Route::post('/purchases/{purchase}/shortage-claim', [PurchaseController::class, 'resolveShortageClaim'])->name('purchases.shortage-claim');
});

/*
|--------------------------------------------------------------------------
| Suppliers & Creditors Management (Tile 7)
|--------------------------------------------------------------------------
*/

Route::middleware('permission:' . PermissionList::SUPPLIER_VIEW)->group(function () {
    Route::get('/suppliers', [\App\Http\Controllers\SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'show'])->name('suppliers.show');
    Route::get('/suppliers/{supplier}/statement', [\App\Http\Controllers\SupplierController::class, 'statement'])->name('suppliers.statement');
});

Route::middleware('permission:' . PermissionList::SUPPLIER_CREATE)->group(function () {
    Route::get('/suppliers/create', [\App\Http\Controllers\SupplierController::class, 'create'])->name('suppliers.create');
    Route::post('/suppliers', [\App\Http\Controllers\SupplierController::class, 'store'])->name('suppliers.store');
});

Route::middleware('permission:' . PermissionList::SUPPLIER_EDIT)->group(function () {
    Route::get('/suppliers/{supplier}/edit', [\App\Http\Controllers\SupplierController::class, 'edit'])->name('suppliers.edit');
    Route::put('/suppliers/{supplier}', [\App\Http\Controllers\SupplierController::class, 'update'])->name('suppliers.update');
});

Route::middleware('permission:' . PermissionList::SUPPLIER_PAYMENT)->group(function () {
    Route::post('/suppliers/{supplier}/payments', [\App\Http\Controllers\SupplierController::class, 'recordPayment'])->name('suppliers.payments.store');
});

