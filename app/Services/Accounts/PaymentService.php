<?php

namespace App\Services\Accounts;

use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly AuditLogService $audit,
        private readonly AccountingService $accounting,
    ) {
    }

    /**
     * Record a payment received from a credit (Udhaar) customer.
     */
    public function receiveCustomerPayment(
        int $branchId,
        int $customerId,
        string|float|int $amount,
        string $paymentMethod = CustomerPayment::METHOD_CASH,
        ?string $date = null,
        ?int $bankAccountId = null,
        ?string $chequeNumber = null,
        ?string $chequeDate = null,
        ?string $notes = null,
        ?User $actor = null,
    ): CustomerPayment {
        $date = Carbon::parse($date ?? now())->format('Y-m-d');

        if ($this->accounting->isDateLocked($branchId, $date)) {
            throw ValidationException::withMessages([
                'payment_date' => "Date {$date} is locked by daily closing.",
            ]);
        }

        $amt = Money::round(Money::n($amount));
        if (! Money::isPositive($amt)) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $actorId = $actor?->id ?? auth()->id();

        return DB::transaction(function () use (
            $branchId, $customerId, $amt, $paymentMethod, $date, $bankAccountId, $chequeNumber, $chequeDate, $notes, $actorId
        ) {
            $year = Carbon::parse($date)->format('Y');
            $payNum = $this->sequences->next(
                branchId: $branchId,
                prefix: "PAY-CUST-{$year}-",
                padding: 6
            );

            $payment = CustomerPayment::create([
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'payment_number' => $payNum,
                'payment_date' => $date,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'cheque_number' => $chequeNumber,
                'cheque_date' => $chequeDate,
                'cheque_status' => $paymentMethod === CustomerPayment::METHOD_CHEQUE ? 'PENDING' : null,
                'amount' => $amt,
                'notes' => $notes,
                'created_by' => $actorId,
            ]);

            // Update customer balance (credit payment reduces receivable balance)
            $customer = Customer::whereKey($customerId)->lockForUpdate()->firstOrFail();
            $newBalance = Money::subtract(Money::n($customer->current_balance), $amt);
            $customer->update(['current_balance' => $newBalance]);

            CustomerLedger::create([
                'branch_id' => $branchId,
                'customer_id' => $customerId,
                'date' => $date,
                'reference_type' => CustomerPayment::class,
                'reference_id' => $payment->id,
                'description' => "Payment received #{$payNum} ({$paymentMethod})",
                'debit' => '0.00',
                'credit' => $amt,
                'running_balance' => $newBalance,
            ]);

            // Post GL entry
            $this->accounting->postCustomerPayment($payment);

            return $payment;
        });
    }

    /**
     * Record a payment made to a supplier.
     */
    public function paySupplier(
        int $branchId,
        int $supplierId,
        string|float|int $amount,
        string $paymentMethod = SupplierPayment::METHOD_CASH,
        ?string $date = null,
        ?int $bankAccountId = null,
        ?string $chequeNumber = null,
        ?string $chequeDate = null,
        ?string $notes = null,
        ?User $actor = null,
    ): SupplierPayment {
        $date = Carbon::parse($date ?? now())->format('Y-m-d');

        if ($this->accounting->isDateLocked($branchId, $date)) {
            throw ValidationException::withMessages([
                'payment_date' => "Date {$date} is locked by daily closing.",
            ]);
        }

        $amt = Money::round(Money::n($amount));
        if (! Money::isPositive($amt)) {
            throw ValidationException::withMessages(['amount' => 'Payment amount must be greater than zero.']);
        }

        $actorId = $actor?->id ?? auth()->id();

        return DB::transaction(function () use (
            $branchId, $supplierId, $amt, $paymentMethod, $date, $bankAccountId, $chequeNumber, $chequeDate, $notes, $actorId
        ) {
            $year = Carbon::parse($date)->format('Y');
            $payNum = $this->sequences->next(
                branchId: $branchId,
                prefix: "PAY-SUP-{$year}-",
                padding: 6
            );

            $payment = SupplierPayment::create([
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'payment_number' => $payNum,
                'payment_date' => $date,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'cheque_number' => $chequeNumber,
                'cheque_date' => $chequeDate,
                'cheque_status' => $paymentMethod === SupplierPayment::METHOD_CHEQUE ? 'PENDING' : null,
                'amount' => $amt,
                'notes' => $notes,
                'created_by' => $actorId,
            ]);

            // Update supplier balance (debit payment reduces payable liability)
            $supplier = Supplier::whereKey($supplierId)->lockForUpdate()->firstOrFail();
            $newBalance = Money::subtract(Money::n($supplier->current_balance), $amt);
            $supplier->update(['current_balance' => $newBalance]);

            SupplierLedger::create([
                'branch_id' => $branchId,
                'supplier_id' => $supplierId,
                'date' => $date,
                'reference_type' => SupplierPayment::class,
                'reference_id' => $payment->id,
                'description' => "Payment disbursed #{$payNum} ({$paymentMethod})",
                'debit' => $amt,
                'credit' => '0.00',
                'running_balance' => $newBalance,
            ]);

            // Post GL entry
            $this->accounting->postSupplierPayment($payment);

            return $payment;
        });
    }
}
