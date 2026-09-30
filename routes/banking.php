<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BankingController;

Route::middleware(['auth', 'branch.scope'])->group(function () {
    // Bank Accounts
    Route::get('/bank-accounts', [BankingController::class, 'bankAccounts'])->name('bank-accounts.list');
    Route::get('/bank-accounts/{account}', [BankingController::class, 'bankAccountDetail'])->name('bank-accounts.detail');
    Route::post('/bank-accounts', [BankingController::class, 'createBankAccount'])->name('bank-accounts.create');

    // Bank Deposits
    Route::post('/deposits', [BankingController::class, 'recordDeposit'])->name('deposits.record');

    // Cheques
    Route::get('/cheques', [BankingController::class, 'cheques'])->name('cheques.list');
    Route::post('/cheques', [BankingController::class, 'issueCheque'])->name('cheques.issue');
    Route::put('/cheques/{cheque}/clear', [BankingController::class, 'clearCheque'])->name('cheques.clear');
    Route::put('/cheques/{cheque}/bounce', [BankingController::class, 'bounceCheque'])->name('cheques.bounce');

    // Bank Transactions
    Route::get('/bank-transactions', [BankingController::class, 'bankTransactions'])->name('bank-transactions.list');

    // Bank Reconciliation
    Route::get('/reconciliation-report', [BankingController::class, 'reconciliationReport'])->name('reconciliation.report');
    Route::post('/reconciliation', [BankingController::class, 'reconcileStatement'])->name('reconciliation.reconcile');
});
