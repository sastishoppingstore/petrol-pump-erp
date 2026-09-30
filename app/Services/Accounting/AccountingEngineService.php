<?php

namespace App\Services\Accounting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Account;
use App\Models\Sale;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccountingEngineService
{
    /**
     * Post sale transaction to journal
     * Debit: Cash/Card/Receivable
     * Credit: Sales Revenue + Tax Liability
     */
    public function postSaleTransaction(Sale $sale, int $branchId): JournalEntry
    {
        return DB::transaction(function () use ($sale, $branchId) {
            $entry = JournalEntry::create([
                'branch_id' => $branchId,
                'entry_number' => $this->generateEntryNumber('SALES', $branchId),
                'entry_date' => $sale->sale_date,
                'entry_type' => 'SALE',
                'reference_type' => 'sales',
                'reference_id' => $sale->id,
                'description' => "Sale Invoice: {$sale->invoice_number}",
                'posted_by' => auth()->id(),
                'status' => 'POSTED',
            ]);

            // Debit: Cash/Receivable account
            $cashAccount = $this->getOrCreateAccount('1101', 'Cash', 'ASSET', 'BANK', $branchId);
            $receivableAccount = $sale->customer_id 
                ? $this->getOrCreateAccount('1201', 'Accounts Receivable', 'ASSET', 'RECEIVABLE', $branchId)
                : $cashAccount;

            $receivableAmount = $sale->customer_id ? $sale->total : 0;
            $cashAmount = !$sale->customer_id ? $sale->total : 0;

            if ($cashAmount > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $cashAccount->id,
                    'debit_amount' => $cashAmount,
                    'credit_amount' => 0,
                    'description' => "Cash from sale {$sale->invoice_number}",
                ]);
            }

            if ($receivableAmount > 0) {
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $receivableAccount->id,
                    'debit_amount' => $receivableAmount,
                    'credit_amount' => 0,
                    'description' => "Credit sale {$sale->invoice_number}",
                ]);
            }

            // Credit: Sales Revenue
            $revenueAccount = $this->getOrCreateAccount('4101', 'Fuel Sales Revenue', 'REVENUE', 'REVENUE', $branchId);
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $revenueAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $sale->subtotal - ($sale->discount ?? 0),
                'description' => "Revenue for {$sale->invoice_number}",
            ]);

            // Credit: Tax Payable (if applicable)
            if ($sale->tax > 0) {
                $taxAccount = $this->getOrCreateAccount('2101', 'Sales Tax Payable', 'LIABILITY', 'PAYABLE', $branchId);
                JournalEntryLine::create([
                    'journal_entry_id' => $entry->id,
                    'account_id' => $taxAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $sale->tax,
                    'description' => "Tax on sale {$sale->invoice_number}",
                ]);
            }

            // Post COGS (Cost of Goods Sold)
            $this->postCogs($entry, $sale, $branchId);

            // Update totals
            $this->updateEntryTotals($entry);

            Log::info("Sale transaction posted", ['sale_id' => $sale->id, 'entry_id' => $entry->id]);

            return $entry;
        });
    }

    /**
     * Post COGS for a sale
     */
    private function postCogs(JournalEntry $entry, Sale $sale, int $branchId): void
    {
        $totalCogs = 0;

        foreach ($sale->items as $item) {
            $cogs = ($item->litres * ($item->cost_rate ?? 0));
            $totalCogs += $cogs;
        }

        if ($totalCogs > 0) {
            // Debit: COGS
            $cogsAccount = $this->getOrCreateAccount('5101', 'Cost of Goods Sold', 'EXPENSE', 'COGS', $branchId);
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $cogsAccount->id,
                'debit_amount' => $totalCogs,
                'credit_amount' => 0,
                'description' => "COGS for sale {$sale->invoice_number}",
            ]);

            // Credit: Inventory
            $inventoryAccount = $this->getOrCreateAccount('1301', 'Fuel Inventory', 'ASSET', 'INVENTORY', $branchId);
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $totalCogs,
                'description' => "Inventory reduction for sale {$sale->invoice_number}",
            ]);
        }
    }

    /**
     * Post purchase transaction to journal
     */
    public function postPurchaseTransaction(Purchase $purchase, int $branchId): JournalEntry
    {
        return DB::transaction(function () use ($purchase, $branchId) {
            $entry = JournalEntry::create([
                'branch_id' => $branchId,
                'entry_number' => $this->generateEntryNumber('PURCHASE', $branchId),
                'entry_date' => $purchase->purchase_date,
                'entry_type' => 'PURCHASE',
                'reference_type' => 'purchases',
                'reference_id' => $purchase->id,
                'description' => "Purchase from {$purchase->supplier->name}",
                'posted_by' => auth()->id(),
                'status' => 'POSTED',
            ]);

            // Debit: Inventory
            $inventoryAccount = $this->getOrCreateAccount('1301', 'Fuel Inventory', 'ASSET', 'INVENTORY', $branchId);
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $inventoryAccount->id,
                'debit_amount' => $purchase->total_amount,
                'credit_amount' => 0,
                'description' => "Fuel purchase from {$purchase->supplier->name}",
            ]);

            // Credit: Accounts Payable
            $payableAccount = $this->getOrCreateAccount('2201', 'Accounts Payable', 'LIABILITY', 'PAYABLE', $branchId);
            JournalEntryLine::create([
                'journal_entry_id' => $entry->id,
                'account_id' => $payableAccount->id,
                'debit_amount' => 0,
                'credit_amount' => $purchase->total_amount,
                'description' => "Payable to {$purchase->supplier->name}",
            ]);

            $this->updateEntryTotals($entry);

            Log::info("Purchase transaction posted", ['purchase_id' => $purchase->id, 'entry_id' => $entry->id]);

            return $entry;
        });
    }

    /**
     * Get or create account
     */
    private function getOrCreateAccount(
        string $code,
        string $name,
        string $type,
        string $classification,
        int $branchId
    ): Account {
        return Account::firstOrCreate(
            ['branch_id' => $branchId, 'code' => $code],
            [
                'name' => $name,
                'type' => $type,
                'classification' => $classification,
                'normal_balance' => in_array($type, ['ASSET', 'EXPENSE']) ? 'DEBIT' : 'CREDIT',
            ]
        );
    }

    /**
     * Update journal entry totals
     */
    private function updateEntryTotals(JournalEntry $entry): void
    {
        $lines = $entry->lines;

        $totalDebit = $lines->sum('debit_amount');
        $totalCredit = $lines->sum('credit_amount');

        $entry->update([
            'total_debit' => round($totalDebit, 2),
            'total_credit' => round($totalCredit, 2),
        ]);

        // Verify balance
        if (round($totalDebit, 2) !== round($totalCredit, 2)) {
            Log::warning("Journal entry out of balance", [
                'entry_id' => $entry->id,
                'debit' => $totalDebit,
                'credit' => $totalCredit,
            ]);
        }
    }

    /**
     * Generate entry number
     */
    private function generateEntryNumber(string $type, int $branchId): string
    {
        $year = now()->year;
        $sequence = \App\Models\NumberSequence::firstOrCreate(
            ['type' => "JOURNAL_{$type}", 'branch_id' => $branchId, 'year' => $year],
            ['last_number' => 0]
        );

        $sequence->increment('last_number');

        return sprintf('JE-%s-%d-%06d', $type, $year, $sequence->last_number);
    }

    /**
     * Get trial balance
     */
    public function getTrialBalance(int $branchId, ?string $asOfDate = null): array
    {
        $asOfDate = $asOfDate ? new \DateTime($asOfDate) : now();

        $accounts = Account::where('branch_id', $branchId)->get();

        $trialBalance = [
            'as_of_date' => $asOfDate->format('Y-m-d'),
            'total_debits' => 0,
            'total_credits' => 0,
            'accounts' => [],
        ];

        foreach ($accounts as $account) {
            $debits = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate, $branchId) {
                    $q->where('branch_id', $branchId)
                        ->whereDate('entry_date', '<=', $asOfDate)
                        ->where('status', 'POSTED');
                })
                ->sum('debit_amount');

            $credits = JournalEntryLine::where('account_id', $account->id)
                ->whereHas('journalEntry', function ($q) use ($asOfDate, $branchId) {
                    $q->where('branch_id', $branchId)
                        ->whereDate('entry_date', '<=', $asOfDate)
                        ->where('status', 'POSTED');
                })
                ->sum('credit_amount');

            $balance = $debits - $credits;

            if (abs($balance) > 0.01) { // Show only non-zero balances
                $trialBalance['accounts'][] = [
                    'account_code' => $account->code,
                    'account_name' => $account->name,
                    'type' => $account->type,
                    'debit_balance' => in_array($account->type, ['ASSET', 'EXPENSE']) ? abs($balance) : 0,
                    'credit_balance' => in_array($account->type, ['LIABILITY', 'EQUITY', 'REVENUE']) ? abs($balance) : 0,
                ];

                $trialBalance['total_debits'] += max(0, $balance);
                $trialBalance['total_credits'] += max(0, -$balance);
            }
        }

        return $trialBalance;
    }

    /**
     * Get financial summary (P&L-like)
     */
    public function getFinancialSummary(int $branchId, string $startDate, string $endDate): array
    {
        return [
            'period' => "$startDate to $endDate",
            'revenue' => $this->getAccountBalance($branchId, 'REVENUE', $startDate, $endDate),
            'cogs' => $this->getAccountBalance($branchId, 'EXPENSE', $startDate, $endDate, 'COGS'),
            'operating_expenses' => $this->getAccountBalance($branchId, 'EXPENSE', $startDate, $endDate, 'OPERATING_EXPENSE'),
            'other_expenses' => $this->getAccountBalance($branchId, 'EXPENSE', $startDate, $endDate, 'OTHER'),
        ];
    }

    /**
     * Get account balance for period
     */
    private function getAccountBalance(
        int $branchId,
        string $type,
        string $startDate,
        string $endDate,
        ?string $classification = null
    ): float {
        $query = JournalEntryLine::whereHas('account', function ($q) use ($branchId, $type, $classification) {
            $q->where('branch_id', $branchId)
                ->where('type', $type);
            if ($classification) {
                $q->where('classification', $classification);
            }
        })->whereHas('journalEntry', function ($q) use ($branchId, $startDate, $endDate) {
            $q->where('branch_id', $branchId)
                ->whereBetween('entry_date', [$startDate, $endDate])
                ->where('status', 'POSTED');
        });

        $debits = $query->clone()->sum('debit_amount');
        $credits = $query->sum('credit_amount');

        return round($debits - $credits, 2);
    }

    /**
     * Verify journal entry is balanced
     */
    public function isEntryBalanced(JournalEntry $entry): bool
    {
        $totalDebit = $entry->lines->sum('debit_amount');
        $totalCredit = $entry->lines->sum('credit_amount');

        return abs($totalDebit - $totalCredit) < 0.01; // Allow for rounding
    }

    /**
     * Get account transactions
     */
    public function getAccountTransactions(Account $account, ?string $startDate = null, ?string $endDate = null): array
    {
        $query = JournalEntryLine::where('account_id', $account->id)
            ->with('journalEntry');

        if ($startDate) {
            $query->whereHas('journalEntry', function ($q) use ($startDate) {
                $q->whereDate('entry_date', '>=', $startDate);
            });
        }

        if ($endDate) {
            $query->whereHas('journalEntry', function ($q) use ($endDate) {
                $q->whereDate('entry_date', '<=', $endDate);
            });
        }

        return $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($line) {
                return [
                    'date' => $line->journalEntry->entry_date,
                    'reference' => $line->journalEntry->entry_number,
                    'description' => $line->description,
                    'debit' => $line->debit_amount,
                    'credit' => $line->credit_amount,
                    'balance' => ($line->debit_amount - $line->credit_amount),
                ];
            })
            ->toArray();
    }
}
