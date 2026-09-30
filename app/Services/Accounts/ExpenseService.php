<?php

namespace App\Services\Accounts;

use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shift;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExpenseService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly AuditLogService $audit,
        private readonly AccountingService $accounting,
    ) {
    }

    /**
     * Record an operating expense.
     */
    public function createExpense(
        int $branchId,
        int $categoryId,
        string $title,
        string|float|int $amount,
        string $paymentMethod = Expense::METHOD_CASH,
        ?string $date = null,
        ?int $bankAccountId = null,
        ?int $shiftId = null,
        ?string $payee = null,
        ?string $receiptNumber = null,
        ?string $notes = null,
        ?User $actor = null,
        ?string $attachmentPath = null,
    ): Expense {
        $date = Carbon::parse($date ?? now())->format('Y-m-d');

        if ($this->accounting->isDateLocked($branchId, $date)) {
            throw ValidationException::withMessages([
                'date' => "Date {$date} is locked by daily closing.",
            ]);
        }

        $amt = Money::round(Money::n($amount));
        if (! Money::isPositive($amt)) {
            throw ValidationException::withMessages(['amount' => 'Expense amount must be greater than zero.']);
        }

        $category = ExpenseCategory::findOrFail($categoryId);
        $actorId = $actor?->id ?? auth()->id();

        return DB::transaction(function () use (
            $branchId, $category, $title, $amt, $paymentMethod, $date,
            $bankAccountId, $shiftId, $payee, $receiptNumber, $notes, $actorId, $attachmentPath
        ) {
            $year = Carbon::parse($date)->format('Y');
            $expenseNum = $this->sequences->next(
                branchId: $branchId,
                prefix: "EXP-{$year}-",
                padding: 6
            );

            $expense = Expense::create([
                'branch_id' => $branchId,
                'category_id' => $category->id,
                'expense_number' => $expenseNum,
                'date' => $date,
                'title' => $title,
                'amount' => $amt,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'shift_id' => $shiftId,
                'payee' => $payee,
                'receipt_number' => $receiptNumber,
                'attachment_path' => $attachmentPath,
                'status' => Expense::STATUS_PAID,
                'created_by' => $actorId,
                'approved_by' => $actorId,
                'notes' => $notes,
            ]);

            // Deduct from shift float if paid from shift cash
            if ($paymentMethod === Expense::METHOD_CASH && $shiftId) {
                DB::table('shift_cash')->insert([
                    'shift_id' => $shiftId,
                    'entry_type' => 'EXPENSE',
                    'amount' => $amt,
                    'notes' => "Expense: {$title} ({$expenseNum})",
                    'user_id' => $actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Deduct from bank if paid from bank
            if ($paymentMethod === Expense::METHOD_BANK_TRANSFER && $bankAccountId) {
                $bankAcc = \App\Models\BankAccount::find($bankAccountId);
                if ($bankAcc) {
                    $before = $bankAcc->currentBalance();
                    $after = Money::subtract($before, $amt);
                    \App\Models\BankTransaction::create([
                        'branch_id' => $branchId,
                        'bank_account_id' => $bankAcc->id,
                        'type' => \App\Models\BankTransaction::TYPE_WITHDRAWAL,
                        'amount' => $amt,
                        'balance_before' => $before,
                        'balance_after' => $after,
                        'reference_number' => $expenseNum,
                        'transaction_date' => Carbon::parse($date),
                        'description' => "Expense: {$title}",
                        'performed_by' => $actorId,
                        'status' => \App\Models\BankTransaction::STATUS_COMPLETED,
                    ]);
                }
            }

            // Post GL entry
            $this->accounting->postExpense($expense);

            $this->audit->record(
                userId: $actorId,
                action: 'expense_created',
                module: 'expenses',
                referenceType: Expense::class,
                referenceId: $expense->id,
                newData: [
                    'expense_number' => $expenseNum,
                    'title' => $title,
                    'amount' => $amt,
                    'category' => $category->name,
                ],
            );

            return $expense;
        });
    }
}
