<?php

use App\Http\Controllers\BankController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:cash.view')->group(function () {
    Route::get('/banks', [BankController::class, 'index'])->name('banks.index');
    Route::get('/bank-deposits', [BankController::class, 'deposits'])->name('bank-deposits.index');
});

Route::middleware('permission:cash.create')->group(function () {
    Route::get('/bank-accounts/create', [BankController::class, 'createAccount'])->name('bank-accounts.create');
    Route::post('/bank-accounts', [BankController::class, 'storeAccount'])->name('bank-accounts.store');
});

Route::middleware('permission:cash.create')->group(function () {
    Route::get('/bank-accounts/{bank_account}/edit', [BankController::class, 'editAccount'])->name('bank-accounts.edit');
    Route::put('/bank-accounts/{bank_account}', [BankController::class, 'updateAccount'])->name('bank-accounts.update');
    Route::delete('/bank-accounts/{bank_account}', [BankController::class, 'deactivateAccount'])->name('bank-accounts.destroy');
});

// The Livewire bank-deposit screen: pick a bank, then one of its accounts.
// Declared with the fully-qualified class name because this file is included
// from inside a loop in web.php, where a `use` alias would not resolve.
Route::middleware('permission:cash.view')->group(function () {
    Route::get('/cash-deposits', function () {
        return view('banks.deposit-page');
    })->name('cash-deposits');
});
