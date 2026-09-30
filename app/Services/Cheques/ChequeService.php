<?php

namespace App\Services\Cheques;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Notification;
use App\Models\Supplier;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Banking\BankingService;
use App\Services\Customer\CustomerLedgerService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChequeService
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly CustomerLedgerService $customerLedger,
        private readonly BankingService $banking,
    ) {
    }

    /**
     * Record a cheque received from a customer.
     */
    public function receiveCustomerCheque(
        User $actor,
        int $branchId,
        Customer $customer,
        string $amount,
        string $chequeNumber,
        string $bankName,
        string $chequeDate,
        ?string $dueDate = null,
        ?string $payeeName = null,
        ?string $notes = null,
        ?string $imagePath = null,
    ): Cheque {
        $amount = Money::round(Money::n($amount));
        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages(['amount' => 'Cheque amount must be greater than zero.']);
        }

        $dueDate = $dueDate ?: $chequeDate;
        $isPdc = Carbon::parse($dueDate)->isFuture();

        return DB::transaction(function () use (
            $actor, $branchId, $customer, $amount, $chequeNumber,
            $bankName, $chequeDate, $dueDate, $isPdc, $payeeName, $notes, $imagePath
        ) {
            $cheque = Cheque::create([
                'branch_id' => $branchId,
                'type' => Cheque::TYPE_RECEIVED,
                'cheque_number' => $chequeNumber,
                'bank_name' => $bankName,
                'customer_id' => $customer->id,
                'payee_name' => $payeeName ?: $customer->name,
                'amount' => $amount,
                'cheque_date' => $chequeDate,
                'due_date' => $dueDate,
                'is_pdc' => $isPdc,
                'status' => Cheque::STATUS_RECEIVED,
                'notes' => $notes,
                'image_path' => $imagePath,
                'created_by' => $actor->id,
            ]);

            // Credit the customer ledger (they paid with cheque)
            $this->customerLedger->recordCredit(
                customer: $customer,
                amount: $amount,
                description: sprintf('Cheque Received #%s (%s)%s', $chequeNumber, $bankName, $isPdc ? ' [PDC]' : ''),
                referenceType: Cheque::class,
                referenceId: $cheque->id,
                date: $chequeDate,
                branchId: $branchId,
            );

            $this->audit->record(
                userId: $actor->id,
                action: 'cheque_received',
                module: 'cheques',
                referenceType: Cheque::class,
                referenceId: $cheque->id,
                newData: [
                    'customer' => $customer->name,
                    'cheque_number' => $chequeNumber,
                    'bank' => $bankName,
                    'amount' => $amount,
                    'is_pdc' => $isPdc,
                ],
            );

            return $cheque;
        });
    }

    /**
     * Record a cheque issued to a supplier.
     */
    public function issueSupplierCheque(
        User $actor,
        int $branchId,
        Supplier $supplier,
        BankAccount $stationAccount,
        string $amount,
        string $chequeNumber,
        string $chequeDate,
        ?string $dueDate = null,
        ?string $payeeName = null,
        ?string $notes = null,
        ?string $imagePath = null,
    ): Cheque {
        $amount = Money::round(Money::n($amount));
        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages(['amount' => 'Cheque amount must be greater than zero.']);
        }

        $dueDate = $dueDate ?: $chequeDate;
        $isPdc = Carbon::parse($dueDate)->isFuture();

        return DB::transaction(function () use (
            $actor, $branchId, $supplier, $stationAccount, $amount, $chequeNumber,
            $chequeDate, $dueDate, $isPdc, $payeeName, $notes, $imagePath
        ) {
            $cheque = Cheque::create([
                'branch_id' => $branchId,
                'type' => Cheque::TYPE_ISSUED,
                'cheque_number' => $chequeNumber,
                'bank_name' => $stationAccount->bank?->name ?? 'Bank',
                'bank_account_id' => $stationAccount->id,
                'supplier_id' => $supplier->id,
                'payee_name' => $payeeName ?: $supplier->name,
                'amount' => $amount,
                'cheque_date' => $chequeDate,
                'due_date' => $dueDate,
                'is_pdc' => $isPdc,
                'status' => Cheque::STATUS_RECEIVED, // Created/Issued
                'notes' => $notes,
                'image_path' => $imagePath,
                'created_by' => $actor->id,
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: 'cheque_issued',
                module: 'cheques',
                referenceType: Cheque::class,
                referenceId: $cheque->id,
                newData: [
                    'supplier' => $supplier->name,
                    'cheque_number' => $chequeNumber,
                    'amount' => $amount,
                    'is_pdc' => $isPdc,
                ],
            );

            return $cheque;
        });
    }

    /**
     * Mark cheque as Deposited into bank account.
     */
    public function depositCheque(User $actor, Cheque $cheque, BankAccount $account, ?string $depositDate = null): Cheque
    {
        if ($cheque->status !== Cheque::STATUS_RECEIVED) {
            throw ValidationException::withMessages(['status' => "Cannot deposit cheque in '{$cheque->status}' status."]);
        }

        return DB::transaction(function () use ($actor, $cheque, $account, $depositDate) {
            $cheque->update([
                'bank_account_id' => $account->id,
                'status' => Cheque::STATUS_DEPOSITED,
                'deposit_date' => $depositDate ?: today()->toDateString(),
                'actioned_by' => $actor->id,
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: 'cheque_deposited',
                module: 'cheques',
                referenceType: Cheque::class,
                referenceId: $cheque->id,
                newData: ['bank_account' => $account->account_title, 'status' => Cheque::STATUS_DEPOSITED],
            );

            return $cheque->fresh();
        });
    }

    /**
     * Clear cheque.
     */
    public function clearCheque(User $actor, Cheque $cheque, ?string $clearedDate = null): Cheque
    {
        if (! in_array($cheque->status, [Cheque::STATUS_RECEIVED, Cheque::STATUS_DEPOSITED], true)) {
            throw ValidationException::withMessages(['status' => "Cannot clear cheque in '{$cheque->status}' status."]);
        }

        return DB::transaction(function () use ($actor, $cheque, $clearedDate) {
            $date = $clearedDate ?: today()->toDateString();

            // If bank account is associated, post to bank transaction
            if ($cheque->bank_account_id) {
                $account = BankAccount::findOrFail($cheque->bank_account_id);
                $isReceived = ($cheque->type === Cheque::TYPE_RECEIVED);

                if ($isReceived) {
                    // Money entered station bank account
                    $before = $account->currentBalance();
                    $after = Money::add($before, $cheque->amount);
                    BankTransaction::create([
                        'branch_id' => $cheque->branch_id,
                        'bank_account_id' => $account->id,
                        'type' => BankTransaction::TYPE_CHEQUE_DEPOSIT,
                        'amount' => $cheque->amount,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'reference_number' => 'CHQ-CLR-' . $cheque->cheque_number,
                        'transaction_date' => Carbon::parse($date),
                        'description' => "Cheque cleared #{$cheque->cheque_number} from {$cheque->partyName()}",
                        'performed_by' => $actor->id,
                        'status' => BankTransaction::STATUS_COMPLETED,
                    ]);
                } else {
                    // Issued cheque cleared: money left station bank account
                    $before = $account->currentBalance();
                    $after = Money::subtract($before, $cheque->amount);
                    BankTransaction::create([
                        'branch_id' => $cheque->branch_id,
                        'bank_account_id' => $account->id,
                        'type' => BankTransaction::TYPE_WITHDRAWAL,
                        'amount' => $cheque->amount,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'reference_number' => 'CHQ-PAY-' . $cheque->cheque_number,
                        'transaction_date' => Carbon::parse($date),
                        'description' => "Issued Cheque cleared #{$cheque->cheque_number} to {$cheque->partyName()}",
                        'performed_by' => $actor->id,
                        'status' => BankTransaction::STATUS_COMPLETED,
                    ]);
                }
            }

            $cheque->update([
                'status' => Cheque::STATUS_CLEARED,
                'cleared_date' => $date,
                'actioned_by' => $actor->id,
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: 'cheque_cleared',
                module: 'cheques',
                referenceType: Cheque::class,
                referenceId: $cheque->id,
                newData: ['status' => Cheque::STATUS_CLEARED, 'cleared_date' => $date],
            );

            return $cheque->fresh();
        });
    }

    /**
     * Automatic bounce handling:
     * - Reverses customer ledger payment
     * - Applies bank charges
     * - Creates notification for manager/owner
     */
    public function bounceCheque(
        User $actor,
        Cheque $cheque,
        string $bounceReason,
        string $bankCharges = '0.00',
        ?string $bouncedDate = null,
    ): Cheque {
        if ($cheque->status === Cheque::STATUS_BOUNCED) {
            throw ValidationException::withMessages(['status' => 'This cheque has already been marked as bounced.']);
        }

        $bankCharges = Money::round(Money::n($bankCharges));
        $date = $bouncedDate ?: today()->toDateString();

        return DB::transaction(function () use ($actor, $cheque, $bounceReason, $bankCharges, $date) {
            $lockedCheque = Cheque::query()->whereKey($cheque->id)->lockForUpdate()->firstOrFail();

            // 1. If cheque was received from a customer: reverse payment by DEBITING customer ledger
            if ($lockedCheque->type === Cheque::TYPE_RECEIVED && $lockedCheque->customer_id) {
                $customer = Customer::findOrFail($lockedCheque->customer_id);

                // Debit customer ledger for the reversed payment
                $this->customerLedger->recordDebit(
                    customer: $customer,
                    amount: $lockedCheque->amount,
                    description: sprintf('BOUNCE REVERSAL: Cheque #%s (%s) - Reason: %s', $lockedCheque->cheque_number, $lockedCheque->bank_name, $bounceReason),
                    referenceType: Cheque::class,
                    referenceId: $lockedCheque->id,
                    date: $date,
                    branchId: $lockedCheque->branch_id,
                );

                // If bank charges apply, debit customer ledger for charges recovery
                if (Money::compare($bankCharges, '0.00') > 0) {
                    $this->customerLedger->recordDebit(
                        customer: $customer,
                        amount: $bankCharges,
                        description: sprintf('Bank Bounce Penalty on Cheque #%s', $lockedCheque->cheque_number),
                        referenceType: Cheque::class,
                        referenceId: $lockedCheque->id,
                        date: $date,
                        branchId: $lockedCheque->branch_id,
                    );
                }
            }

            // 2. If bank account was charged by the bank, record bank transaction CHARGES
            if ($lockedCheque->bank_account_id && Money::compare($bankCharges, '0.00') > 0) {
                $account = BankAccount::findOrFail($lockedCheque->bank_account_id);
                $before = $account->currentBalance();
                $after = Money::subtract($before, $bankCharges);

                BankTransaction::create([
                    'branch_id' => $lockedCheque->branch_id,
                    'bank_account_id' => $account->id,
                    'type' => BankTransaction::TYPE_CHARGES,
                    'amount' => $bankCharges,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'reference_number' => 'CHQ-BNC-' . $lockedCheque->cheque_number,
                    'transaction_date' => Carbon::parse($date),
                    'description' => "Bank bounce charges for Cheque #{$lockedCheque->cheque_number}",
                    'performed_by' => $actor->id,
                    'status' => BankTransaction::STATUS_COMPLETED,
                ]);
            }

            // 3. Update cheque status
            $lockedCheque->update([
                'status' => Cheque::STATUS_BOUNCED,
                'bounced_date' => $date,
                'bounce_reason' => $bounceReason,
                'bank_charges' => $bankCharges,
                'actioned_by' => $actor->id,
            ]);

            // 4. Create Notification for admins/managers
            Notification::create([
                'user_id' => $actor->id,
                'type' => 'CHEQUE_BOUNCED',
                'title' => sprintf('🚨 Cheque Bounced: #%s (%s)', $lockedCheque->cheque_number, PakistaniCurrency::format($lockedCheque->amount)),
                'message' => sprintf(
                    'Cheque #%s from %s for %s has BOUNCED at %s. Reason: %s. Bank charges applied: %s. Customer ledger reversed.',
                    $lockedCheque->cheque_number,
                    $lockedCheque->partyName(),
                    PakistaniCurrency::format($lockedCheque->amount),
                    $lockedCheque->bank_name,
                    $bounceReason,
                    PakistaniCurrency::format($bankCharges)
                ),
                'level' => Notification::LEVEL_CRITICAL,
                'module' => 'cheques',
                'reference_type' => Cheque::class,
                'reference_id' => $lockedCheque->id,
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: 'cheque_bounced',
                module: 'cheques',
                referenceType: Cheque::class,
                referenceId: $lockedCheque->id,
                newData: [
                    'status' => Cheque::STATUS_BOUNCED,
                    'bounce_reason' => $bounceReason,
                    'bank_charges' => $bankCharges,
                ],
            );

            return $lockedCheque->fresh();
        });
    }

    /**
     * Cancel a cheque.
     */
    public function cancelCheque(User $actor, Cheque $cheque, string $reason): Cheque
    {
        return DB::transaction(function () use ($actor, $cheque, $reason) {
            $cheque->update([
                'status' => Cheque::STATUS_CANCELLED,
                'notes' => ($cheque->notes ? $cheque->notes . "\n" : '') . "Cancelled: {$reason}",
                'actioned_by' => $actor->id,
            ]);

            // If it was a received customer cheque, reverse customer ledger
            if ($cheque->type === Cheque::TYPE_RECEIVED && $cheque->customer_id) {
                $customer = Customer::findOrFail($cheque->customer_id);
                $this->customerLedger->recordDebit(
                    customer: $customer,
                    amount: $cheque->amount,
                    description: "Cheque Cancelled #{$cheque->cheque_number}: {$reason}",
                    referenceType: Cheque::class,
                    referenceId: $cheque->id,
                    branchId: $cheque->branch_id,
                );
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'cheque_cancelled',
                module: 'cheques',
                referenceType: Cheque::class,
                referenceId: $cheque->id,
                newData: ['status' => Cheque::STATUS_CANCELLED, 'reason' => $reason],
            );

            return $cheque->fresh();
        });
    }

    /**
     * PDC (Post-Dated Cheque) Calendar items for branch and month.
     */
    public function getPdcCalendar(int $branchId, ?int $month = null, ?int $year = null): array
    {
        $year = $year ?: (int) date('Y');
        $month = $month ?: (int) date('m');

        $start = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $end = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $cheques = Cheque::query()
            ->with(['customer', 'supplier', 'bankAccount.bank'])
            ->where('branch_id', $branchId)
            ->whereBetween('due_date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('due_date')
            ->get();

        $byDate = [];
        $totalAmount = '0.00';

        foreach ($cheques as $chq) {
            $d = $chq->due_date->toDateString();
            $byDate[$d][] = $chq;
            $totalAmount = Money::add($totalAmount, Money::n($chq->amount));
        }

        return [
            'year' => $year,
            'month' => $month,
            'total_amount' => $totalAmount,
            'cheques' => $cheques,
            'grouped_by_date' => $byDate,
        ];
    }
}
