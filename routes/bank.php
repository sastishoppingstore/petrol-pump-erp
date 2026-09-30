<?php

use App\Http\Controllers\BankController;
use Illuminate\Support\Facades\Route;

Route::middleware('permission:cash.view')->group(function () {
    Route::get('/banks', [BankController::class, 'index'])->name('banks.index');
    Route::get('/bank-deposits', [BankController::class, 'deposits'])->name('bank-deposits.index');
    Route::get('/bank-accounts/{bank_account}/book', [BankController::class, 'bankBook'])->name('banks.book');
    Route::get('/bank-accounts/{bank_account}/reconciliation', [BankController::class, 'reconciliation'])->name('banks.reconciliation');
});

Route::middleware('permission:cash.create')->group(function () {
    Route::get('/bank-accounts/create', [BankController::class, 'createAccount'])->name('bank-accounts.create');
    Route::post('/bank-accounts', [BankController::class, 'storeAccount'])->name('bank-accounts.store');
    Route::get('/bank-accounts/{bank_account}/edit', [BankController::class, 'editAccount'])->name('bank-accounts.edit');
    Route::put('/bank-accounts/{bank_account}', [BankController::class, 'updateAccount'])->name('bank-accounts.update');
    Route::delete('/bank-accounts/{bank_account}', [BankController::class, 'deactivateAccount'])->name('bank-accounts.destroy');

    // Deposit, Withdrawal, Transfer & Reconciliation
    Route::post('/bank-deposits', [BankController::class, 'storeDeposit'])->name('bank-deposits.store');
    Route::post('/bank-accounts/{bank_account}/withdraw', [BankController::class, 'withdraw'])->name('banks.withdraw');
    Route::post('/banks/transfer', [BankController::class, 'transfer'])->name('banks.transfer');
    Route::post('/bank-accounts/{bank_account}/charges', [BankController::class, 'applyChargesOrMarkup'])->name('banks.charges');
    Route::post('/bank-accounts/{bank_account}/reconciliation', [BankController::class, 'storeReconciliation'])->name('banks.reconciliation.store');
    Route::post('/bank-accounts/{bank_account}/import-csv', [BankController::class, 'importCsv'])->name('banks.import-csv');
});

// The Livewire bank-deposit screen: pick a bank, then one of its accounts.
Route::middleware('permission:cash.view')->group(function () {
    Route::get('/cash-deposits', function () {
        return view('banks.deposit-page');
    })->name('cash-deposits');
});
