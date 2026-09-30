<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\BankDeposit;
use App\Models\CustomerPayment;
use App\Models\DailyClosing;
use App\Models\Expense;
use App\Models\FinancialPeriodLock;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\Shift;
use App\Models\SupplierPayment;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Decimal;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Double-Entry Accounting Service for Pakistani Petrol Pump Operations.
 *
 * Enforces balanced debit = credit postings in database transactions,
 * locked financial periods, and standard petroleum chart of accounts.
 */
class AccountingService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Unified journal posting method.
     *
     * @param array{
     *     branch_id: int,
     *     date: string|\DateTimeInterface,
     *     narration: string,
     *     reference_type?: string|null,
     *     reference_id?: int|null,
     *     created_by?: int|null,
     *     is_closing_entry?: bool,
     *     lines: array<int, array{
     *         account_id?: int,
     *         account_code?: string,
     *         debit?: string|float|int,
     *         credit?: string|float|int,
     *         memo?: string|null
     *     }>
     * } $data
     * @throws ValidationException
     */
    public function post(array $data): JournalEntry
    {
        $branchId = (int) ($data['branch_id'] ?? 1);
        $date = $data['date'] instanceof \DateTimeInterface
            ? $data['date']->format('Y-m-d')
            : Carbon::parse($data['date'] ?? now())->format('Y-m-d');

        if ($this->isDateLocked($branchId, $date)) {
            throw ValidationException::withMessages([
                'date' => "Cannot post journal entry: Date {$date} is locked by period closing.",
            ]);
        }

        $lines = $data['lines'] ?? [];
        if (count($lines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'A journal entry must contain at least two lines.',
            ]);
        }

        $totalDebit = '0.00';
        $totalCredit = '0.00';
        $resolvedLines = [];

        foreach ($lines as $idx => $line) {
            $debit = Money::round(Money::n($line['debit'] ?? '0.00'));
            $credit = Money::round(Money::n($line['credit'] ?? '0.00'));

            if (Money::isNegative($debit) || Money::isNegative($credit)) {
                throw ValidationException::withMessages([
                    "lines.{$idx}" => 'Debits and credits cannot be negative.',
                ]);
            }

            if (! Money::isZero($debit) && ! Money::isZero($credit)) {
                throw ValidationException::withMessages([
                    "lines.{$idx}" => 'A line cannot have both a debit and a credit.',
                ]);
            }

            if (Money::isZero($debit) && Money::isZero($credit)) {
                continue; // Skip zero lines
            }

            $account = null;
            if (! empty($line['account_id'])) {
                $account = Account::find($line['account_id']);
            } elseif (! empty($line['account_code'])) {
                $account = Account::where('code', $line['account_code'])->first();
            }

            if (! $account) {
                throw ValidationException::withMessages([
                    "lines.{$idx}" => 'Valid account id or account code is required.',
                ]);
            }

            $totalDebit = Money::add($totalDebit, $debit);
            $totalCredit = Money::add($totalCredit, $credit);

            $resolvedLines[] = [
                'account' => $account,
                'debit' => $debit,
                'credit' => $credit,
                'memo' => $line['memo'] ?? null,
            ];
        }

        if (count($resolvedLines) < 2) {
            throw ValidationException::withMessages([
                'lines' => 'Journal entry requires at least two non-zero lines.',
            ]);
        }

        if (Money::compare($totalDebit, $totalCredit) !== 0) {
            throw ValidationException::withMessages([
                'lines' => "Journal entry is not balanced. Total Debits: Rs. {$totalDebit}, Total Credits: Rs. {$totalCredit}.",
            ]);
        }

        return DB::transaction(function () use ($branchId, $date, $data, $resolvedLines) {
            $year = Carbon::parse($date)->format('Y');
            $entryNumber = $this->sequences->next(
                branchId: $branchId,
                prefix: "JE-{$year}-",
                padding: 6
            );

            $entry = JournalEntry::create([
                'branch_id' => $branchId,
                'entry_number' => $entryNumber,
                'date' => $date,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'narration' => $data['narration'] ?? 'General Journal Entry',
                'status' => JournalEntry::STATUS_POSTED,
                'is_closing_entry' => (bool) ($data['is_closing_entry'] ?? false),
                'created_by' => $data['created_by'] ?? auth()->id(),
                'posted_at' => now(),
            ]);

            foreach ($resolvedLines as $line) {
                /** @var Account $account */
                $account = $line['account'];
                $debit = $line['debit'];
                $credit = $line['credit'];

                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $account->id,
                    'debit' => $debit,
                    'credit' => $credit,
                    'memo' => $line['memo'],
                ]);

                // Update account current balance
                $lockedAccount = Account::whereKey($account->id)->lockForUpdate()->firstOrFail();
                $cur = Money::n($lockedAccount->current_balance);

                if ($lockedAccount->normal_balance === Account::BALANCE_DEBIT) {
                    $newBalance = Money::subtract(Money::add($cur, $debit), $credit);
                } else {
                    $newBalance = Money::add(Money::subtract($cur, $debit), $credit);
                }

                $lockedAccount->update(['current_balance' => $newBalance]);
            }

            return $entry->load(['lines.account']);
        });
    }

    /**
     * Check if a date is locked against postings or edits.
     */
    public function isDateLocked(int $branchId, string|\DateTimeInterface $date): bool
    {
        $d = $date instanceof \DateTimeInterface ? $date->format('Y-m-d') : Carbon::parse($date)->format('Y-m-d');

        $isPeriodLocked = FinancialPeriodLock::query()
            ->where('branch_id', $branchId)
            ->where('is_locked', true)
            ->where('locked_until_date', '>=', $d)
            ->exists();

        if ($isPeriodLocked) {
            return true;
        }

        // Also check if daily closing for that date is marked LOCKED
        return DailyClosing::query()
            ->where('branch_id', $branchId)
            ->where('closing_date', $d)
            ->where('status', DailyClosing::STATUS_LOCKED)
            ->exists();
    }

    /**
     * Lock financial period up to a date.
     */
    public function lockPeriod(int $branchId, string $untilDate, ?int $userId = null, ?string $reason = null): FinancialPeriodLock
    {
        return FinancialPeriodLock::updateOrCreate(
            ['branch_id' => $branchId, 'locked_until_date' => $untilDate],
            [
                'reason' => $reason ?? 'Period Closing Date Lock',
                'locked_by' => $userId ?? auth()->id(),
                'is_locked' => true,
            ]
        );
    }

    /**
     * Post a Forecourt Sale to the General Ledger.
     */
    public function postSale(Sale $sale): JournalEntry
    {
        $lines = [];

        // Debit side: Payment methods
        foreach ($sale->payments as $payment) {
            $amount = Money::round($payment->amount);
            if (Money::isZero($amount)) {
                continue;
            }

            $method = strtoupper($payment->method);
            $accountCode = match ($method) {
                'CREDIT' => Account::CODE_ACCOUNTS_RECEIVABLE,
                'CARD', 'FLEET_CARD', 'OMC_CARD' => Account::CODE_OMC_CARD_RECEIVABLE,
                'BANK', 'BANK_TRANSFER' => Account::CODE_BANK_ACCOUNTS,
                default => Account::CODE_CASH_IN_HAND,
            };

            $lines[] = [
                'account_code' => $accountCode,
                'debit' => $amount,
                'credit' => '0.00',
                'memo' => "Payment via {$method} for invoice {$sale->invoice_number}",
            ];
        }

        // Credit side: Sales revenue and tax
        $tax = Money::round($sale->tax ?? '0.00');
        $subtotal = Money::round($sale->subtotal ?? $sale->total);

        // Net revenue before tax
        $netRevenue = Money::round($sale->total);
        if (! Money::isZero($tax)) {
            $netRevenue = Money::subtract($sale->total, $tax);
            $lines[] = [
                'account_code' => Account::CODE_SALES_TAX_PAYABLE,
                'debit' => '0.00',
                'credit' => $tax,
                'memo' => "Sales tax on invoice {$sale->invoice_number}",
            ];
        }

        $lines[] = [
            'account_code' => Account::CODE_FUEL_SALES_REVENUE,
            'debit' => '0.00',
            'credit' => $netRevenue,
            'memo' => "Fuel sales revenue from invoice {$sale->invoice_number}",
        ];

        return $this->post([
            'branch_id' => $sale->branch_id,
            'date' => $sale->created_at ?? now(),
            'narration' => "Forecourt Sale Invoice #{$sale->invoice_number}",
            'reference_type' => Sale::class,
            'reference_id' => $sale->id,
            'created_by' => $sale->user_id ?? auth()->id(),
            'lines' => $lines,
        ]);
    }

    /**
     * Post a Fuel/Lube Purchase to the General Ledger.
     */
    public function postPurchase(Purchase $purchase): JournalEntry
    {
        $amount = Money::round($purchase->total_amount);

        $lines = [
            [
                'account_code' => Account::CODE_FUEL_INVENTORY,
                'debit' => $amount,
                'credit' => '0.00',
                'memo' => "Fuel inventory received: {$purchase->volume_received} L",
            ],
            [
                'account_code' => Account::CODE_ACCOUNTS_PAYABLE,
                'debit' => '0.00',
                'credit' => $amount,
                'memo' => "Payable to supplier #{$purchase->supplier_id} for purchase #{$purchase->purchase_number}",
            ],
        ];

        return $this->post([
            'branch_id' => $purchase->branch_id,
            'date' => $purchase->purchase_date,
            'narration' => "Purchase #{$purchase->purchase_number} from {$purchase->supplier?->name}",
            'reference_type' => Purchase::class,
            'reference_id' => $purchase->id,
            'created_by' => $purchase->created_by ?? auth()->id(),
            'lines' => $lines,
        ]);
    }

    /**
     * Post a Customer Udhaar Payment received.
     */
    public function postCustomerPayment(CustomerPayment $payment): JournalEntry
    {
        $amount = Money::round($payment->amount);
        $debitAccount = $payment->payment_method === CustomerPayment::METHOD_CASH
            ? Account::CODE_CASH_IN_HAND
            : Account::CODE_BANK_ACCOUNTS;

        $lines = [
            [
                'account_code' => $debitAccount,
                'debit' => $amount,
                'credit' => '0.00',
                'memo' => "Receipt via {$payment->payment_method}",
            ],
            [
                'account_code' => Account::CODE_ACCOUNTS_RECEIVABLE,
                'debit' => '0.00',
                'credit' => $amount,
                'memo' => "Udhaar clearance for customer #{$payment->customer_id}",
            ],
        ];

        return $this->post([
            'branch_id' => $payment->branch_id,
            'date' => $payment->payment_date,
            'narration' => "Customer payment #{$payment->payment_number} from {$payment->customer?->name}",
            'reference_type' => CustomerPayment::class,
            'reference_id' => $payment->id,
            'created_by' => $payment->created_by ?? auth()->id(),
            'lines' => $lines,
        ]);
    }

    /**
     * Post a Supplier Payment.
     */
    public function postSupplierPayment(SupplierPayment $payment): JournalEntry
    {
        $amount = Money::round($payment->amount);
        $creditAccount = $payment->payment_method === SupplierPayment::METHOD_CASH
            ? Account::CODE_CASH_IN_HAND
            : Account::CODE_BANK_ACCOUNTS;

        $lines = [
            [
                'account_code' => Account::CODE_ACCOUNTS_PAYABLE,
                'debit' => $amount,
                'credit' => '0.00',
                'memo' => "Payment to supplier #{$payment->supplier_id}",
            ],
            [
                'account_code' => $creditAccount,
                'debit' => '0.00',
                'credit' => $amount,
                'memo' => "Disbursement via {$payment->payment_method}",
            ],
        ];

        return $this->post([
            'branch_id' => $payment->branch_id,
            'date' => $payment->payment_date,
            'narration' => "Supplier payment #{$payment->payment_number} to {$payment->supplier?->name}",
            'reference_type' => SupplierPayment::class,
            'reference_id' => $payment->id,
            'created_by' => $payment->created_by ?? auth()->id(),
            'lines' => $lines,
        ]);
    }

    /**
     * Post an Expense.
     */
    public function postExpense(Expense $expense): JournalEntry
    {
        $amount = Money::round($expense->amount);
        $creditAccount = $expense->payment_method === Expense::METHOD_CASH
            ? Account::CODE_CASH_IN_HAND
            : Account::CODE_BANK_ACCOUNTS;

        $lines = [
            [
                'account_code' => Account::CODE_OPERATING_EXPENSES,
                'debit' => $amount,
                'credit' => '0.00',
                'memo' => "Expense: {$expense->title}",
            ],
            [
                'account_code' => $creditAccount,
                'debit' => '0.00',
                'credit' => $amount,
                'memo' => "Paid via {$expense->payment_method}",
            ],
        ];

        return $this->post([
            'branch_id' => $expense->branch_id,
            'date' => $expense->date,
            'narration' => "Expense #{$expense->expense_number}: {$expense->title}",
            'reference_type' => Expense::class,
            'reference_id' => $expense->id,
            'created_by' => $expense->created_by ?? auth()->id(),
            'lines' => $lines,
        ]);
    }

    /**
     * Post a Bank Deposit from Cash in Hand.
     */
    public function postBankDeposit(BankDeposit $deposit): JournalEntry
    {
        $amount = Money::round($deposit->amount);

        $lines = [
            [
                'account_code' => Account::CODE_BANK_ACCOUNTS,
                'debit' => $amount,
                'credit' => '0.00',
                'memo' => "Deposit into bank account #{$deposit->bank_account_id}",
            ],
            [
                'account_code' => Account::CODE_CASH_IN_HAND,
                'debit' => '0.00',
                'credit' => $amount,
                'memo' => "Cash handover from till/safe to bank",
            ],
        ];

        return $this->post([
            'branch_id' => $deposit->branch_id,
            'date' => $deposit->deposited_at ?? now(),
            'narration' => "Bank Deposit #{$deposit->deposit_number}",
            'reference_type' => BankDeposit::class,
            'reference_id' => $deposit->id,
            'created_by' => $deposit->created_by ?? auth()->id(),
            'lines' => $lines,
        ]);
    }

    /**
     * Post Cash Variance (Short / Over) on shift closing.
     */
    public function postCashVariance(Shift $shift, string $variance): ?JournalEntry
    {
        $v = Money::round($variance);
        if (Money::isZero($v)) {
            return null;
        }

        $lines = [];
        if (Money::isPositive($v)) {
            // Cash Over (+): Debit Cash, Credit Cash Short/Over
            $lines = [
                [
                    'account_code' => Account::CODE_CASH_IN_HAND,
                    'debit' => $v,
                    'credit' => '0.00',
                    'memo' => "Cash surplus in shift {$shift->shift_number}",
                ],
                [
                    'account_code' => Account::CODE_CASH_SHORT_OVER,
                    'debit' => '0.00',
                    'credit' => $v,
                    'memo' => "Cash over credit",
                ],
            ];
        } else {
            // Cash Short (-): Debit Cash Short/Over, Credit Cash
            $shortAmount = Money::abs($v);
            $lines = [
                [
                    'account_code' => Account::CODE_CASH_SHORT_OVER,
                    'debit' => $shortAmount,
                    'credit' => '0.00',
                    'memo' => "Cash shortfall in shift {$shift->shift_number}",
                ],
                [
                    'account_code' => Account::CODE_CASH_IN_HAND,
                    'debit' => '0.00',
                    'credit' => $shortAmount,
                    'memo' => "Cash short deduction from till",
                ],
            ];
        }

        return $this->post([
            'branch_id' => $shift->branch_id,
            'date' => $shift->end_time ?? now(),
            'narration' => "Cash variance for Shift #{$shift->shift_number}",
            'reference_type' => Shift::class,
            'reference_id' => $shift->id,
            'created_by' => auth()->id(),
            'lines' => $lines,
        ]);
    }

    /**
     * Void a journal entry by issuing a reversing entry.
     */
    public function voidEntry(JournalEntry $entry, string $reason, ?User $actor = null): JournalEntry
    {
        if ($entry->isVoid()) {
            throw ValidationException::withMessages([
                'status' => "Journal entry {$entry->entry_number} is already voided.",
            ]);
        }

        return DB::transaction(function () use ($entry, $reason, $actor) {
            $reversingLines = [];
            foreach ($entry->lines as $line) {
                // Swap debit and credit
                $reversingLines[] = [
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                    'memo' => "Reversal of line #{$line->id}: {$line->memo}",
                ];
            }

            $entry->update([
                'status' => JournalEntry::STATUS_VOID,
                'voided_at' => now(),
                'voided_by' => $actor?->id ?? auth()->id(),
                'void_reason' => $reason,
            ]);

            return $this->post([
                'branch_id' => $entry->branch_id,
                'date' => now(),
                'narration' => "Reversal of Entry {$entry->entry_number}: {$reason}",
                'reference_type' => JournalEntry::class,
                'reference_id' => $entry->id,
                'created_by' => $actor?->id ?? auth()->id(),
                'lines' => $reversingLines,
            ]);
        });
    }

    // =========================================================================
    // FINANCIAL STATEMENTS
    // =========================================================================

    /**
     * Trial Balance statement.
     *
     * @return array{
     *     as_of_date: string,
     *     accounts: array<int, array{
     *         id: int,
     *         code: string,
     *         name: string,
     *         urdu_name: string|null,
     *         type: string,
     *         normal_balance: string,
     *         debit_balance: string,
     *         credit_balance: string
     *     }>,
     *     total_debit: string,
     *     total_credit: string,
     *     is_balanced: bool
     * }
     */
    public function trialBalance(?int $branchId = null, ?string $asOfDate = null): array
    {
        $date = $asOfDate ? Carbon::parse($asOfDate)->format('Y-m-d') : now()->format('Y-m-d');

        $query = Account::query()->active()->orderBy('code');
        $accounts = $query->get();

        $rows = [];
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($accounts as $acc) {
            $lineQuery = JournalEntryLine::query()
                ->where('account_id', $acc->id)
                ->whereHas('entry', function ($q) use ($branchId, $date) {
                    $q->where('status', JournalEntry::STATUS_POSTED)
                      ->where('date', '<=', $date);
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                });

            $sumDebit = Money::round(Money::n($lineQuery->sum('debit')));
            $sumCredit = Money::round(Money::n($lineQuery->sum('credit')));

            $net = Money::subtract($sumDebit, $sumCredit);

            $debitBalance = '0.00';
            $creditBalance = '0.00';

            if (Money::compare($net, '0.00') > 0) {
                $debitBalance = $net;
            } elseif (Money::compare($net, '0.00') < 0) {
                $creditBalance = Money::abs($net);
            }

            if (! Money::isZero($debitBalance) || ! Money::isZero($creditBalance)) {
                $rows[] = [
                    'id' => $acc->id,
                    'code' => $acc->code,
                    'name' => $acc->name,
                    'urdu_name' => $acc->urdu_name,
                    'type' => $acc->type,
                    'normal_balance' => $acc->normal_balance,
                    'debit_balance' => $debitBalance,
                    'credit_balance' => $creditBalance,
                ];

                $totalDebit = Money::add($totalDebit, $debitBalance);
                $totalCredit = Money::add($totalCredit, $creditBalance);
            }
        }

        return [
            'as_of_date' => $date,
            'accounts' => $rows,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'is_balanced' => Money::compare($totalDebit, $totalCredit) === 0,
        ];
    }

    /**
     * Profit & Loss statement (Gross Margin vs Net Profit).
     */
    public function profitAndLoss(?int $branchId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $from = $fromDate ? Carbon::parse($fromDate)->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
        $to = $toDate ? Carbon::parse($toDate)->format('Y-m-d') : now()->format('Y-m-d');

        $pnlAccounts = Account::query()
            ->whereIn('type', [Account::TYPE_REVENUE, Account::TYPE_EXPENSE])
            ->active()
            ->orderBy('code')
            ->get();

        $revenueItems = [];
        $totalRevenue = '0.00';

        $cogsItems = [];
        $totalCogs = '0.00';

        $expenseItems = [];
        $totalExpenses = '0.00';

        $otherItems = [];
        $totalOther = '0.00';

        foreach ($pnlAccounts as $acc) {
            $lineQuery = JournalEntryLine::query()
                ->where('account_id', $acc->id)
                ->whereHas('entry', function ($q) use ($branchId, $from, $to) {
                    $q->where('status', JournalEntry::STATUS_POSTED)
                      ->whereBetween('date', [$from, $to]);
                    if ($branchId) {
                        $q->where('branch_id', $branchId);
                    }
                });

            $sumDebit = Money::round(Money::n($lineQuery->sum('debit')));
            $sumCredit = Money::round(Money::n($lineQuery->sum('credit')));

            if ($acc->type === Account::TYPE_REVENUE) {
                // Revenue net = Credit - Debit
                $net = Money::subtract($sumCredit, $sumDebit);
                if (! Money::isZero($net)) {
                    $revenueItems[] = ['account' => $acc, 'amount' => $net];
                    $totalRevenue = Money::add($totalRevenue, $net);
                }
            } else {
                // Expense net = Debit - Credit
                $net = Money::subtract($sumDebit, $sumCredit);
                if (! Money::isZero($net)) {
                    if (in_array($acc->code, [Account::CODE_COST_OF_FUEL_SOLD, Account::CODE_COST_OF_LUBE_SOLD], true)) {
                        $cogsItems[] = ['account' => $acc, 'amount' => $net];
                        $totalCogs = Money::add($totalCogs, $net);
                    } elseif (in_array($acc->code, [Account::CODE_INVENTORY_GAIN_LOSS, Account::CODE_CASH_SHORT_OVER], true)) {
                        $otherItems[] = ['account' => $acc, 'amount' => $net];
                        $totalOther = Money::add($totalOther, $net);
                    } else {
                        $expenseItems[] = ['account' => $acc, 'amount' => $net];
                        $totalExpenses = Money::add($totalExpenses, $net);
                    }
                }
            }
        }

        // Gross Profit = Total Revenue - Total COGS
        $grossProfit = Money::subtract($totalRevenue, $totalCogs);

        // Net Profit = Gross Profit - Operating Expenses - Other Expenses/Losses
        $netProfit = Money::subtract(Money::subtract($grossProfit, $totalExpenses), $totalOther);

        return [
            'from_date' => $from,
            'to_date' => $to,
            'revenue_items' => $revenueItems,
            'total_revenue' => $totalRevenue,
            'cogs_items' => $cogsItems,
            'total_cogs' => $totalCogs,
            'gross_profit' => $grossProfit,
            'expense_items' => $expenseItems,
            'total_expenses' => $totalExpenses,
            'other_items' => $otherItems,
            'total_other' => $totalOther,
            'net_profit' => $netProfit,
        ];
    }

    /**
     * Balance Sheet statement (Assets = Liabilities + Equity).
     */
    public function balanceSheet(?int $branchId = null, ?string $asOfDate = null): array
    {
        $date = $asOfDate ? Carbon::parse($asOfDate)->format('Y-m-d') : now()->format('Y-m-d');

        $assetAccounts = Account::query()->ofType(Account::TYPE_ASSET)->active()->orderBy('code')->get();
        $liabilityAccounts = Account::query()->ofType(Account::TYPE_LIABILITY)->active()->orderBy('code')->get();
        $equityAccounts = Account::query()->ofType(Account::TYPE_EQUITY)->active()->orderBy('code')->get();

        $calcBalances = function ($accounts, $isDebitNormal) use ($branchId, $date) {
            $items = [];
            $total = '0.00';
            foreach ($accounts as $acc) {
                $lineQuery = JournalEntryLine::query()
                    ->where('account_id', $acc->id)
                    ->whereHas('entry', function ($q) use ($branchId, $date) {
                        $q->where('status', JournalEntry::STATUS_POSTED)
                          ->where('date', '<=', $date);
                        if ($branchId) {
                            $q->where('branch_id', $branchId);
                        }
                    });

                $sumDebit = Money::round(Money::n($lineQuery->sum('debit')));
                $sumCredit = Money::round(Money::n($lineQuery->sum('credit')));

                $balance = $isDebitNormal
                    ? Money::subtract($sumDebit, $sumCredit)
                    : Money::subtract($sumCredit, $sumDebit);

                if (! Money::isZero($balance)) {
                    $items[] = ['account' => $acc, 'balance' => $balance];
                    $total = Money::add($total, $balance);
                }
            }
            return [$items, $total];
        };

        [$assets, $totalAssets] = $calcBalances($assetAccounts, true);
        [$liabilities, $totalLiabilities] = $calcBalances($liabilityAccounts, false);
        [$equity, $totalEquityBase] = $calcBalances($equityAccounts, false);

        // Calculate Retained Earnings (all historical revenue - expense up to date)
        $pnl = $this->profitAndLoss($branchId, '2000-01-01', $date);
        $retainedEarnings = $pnl['net_profit'];

        $totalEquity = Money::add($totalEquityBase, $retainedEarnings);
        $totalLiabilitiesAndEquity = Money::add($totalLiabilities, $totalEquity);

        return [
            'as_of_date' => $date,
            'assets' => $assets,
            'total_assets' => $totalAssets,
            'liabilities' => $liabilities,
            'total_liabilities' => $totalLiabilities,
            'equity' => $equity,
            'retained_earnings' => $retainedEarnings,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => Money::compare($totalAssets, $totalLiabilitiesAndEquity) === 0,
        ];
    }

    /**
     * General Ledger for an account.
     */
    public function generalLedger(int $accountId, ?int $branchId = null, ?string $fromDate = null, ?string $toDate = null): array
    {
        $account = Account::findOrFail($accountId);
        $from = $fromDate ? Carbon::parse($fromDate)->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
        $to = $toDate ? Carbon::parse($toDate)->format('Y-m-d') : now()->format('Y-m-d');

        // Opening balance before fromDate
        $preQuery = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('entry', function ($q) use ($branchId, $from) {
                $q->where('status', JournalEntry::STATUS_POSTED)
                  ->where('date', '<', $from);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            });

        $preDebit = Money::round(Money::n($preQuery->sum('debit')));
        $preCredit = Money::round(Money::n($preQuery->sum('credit')));

        $openingBalance = $account->isDebit()
            ? Money::subtract($preDebit, $preCredit)
            : Money::subtract($preCredit, $preDebit);

        // Lines in range
        $lines = JournalEntryLine::query()
            ->where('account_id', $account->id)
            ->whereHas('entry', function ($q) use ($branchId, $from, $to) {
                $q->where('status', JournalEntry::STATUS_POSTED)
                  ->whereBetween('date', [$from, $to]);
                if ($branchId) {
                    $q->where('branch_id', $branchId);
                }
            })
            ->with('entry')
            ->get()
            ->sortBy(fn ($l) => $l->entry->date->format('Y-m-d') . ' ' . str_pad($l->entry->id, 10, '0', STR_PAD_LEFT));

        $running = $openingBalance;
        $formattedLines = [];
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($lines as $line) {
            $deb = Money::round($line->debit);
            $cred = Money::round($line->credit);

            if ($account->isDebit()) {
                $running = Money::subtract(Money::add($running, $deb), $cred);
            } else {
                $running = Money::add(Money::subtract($running, $deb), $cred);
            }

            $totalDebit = Money::add($totalDebit, $deb);
            $totalCredit = Money::add($totalCredit, $cred);

            $formattedLines[] = [
                'id' => $line->id,
                'date' => $line->entry->date->format('Y-m-d'),
                'entry_number' => $line->entry->entry_number,
                'narration' => $line->entry->narration,
                'memo' => $line->memo,
                'debit' => $deb,
                'credit' => $cred,
                'running_balance' => $running,
            ];
        }

        return [
            'account' => $account,
            'from_date' => $from,
            'to_date' => $to,
            'opening_balance' => $openingBalance,
            'lines' => $formattedLines,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'closing_balance' => $running,
        ];
    }

    /**
     * Day Book: chronological journal listing for a single day.
     */
    public function dayBook(?int $branchId = null, ?string $date = null): array
    {
        $d = $date ? Carbon::parse($date)->format('Y-m-d') : now()->format('Y-m-d');

        $query = JournalEntry::query()
            ->where('date', $d)
            ->with(['lines.account', 'creator'])
            ->orderBy('id');

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $entries = $query->get();

        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($entries as $entry) {
            if ($entry->isPosted()) {
                foreach ($entry->lines as $line) {
                    $totalDebit = Money::add($totalDebit, $line->debit);
                    $totalCredit = Money::add($totalCredit, $line->credit);
                }
            }
        }

        return [
            'date' => $d,
            'entries' => $entries,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
        ];
    }
}
