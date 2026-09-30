<?php

namespace App\Services\Banking;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationItem;
use App\Models\Cheque;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BankingService
{
    /**
     * Record a bank deposit (cash from shift to bank)
     */
    public function recordDeposit(
        int $bankAccountId,
        string $referenceNumber,
        string $amount,
        string $description,
        ?int $shiftId = null
    ): BankTransaction {
        return DB::transaction(function () use ($bankAccountId, $referenceNumber, $amount, $description, $shiftId) {
            $bankAccount = BankAccount::findOrFail($bankAccountId);

            $transaction = BankTransaction::create([
                'branch_id' => auth()->user()->branch_id ?? 1,
                'bank_account_id' => $bankAccountId,
                'type' => BankTransaction::TYPE_DEPOSIT,
                'reference_number' => $referenceNumber,
                'description' => $description,
                'debit_amount' => 0,
                'credit_amount' => $amount,
                'transaction_date' => now()->date,
                'value_date' => now()->date,
                'shift_id' => $shiftId,
            ]);

            // Update bank account balance
            $newBalance = bcadd($bankAccount->current_balance, $amount, 2);
            $bankAccount->update(['current_balance' => $newBalance]);

            Log::info('Bank deposit recorded', ['account_id' => $bankAccountId, 'amount' => $amount, 'transaction_id' => $transaction->id]);

            return $transaction;
        });
    }

    /**
     * Record a cheque issue
     */
    public function issueCheque(
        int $bankAccountId,
        string $chequeNumber,
        string $issuedTo,
        string $amount,
        \DateTime $issueDate,
        \DateTime $dueDate,
        ?string $notes = null
    ): Cheque {
        return Cheque::create([
            'bank_account_id' => $bankAccountId,
            'cheque_number' => $chequeNumber,
            'issued_to' => $issuedTo,
            'amount' => $amount,
            'issue_date' => $issueDate,
            'due_date' => $dueDate,
            'status' => Cheque::STATUS_ISSUED,
            'notes' => $notes,
        ]);
    }

    /**
     * Mark cheque as presented (sent to bank)
     */
    public function presentCheque(Cheque $cheque): void
    {
        $cheque->update(['status' => Cheque::STATUS_PRESENTED]);
        Log::info('Cheque presented', ['cheque_id' => $cheque->id, 'number' => $cheque->cheque_number]);
    }

    /**
     * Mark cheque as cleared
     */
    public function clearCheque(Cheque $cheque, int $bankAccountId): void
    {
        DB::transaction(function () use ($cheque, $bankAccountId) {
            $cheque->markCleared();

            // Record bank transaction
            BankTransaction::create([
                'branch_id' => auth()->user()->branch_id ?? 1,
                'bank_account_id' => $bankAccountId,
                'type' => BankTransaction::TYPE_CHEQUE_CLEARED,
                'reference_number' => $cheque->cheque_number,
                'description' => "Cheque cleared: {$cheque->cheque_number} to {$cheque->issued_to}",
                'debit_amount' => $cheque->amount,
                'credit_amount' => 0,
                'transaction_date' => now()->date,
                'value_date' => now()->date,
            ]);

            // Update bank account balance
            $bankAccount = BankAccount::findOrFail($bankAccountId);
            $newBalance = bcsub($bankAccount->current_balance, $cheque->amount, 2);
            $bankAccount->update(['current_balance' => $newBalance]);

            Log::info('Cheque cleared', ['cheque_id' => $cheque->id, 'number' => $cheque->cheque_number]);
        });
    }

    /**
     * Mark cheque as bounced
     */
    public function bounceCheque(Cheque $cheque, int $bankAccountId, string $reason): void
    {
        DB::transaction(function () use ($cheque, $bankAccountId, $reason) {
            $cheque->update([
                'status' => Cheque::STATUS_BOUNCED,
                'notes' => ($cheque->notes ? $cheque->notes . "\n" : '') . "BOUNCED: {$reason}",
            ]);

            // Reverse the payment - credit back to account
            BankTransaction::create([
                'branch_id' => auth()->user()->branch_id ?? 1,
                'bank_account_id' => $bankAccountId,
                'type' => BankTransaction::TYPE_CHEQUE_BOUNCED,
                'reference_number' => $cheque->cheque_number,
                'description' => "Cheque bounced: {$cheque->cheque_number} to {$cheque->issued_to} - {$reason}",
                'debit_amount' => 0,
                'credit_amount' => $cheque->amount,
                'transaction_date' => now()->date,
                'value_date' => now()->date,
            ]);

            // Restore bank account balance
            $bankAccount = BankAccount::findOrFail($bankAccountId);
            $newBalance = bcadd($bankAccount->current_balance, $cheque->amount, 2);
            $bankAccount->update(['current_balance' => $newBalance]);

            Log::warning('Cheque bounced', ['cheque_id' => $cheque->id, 'number' => $cheque->cheque_number, 'reason' => $reason]);
        });
    }

    /**
     * Get bank reconciliation for a statement
     */
    public function getBankReconciliation(int $bankAccountId, \DateTime $statementDate): ?BankReconciliation
    {
        return BankReconciliation::where('bank_account_id', $bankAccountId)
            ->whereDate('statement_date', $statementDate)
            ->first();
    }

    /**
     * Compute book balance (from journal entries + transactions)
     */
    public function computeBookBalance(int $bankAccountId, \DateTime $asOfDate): string
    {
        $bankAccount = BankAccount::findOrFail($bankAccountId);

        $transactionSum = BankTransaction::where('bank_account_id', $bankAccountId)
            ->whereDate('transaction_date', '<=', $asOfDate->format('Y-m-d'))
            ->selectRaw('COALESCE(SUM(credit_amount), 0) - COALESCE(SUM(debit_amount), 0) as net')
            ->value('net');

        return bcadd($bankAccount->opening_balance, (string) $transactionSum, 2);
    }

    /**
     * Create/update bank reconciliation with matching
     */
    public function reconcileStatement(
        int $bankAccountId,
        \DateTime $statementDate,
        string $statementBalance,
        array $matchedTransactionIds = []
    ): BankReconciliation {
        return DB::transaction(function () use ($bankAccountId, $statementDate, $statementBalance, $matchedTransactionIds) {
            $bookBalance = $this->computeBookBalance($bankAccountId, $statementDate);

            $reconciliation = BankReconciliation::updateOrCreate(
                [
                    'bank_account_id' => $bankAccountId,
                    'statement_date' => $statementDate,
                ],
                [
                    'branch_id' => auth()->user()->branch_id ?? 1,
                    'statement_balance' => $statementBalance,
                    'book_balance' => $bookBalance,
                    'reconciliation_date' => now(),
                    'reconciled_by' => auth()->id(),
                    'status' => $reconciliation->isBalanced() ? BankReconciliation::STATUS_VERIFIED : BankReconciliation::STATUS_COMPLETED,
                ]
            );

            // Mark matched transactions as reconciled
            foreach ($matchedTransactionIds as $transactionId) {
                $transaction = BankTransaction::findOrFail($transactionId);
                $transaction->markReconciled();

                BankReconciliationItem::create([
                    'bank_reconciliation_id' => $reconciliation->id,
                    'bank_transaction_id' => $transactionId,
                ]);
            }

            Log::info('Bank reconciliation completed', [
                'account_id' => $bankAccountId,
                'statement_date' => $statementDate->format('Y-m-d'),
                'difference' => $reconciliation->getDifferenceAttribute(),
            ]);

            return $reconciliation;
        });
    }

    /**
     * Get reconciliation report (outstanding items, difference)
     */
    public function getReconciliationReport(int $bankAccountId, \DateTime $asOfDate): array
    {
        $bookBalance = $this->computeBookBalance($bankAccountId, $asOfDate);

        $reconciledTotal = BankTransaction::where('bank_account_id', $bankAccountId)
            ->whereDate('transaction_date', '<=', $asOfDate->format('Y-m-d'))
            ->where('reconciled', true)
            ->selectRaw('COALESCE(SUM(credit_amount), 0) - COALESCE(SUM(debit_amount), 0) as net')
            ->value('net') ?? 0;

        $unreconciledTransactions = BankTransaction::where('bank_account_id', $bankAccountId)
            ->whereDate('transaction_date', '<=', $asOfDate->format('Y-m-d'))
            ->where('reconciled', false)
            ->orderBy('transaction_date', 'asc')
            ->get();

        return [
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'book_balance' => $bookBalance,
            'reconciled_total' => (string) $reconciledTotal,
            'unreconciled_total' => $unreconciledTransactions->sum(function ($t) {
                return (float) bcsub($t->credit_amount, $t->debit_amount, 2);
            }),
            'unreconciled_count' => $unreconciledTransactions->count(),
            'unreconciled_transactions' => $unreconciledTransactions->map(function ($t) {
                return [
                    'id' => $t->id,
                    'date' => $t->transaction_date->format('Y-m-d'),
                    'type' => $t->type,
                    'reference' => $t->reference_number,
                    'amount' => bcsub($t->credit_amount, $t->debit_amount, 2),
                ];
            })->toArray(),
        ];
    }
}
