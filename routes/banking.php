<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BankingController;

/*
|--------------------------------------------------------------------------
| Banking JSON API (currently NOT registered)
|--------------------------------------------------------------------------
| NOTE: routes/web.php loads a fixed module list (fuel, stock, shift, pos,
| sales, bank, cash, purchase, accounts, reports, settings, cheques,
| employees, expenses, approvals, payroll) — this file is not in it, so
| none of these endpoints are live. The HTML banking UI lives in
| routes/bank.php (BankController).
|
| Route names are namespaced `banking.*` on purpose: the old names
| (bank-accounts.create, cheques.*, …) collided with routes/bank.php and
| routes/cheques.php, and POST /bank-accounts duplicated BankController's
| account store. If this API is ever enabled, register it deliberately
| (e.g. under an /api prefix) instead of adding it to the web module
| list as-is. The previous `branch.scope` middleware alias also did not
| exist and has been removed.
*/

Route::middleware(['auth'])->group(function () {
    // Bank Accounts
    Route::get('/banking/accounts', [BankingController::class, 'bankAccounts'])->name('banking.accounts.index');
    Route::get('/banking/accounts/{account}', [BankingController::class, 'bankAccountDetail'])->name('banking.accounts.show');
    Route::post('/banking/accounts', [BankingController::class, 'createBankAccount'])->name('banking.accounts.store');

    // Bank Deposits
    Route::post('/banking/deposits', [BankingController::class, 'recordDeposit'])->name('banking.deposits.store');

    // Cheques
    Route::get('/banking/cheques', [BankingController::class, 'cheques'])->name('banking.cheques.index');
    Route::post('/banking/cheques', [BankingController::class, 'issueCheque'])->name('banking.cheques.store');
    Route::put('/banking/cheques/{cheque}/clear', [BankingController::class, 'clearCheque'])->name('banking.cheques.clear');
    Route::put('/banking/cheques/{cheque}/bounce', [BankingController::class, 'bounceCheque'])->name('banking.cheques.bounce');

    // Bank Transactions
    Route::get('/banking/transactions', [BankingController::class, 'bankTransactions'])->name('banking.transactions.index');

    // Bank Reconciliation
    Route::get('/banking/reconciliation-report', [BankingController::class, 'reconciliationReport'])->name('banking.reconciliation.report');
    Route::post('/banking/reconciliation', [BankingController::class, 'reconcileStatement'])->name('banking.reconciliation.store');
});
