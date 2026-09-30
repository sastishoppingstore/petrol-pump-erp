<?php

use App\Http\Controllers\CustomerController;
use App\Support\PermissionList;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Customer & Udhaar Management
|--------------------------------------------------------------------------
*/

Route::middleware('permission:' . PermissionList::CUSTOMER_VIEW)->group(function () {
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/ageing', [CustomerController::class, 'ageingReport'])->name('customers.ageing');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->name('customers.show');
    Route::get('/customers/{customer}/statement', [CustomerController::class, 'statement'])->name('customers.statement');
    Route::get('/customers/{customer}/statement/pdf', [CustomerController::class, 'statementPdf'])->name('customers.statement.pdf');
});

Route::middleware('permission:' . PermissionList::CUSTOMER_CREATE)->group(function () {
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::post('/customers', [CustomerController::class, 'store'])->name('customers.store');
    Route::post('/customers/{customer}/vehicles', [CustomerController::class, 'addVehicle'])->name('customers.vehicles.store');
    Route::delete('/customers/{customer}/vehicles/{vehicle}', [CustomerController::class, 'removeVehicle'])->name('customers.vehicles.destroy');
});

Route::middleware('permission:' . PermissionList::CUSTOMER_EDIT)->group(function () {
    Route::get('/customers/{customer}/edit', [CustomerController::class, 'edit'])->name('customers.edit');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->name('customers.update');
});

Route::middleware('permission:' . PermissionList::CUSTOMER_PAYMENT)->group(function () {
    Route::post('/customers/{customer}/payments', [CustomerController::class, 'recordPayment'])->name('customers.payments.store');
});
