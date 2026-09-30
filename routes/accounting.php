<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccountingController;

Route::middleware(['auth', 'branch.scope'])->group(function () {
    // Trial Balance & Financial Reports
    Route::get('/trial-balance', [AccountingController::class, 'trialBalance'])->name('trial-balance');
    Route::get('/financial-summary', [AccountingController::class, 'financialSummary'])->name('financial-summary');

    // Journal Entries
    Route::get('/journal-entries', [AccountingController::class, 'journalEntries'])->name('journal-entries.list');
    Route::get('/journal-entries/{entry}', [AccountingController::class, 'journalEntryDetail'])->name('journal-entries.detail');

    // Account Ledger
    Route::get('/account-ledger/{account}', [AccountingController::class, 'accountLedger'])->name('account-ledger');

    // Manual Transaction Posting
    Route::post('/post-sale/{sale}', [AccountingController::class, 'postSaleTransaction'])->name('post-sale');
    Route::post('/post-purchase/{purchase}', [AccountingController::class, 'postPurchaseTransaction'])->name('post-purchase');

    // Create Account (admin only)
    Route::post('/accounts', [AccountingController::class, 'createAccount'])->name('accounts.create');
});
