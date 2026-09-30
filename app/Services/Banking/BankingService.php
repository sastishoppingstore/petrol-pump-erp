<?php

namespace App\Services\Banking;

use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\Shift;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Money;
use App\Support\PermissionList;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class BankingService
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly NumberSequenceService $sequences,
    ) {
    }

    /**
     * Record a bank deposit (cash -> bank).
     */
    public function deposit(
        User $actor,
        int $branchId,
        BankAccount $account,
        string $amount,
        ?Shift $shift = null,
        ?string $reference = null,
        ?string $slipPath = null,
        ?string $notes = null,
        ?DateTimeInterface $date = null,
    ): BankTransaction {
        $amount = Money::round(Money::n($amount));
        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages(['amount' => 'Deposit amount must be greater than zero.']);
        }

        if (! $account->isActive()) {
            throw ValidationException::withMessages(['bank_account_id' => 'That bank account is inactive.']);
        }

        // Validate shift cash if shift is given and open
        if ($shift && $shift->isOpen()) {
            $expected = Money::n(app(\App\Services\Shift\ShiftService::class)->summary($shift)['expected_cash'] ?? '0.00');
            $alreadyOut = Money::n(DB::table('shift_cash')
                ->where('shift_id', $shift->id)
                ->whereIn('entry_type', ['BANK_DEPOSIT', 'DROP', 'HANDOVER', 'EXPENSE'])
                ->sum('amount'));

            $available = Money::subtract($expected, $alreadyOut);
            if (Money::compare($amount, $available) > 0) {
                throw ValidationException::withMessages([
                    'amount' => sprintf('Deposit of %s exceeds the cash (%s) available in shift %s.',
                        Money::format($amount),
                        Money::format($available),
                        $shift->shift_number
                    ),
                ]);
            }
        }

        return DB::transaction(function () use ($actor, $branchId, $account, $amount, $shift, $reference, $slipPath, $notes, $date) {
            $locked = BankAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = $locked->currentBalance();
            $balanceAfter = Money::add($balanceBefore, $amount);
            $refNumber = $reference ?: ('DEP-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));
            $txnDate = $date ? Carbon::instance($date) : now();

            $transaction = BankTransaction::create([
                'branch_id' => $branchId,
                'bank_account_id' => $locked->id,
                'type' => BankTransaction::TYPE_DEPOSIT,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_number' => $refNumber,
                'transaction_date' => $txnDate,
                'description' => $notes ?: "Cash deposit into {$locked->bank?->name} ({$locked->account_number})",
                'performed_by' => $actor->id,
                'slip_path' => $slipPath,
                'shift_id' => $shift?->id,
                'status' => BankTransaction::STATUS_COMPLETED,
            ]);

            // Also keep legacy bank_deposits synced
            BankDeposit::create([
                'branch_id' => $branchId,
                'bank_account_id' => $locked->id,
                'bank_name' => $locked->bank?->name ?? 'Unknown',
                'shift_id' => $shift?->id,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_number' => $refNumber,
                'deposited_at' => $txnDate,
                'reason' => $notes,
                'deposit_type' => BankDeposit::TYPE_CASH,
                'deposited_by' => $actor->id,
                'slip_path' => $slipPath,
                'status' => BankDeposit::STATUS_COMPLETED,
            ]);

            if ($shift) {
                DB::table('shift_cash')->insert([
                    'shift_id' => $shift->id,
                    'entry_type' => 'BANK_DEPOSIT',
                    'amount' => $amount,
                    'notes' => "Bank deposit {$refNumber} to {$locked->bank?->name}",
                    'user_id' => $actor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'bank_deposit',
                module: 'banking',
                referenceType: BankTransaction::class,
                referenceId: $transaction->id,
                newData: [
                    'account' => $locked->account_title,
                    'amount' => $amount,
                    'reference' => $refNumber,
                ],
            );

            return $transaction;
        });
    }

    /**
     * Record a bank withdrawal (bank -> cash drawer / cash out).
     */
    public function withdraw(
        User $actor,
        int $branchId,
        BankAccount $account,
        string $amount,
        string $reason,
        ?string $reference = null,
        ?Shift $shift = null,
        ?DateTimeInterface $date = null,
    ): BankTransaction {
        $amount = Money::round(Money::n($amount));
        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages(['amount' => 'Withdrawal amount must be greater than zero.']);
        }

        if (! $account->isActive()) {
            throw ValidationException::withMessages(['bank_account_id' => 'That bank account is inactive.']);
        }

        return DB::transaction(function () use ($actor, $branchId, $account, $amount, $reason, $reference, $shift, $date) {
            $locked = BankAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = $locked->currentBalance();
            if (Money::compare($balanceBefore, $amount) < 0) {
                throw ValidationException::withMessages([
                    'amount' => sprintf('Withdrawal of %s exceeds available bank balance %s.', Money::format($amount), Money::format($balanceBefore)),
                ]);
            }

            $balanceAfter = Money::subtract($balanceBefore, $amount);
            $refNumber = $reference ?: ('WDL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));
            $txnDate = $date ? Carbon::instance($date) : now();

            $transaction = BankTransaction::create([
                'branch_id' => $branchId,
                'bank_account_id' => $locked->id,
                'type' => BankTransaction::TYPE_WITHDRAWAL,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_number' => $refNumber,
                'transaction_date' => $txnDate,
                'description' => "Bank withdrawal: {$reason}",
                'performed_by' => $actor->id,
                'shift_id' => $shift?->id,
                'status' => BankTransaction::STATUS_COMPLETED,
            ]);

            // If shift given, cash came into the till
            if ($shift) {
                DB::table('shift_cash')->insert([
                    'shift_id' => $shift->id,
                    'entry_type' => 'CASH_IN',
                    'amount' => $amount,
                    'notes' => "Bank withdrawal {$refNumber} from {$locked->bank?->name}",
                    'user_id' => $actor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'bank_withdrawal',
                module: 'banking',
                referenceType: BankTransaction::class,
                referenceId: $transaction->id,
                newData: [
                    'account' => $locked->account_title,
                    'amount' => $amount,
                    'reason' => $reason,
                ],
            );

            return $transaction;
        });
    }

    /**
     * Bank-to-bank transfer with optional bank transfer charges.
     */
    public function transfer(
        User $actor,
        int $branchId,
        BankAccount $fromAccount,
        BankAccount $toAccount,
        string $amount,
        string $charges = '0.00',
        ?string $reference = null,
        ?string $notes = null,
        ?DateTimeInterface $date = null,
    ): array {
        if ($fromAccount->id === $toAccount->id) {
            throw ValidationException::withMessages(['to_account_id' => 'Source and destination accounts must be different.']);
        }

        $amount = Money::round(Money::n($amount));
        $charges = Money::round(Money::n($charges));

        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages(['amount' => 'Transfer amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($actor, $branchId, $fromAccount, $toAccount, $amount, $charges, $reference, $notes, $date) {
            // Lock in consistent order to prevent deadlock
            $ids = [$fromAccount->id, $toAccount->id];
            sort($ids);
            $accounts = BankAccount::query()->whereIn('id', $ids)->lockForUpdate()->get()->keyBy('id');

            $lockedFrom = $accounts->get($fromAccount->id);
            $lockedTo = $accounts->get($toAccount->id);

            $totalRequired = Money::add($amount, $charges);
            $fromBalance = $lockedFrom->currentBalance();

            if (Money::compare($fromBalance, $totalRequired) < 0) {
                throw ValidationException::withMessages([
                    'amount' => sprintf('Insufficient balance in %s. Available: %s, Required: %s (including charges %s).',
                        $lockedFrom->account_title,
                        Money::format($fromBalance),
                        Money::format($totalRequired),
                        Money::format($charges)
                    ),
                ]);
            }

            $refNumber = $reference ?: ('TRF-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));
            $txnDate = $date ? Carbon::instance($date) : now();

            // 1. Outgoing from source account
            $fromAfter = Money::subtract($fromBalance, $amount);
            $outTxn = BankTransaction::create([
                'branch_id' => $branchId,
                'bank_account_id' => $lockedFrom->id,
                'type' => BankTransaction::TYPE_TRANSFER_OUT,
                'amount' => $amount,
                'balance_before' => $fromBalance,
                'balance_after' => $fromAfter,
                'reference_number' => $refNumber,
                'transaction_date' => $txnDate,
                'description' => "Transfer to {$lockedTo->bank?->name} ({$lockedTo->maskedAccountNumber()}): " . ($notes ?: 'Bank Transfer'),
                'performed_by' => $actor->id,
                'related_account_id' => $lockedTo->id,
                'status' => BankTransaction::STATUS_COMPLETED,
            ]);

            // 2. Incoming to destination account
            $toBefore = $lockedTo->currentBalance();
            $toAfter = Money::add($toBefore, $amount);
            $inTxn = BankTransaction::create([
                'branch_id' => $branchId,
                'bank_account_id' => $lockedTo->id,
                'type' => BankTransaction::TYPE_TRANSFER_IN,
                'amount' => $amount,
                'balance_before' => $toBefore,
                'balance_after' => $toAfter,
                'reference_number' => $refNumber,
                'transaction_date' => $txnDate,
                'description' => "Transfer from {$lockedFrom->bank?->name} ({$lockedFrom->maskedAccountNumber()}): " . ($notes ?: 'Bank Transfer'),
                'performed_by' => $actor->id,
                'related_account_id' => $lockedFrom->id,
                'status' => BankTransaction::STATUS_COMPLETED,
            ]);

            // 3. Bank charges (if applicable)
            $chargesTxn = null;
            if (Money::compare($charges, '0.00') > 0) {
                $chargeBefore = $fromAfter;
                $chargeAfter = Money::subtract($chargeBefore, $charges);
                $chargesTxn = BankTransaction::create([
                    'branch_id' => $branchId,
                    'bank_account_id' => $lockedFrom->id,
                    'type' => BankTransaction::TYPE_CHARGES,
                    'amount' => $charges,
                    'balance_before' => $chargeBefore,
                    'balance_after' => $chargeAfter,
                    'reference_number' => $refNumber . '-CHG',
                    'transaction_date' => $txnDate,
                    'description' => "Bank charges for transfer {$refNumber}",
                    'performed_by' => $actor->id,
                    'status' => BankTransaction::STATUS_COMPLETED,
                ]);
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'bank_transfer',
                module: 'banking',
                referenceType: BankTransaction::class,
                referenceId: $outTxn->id,
                newData: [
                    'from' => $lockedFrom->account_title,
                    'to' => $lockedTo->account_title,
                    'amount' => $amount,
                    'charges' => $charges,
                    'reference' => $refNumber,
                ],
            );

            return [
                'out' => $outTxn,
                'in' => $inTxn,
                'charges' => $chargesTxn,
            ];
        });
    }

    /**
     * Record Bank Charges or Markup.
     */
    public function applyChargesOrMarkup(
        User $actor,
        int $branchId,
        BankAccount $account,
        string $amount,
        string $type,
        string $reason,
        ?string $reference = null,
        ?DateTimeInterface $date = null,
    ): BankTransaction {
        if (! in_array($type, [BankTransaction::TYPE_CHARGES, BankTransaction::TYPE_MARKUP], true)) {
            throw ValidationException::withMessages(['type' => 'Type must be CHARGES or MARKUP.']);
        }

        $amount = Money::round(Money::n($amount));
        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be greater than zero.']);
        }

        return DB::transaction(function () use ($actor, $branchId, $account, $amount, $type, $reason, $reference, $date) {
            $locked = BankAccount::query()->whereKey($account->id)->lockForUpdate()->firstOrFail();

            $balanceBefore = $locked->currentBalance();
            $isCharges = ($type === BankTransaction::TYPE_CHARGES);

            if ($isCharges && Money::compare($balanceBefore, $amount) < 0) {
                throw ValidationException::withMessages([
                    'amount' => sprintf('Charges of %s exceed available balance %s.', Money::format($amount), Money::format($balanceBefore)),
                ]);
            }

            $balanceAfter = $isCharges ? Money::subtract($balanceBefore, $amount) : Money::add($balanceBefore, $amount);
            $refNumber = $reference ?: (($isCharges ? 'CHG-' : 'MKP-') . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));
            $txnDate = $date ? Carbon::instance($date) : now();

            $transaction = BankTransaction::create([
                'branch_id' => $branchId,
                'bank_account_id' => $locked->id,
                'type' => $type,
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceAfter,
                'reference_number' => $refNumber,
                'transaction_date' => $txnDate,
                'description' => $reason,
                'performed_by' => $actor->id,
                'status' => BankTransaction::STATUS_COMPLETED,
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: strtolower($type),
                module: 'banking',
                referenceType: BankTransaction::class,
                referenceId: $transaction->id,
                newData: [
                    'account' => $locked->account_title,
                    'type' => $type,
                    'amount' => $amount,
                    'reason' => $reason,
                ],
            );

            return $transaction;
        });
    }

    /**
     * Bank Book statement with running balance calculation.
     */
    public function getBankBook(BankAccount $account, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? today()->startOfMonth()->toDateString();
        $endDate = $endDate ?? today()->toDateString();

        $openingBalance = Money::n($account->opening_balance);

        // Transactions before startDate
        $priorCredits = Money::n(BankTransaction::query()
            ->where('bank_account_id', $account->id)
            ->where('status', BankTransaction::STATUS_COMPLETED)
            ->whereDate('transaction_date', '<', $startDate)
            ->whereIn('type', [
                BankTransaction::TYPE_DEPOSIT,
                BankTransaction::TYPE_TRANSFER_IN,
                BankTransaction::TYPE_MARKUP,
                BankTransaction::TYPE_CHEQUE_DEPOSIT,
            ])->sum('amount'));

        $priorDebits = Money::n(BankTransaction::query()
            ->where('bank_account_id', $account->id)
            ->where('status', BankTransaction::STATUS_COMPLETED)
            ->whereDate('transaction_date', '<', $startDate)
            ->whereIn('type', [
                BankTransaction::TYPE_WITHDRAWAL,
                BankTransaction::TYPE_TRANSFER_OUT,
                BankTransaction::TYPE_CHARGES,
                BankTransaction::TYPE_CHEQUE_BOUNCE,
            ])->sum('amount'));

        $periodOpening = Money::subtract(Money::add($openingBalance, $priorCredits), $priorDebits);

        // Transactions in period
        $transactions = BankTransaction::query()
            ->with(['performer', 'relatedAccount.bank'])
            ->where('bank_account_id', $account->id)
            ->where('status', BankTransaction::STATUS_COMPLETED)
            ->whereBetween(DB::raw('DATE(transaction_date)'), [$startDate, $endDate])
            ->orderBy('transaction_date')
            ->orderBy('id')
            ->get();

        $running = $periodOpening;
        $items = [];
        $totalCredits = '0.00';
        $totalDebits = '0.00';

        foreach ($transactions as $txn) {
            $isCredit = $txn->isCredit();
            if ($isCredit) {
                $running = Money::add($running, Money::n($txn->amount));
                $totalCredits = Money::add($totalCredits, Money::n($txn->amount));
                $creditAmt = $txn->amount;
                $debitAmt = '0.00';
            } else {
                $running = Money::subtract($running, Money::n($txn->amount));
                $totalDebits = Money::add($totalDebits, Money::n($txn->amount));
                $creditAmt = '0.00';
                $debitAmt = $txn->amount;
            }

            $items[] = [
                'transaction' => $txn,
                'id' => $txn->id,
                'date' => $txn->transaction_date->format('Y-m-d H:i'),
                'type' => $txn->type,
                'reference' => $txn->reference_number,
                'description' => $txn->description,
                'debit' => $debitAmt,
                'credit' => $creditAmt,
                'running_balance' => $running,
                'reconciled' => $txn->reconciled,
            ];
        }

        return [
            'account' => $account,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $periodOpening,
            'closing_balance' => $running,
            'total_credits' => $totalCredits,
            'total_debits' => $totalDebits,
            'items' => $items,
        ];
    }

    /**
     * Bank Reconciliation: reconcile bank statement with system transactions.
     */
    public function reconcileStatement(
        User $actor,
        int $branchId,
        BankAccount $account,
        string $statementDate,
        string $statementBalance,
        array $matchedTransactionIds,
        ?string $statementFilePath = null,
        ?string $notes = null,
    ): BankReconciliation {
        $statementBalance = Money::round(Money::n($statementBalance));
        $currentLedgerBalance = $account->currentBalance();
        $diff = Money::subtract($statementBalance, $currentLedgerBalance);

        return DB::transaction(function () use (
            $actor, $branchId, $account, $statementDate, $statementBalance, $currentLedgerBalance,
            $diff, $matchedTransactionIds, $statementFilePath, $notes
        ) {
            $reconciliation = BankReconciliation::create([
                'branch_id' => $branchId,
                'bank_account_id' => $account->id,
                'statement_date' => $statementDate,
                'statement_balance' => $statementBalance,
                'ledger_balance' => $currentLedgerBalance,
                'difference' => $diff,
                'reconciled_by' => $actor->id,
                'statement_file_path' => $statementFilePath,
                'status' => BankReconciliation::STATUS_COMPLETED,
                'notes' => $notes,
            ]);

            if (! empty($matchedTransactionIds)) {
                BankTransaction::query()
                    ->where('bank_account_id', $account->id)
                    ->whereIn('id', $matchedTransactionIds)
                    ->update([
                        'reconciled' => true,
                        'reconciled_at' => now(),
                        'reconciliation_id' => $reconciliation->id,
                    ]);
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'bank_reconciliation',
                module: 'banking',
                referenceType: BankReconciliation::class,
                referenceId: $reconciliation->id,
                newData: [
                    'account' => $account->account_title,
                    'statement_balance' => $statementBalance,
                    'ledger_balance' => $currentLedgerBalance,
                    'difference' => $diff,
                    'matched_count' => count($matchedTransactionIds),
                ],
            );

            return $reconciliation;
        });
    }

    /**
     * Import statement CSV from Pakistani banks (HBL, Meezan, Alfalah, UBL, etc.).
     * Returns parsed rows: [['date' => ..., 'description' => ..., 'debit' => ..., 'credit' => ..., 'balance' => ..., 'ref' => ...]]
     */
    public function importStatementCsv(string|UploadedFile $file): array
    {
        $path = is_string($file) ? $file : $file->getRealPath();
        if (! file_exists($path)) {
            throw ValidationException::withMessages(['file' => 'CSV statement file not found.']);
        }

        $rows = [];
        $handle = fopen($path, 'r');
        if (! $handle) {
            throw ValidationException::withMessages(['file' => 'Could not open CSV file.']);
        }

        $header = null;
        while (($data = fgetcsv($handle, 1000, ',')) !== false) {
            if (! $header) {
                // Normalise header keys
                $header = array_map(fn ($col) => strtolower(trim(preg_replace('/[^a-zA-Z0-9]/', '', $col))), $data);
                continue;
            }

            if (empty(array_filter($data))) {
                continue;
            }

            $row = [];
            foreach ($header as $i => $key) {
                $row[$key] = trim($data[$i] ?? '');
            }

            // Extract date
            $date = $row['date'] ?? $row['valuedate'] ?? $row['txndate'] ?? date('Y-m-d');
            try {
                $parsedDate = Carbon::parse($date)->format('Y-m-d');
            } catch (Throwable) {
                $parsedDate = date('Y-m-d');
            }

            // Extract description & reference
            $description = $row['description'] ?? $row['particulars'] ?? $row['narration'] ?? $row['details'] ?? 'Bank transaction';
            $ref = $row['ref'] ?? $row['referenceno'] ?? $row['chequeno'] ?? null;

            // Extract debit & credit
            $debitRaw = preg_replace('/[^0-9.]/', '', $row['debit'] ?? $row['withdrawal'] ?? '0');
            $creditRaw = preg_replace('/[^0-9.]/', '', $row['credit'] ?? $row['deposit'] ?? '0');
            $balanceRaw = preg_replace('/[^0-9.]/', '', $row['balance'] ?? '0');

            $rows[] = [
                'date' => $parsedDate,
                'description' => $description,
                'reference' => $ref,
                'debit' => Money::round($debitRaw ?: '0.00'),
                'credit' => Money::round($creditRaw ?: '0.00'),
                'balance' => Money::round($balanceRaw ?: '0.00'),
            ];
        }

        fclose($handle);

        return $rows;
    }
}
