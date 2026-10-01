<?php

namespace App\Services\Customer;

use App\Models\CashEntry;
use App\Models\Customer;
use App\Models\CustomerLedger;
use App\Models\CustomerPayment;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CustomerLedgerService
{
    /**
     * Record an append-only debit on customer ledger.
     * Debit increases the customer's outstanding balance (e.g. credit fuel sale).
     */
    public function recordDebit(
        Customer $customer,
        string $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $date = null,
        ?int $branchId = null,
    ): CustomerLedger {
        return $this->appendEntry(
            customer: $customer,
            debit: Money::round(Money::n($amount)),
            credit: '0.00',
            description: $description,
            referenceType: $referenceType,
            referenceId: $referenceId,
            date: $date,
            branchId: $branchId,
        );
    }

    /**
     * Record an append-only credit on customer ledger.
     * Credit decreases the customer's outstanding balance (e.g. payment received).
     */
    public function recordCredit(
        Customer $customer,
        string $amount,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $date = null,
        ?int $branchId = null,
    ): CustomerLedger {
        return $this->appendEntry(
            customer: $customer,
            debit: '0.00',
            credit: Money::round(Money::n($amount)),
            description: $description,
            referenceType: $referenceType,
            referenceId: $referenceId,
            date: $date,
            branchId: $branchId,
        );
    }

    /**
     * Internal atomic append with running balance calculation.
     */
    protected function appendEntry(
        Customer $customer,
        string $debit,
        string $credit,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $date = null,
        ?int $branchId = null,
    ): CustomerLedger {
        return DB::transaction(function () use ($customer, $debit, $credit, $description, $referenceType, $referenceId, $date, $branchId) {
            $lockedCustomer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            // Find latest ledger row
            $lastEntry = CustomerLedger::query()
                ->where('customer_id', $lockedCustomer->id)
                ->orderByDesc('id')
                ->first();

            $prevBalance = $lastEntry
                ? Money::n($lastEntry->running_balance)
                : Money::n($lockedCustomer->opening_balance);

            // New running balance = prev + debit - credit
            $newBalance = Money::subtract(Money::add($prevBalance, $debit), $credit);

            $entry = CustomerLedger::create([
                'branch_id' => $branchId ?? $lockedCustomer->branch_id ?? 1,
                'customer_id' => $lockedCustomer->id,
                'date' => $date ?? today()->toDateString(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $newBalance,
            ]);

            $lockedCustomer->forceFill([
                'current_balance' => $newBalance,
            ])->save();

            return $entry;
        });
    }

    /**
     * Collect a customer payment (Cash, Bank, Cheque).
     */
    public function recordPayment(Customer $customer, array $data, ?int $userId = null): CustomerPayment
    {
        $amount = Money::round(Money::n($data['amount'] ?? '0'));
        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $method = $data['payment_method'] ?? CustomerPayment::METHOD_CASH;
        $date = $data['payment_date'] ?? today()->toDateString();
        $branchId = $data['branch_id'] ?? $customer->branch_id ?? 1;

        return DB::transaction(function () use ($customer, $data, $amount, $method, $date, $branchId, $userId) {
            $paymentNumber = $data['payment_number'] ?? ('CPAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));

            $payment = CustomerPayment::create([
                'branch_id' => $branchId,
                'customer_id' => $customer->id,
                'sale_id' => $data['sale_id'] ?? null,
                'payment_number' => $paymentNumber,
                'payment_date' => $date,
                'payment_method' => $method,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'cheque_number' => $data['cheque_number'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'cheque_status' => $data['cheque_status'] ?? ($method === CustomerPayment::METHOD_CHEQUE ? 'PENDING' : null),
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId ?? auth()->id(),
            ]);

            // Append credit entry to customer ledger
            $description = sprintf('Payment Received [%s] #%s', $method, $paymentNumber);
            if (! empty($data['notes'])) {
                $description .= ' - ' . $data['notes'];
            }

            $this->recordCredit(
                customer: $customer,
                amount: $amount,
                description: $description,
                referenceType: CustomerPayment::class,
                referenceId: $payment->id,
                date: $date,
                branchId: $branchId,
            );

            // If cash payment, record in cash_entries
            if ($method === CustomerPayment::METHOD_CASH) {
                CashEntry::create([
                    'branch_id' => $branchId,
                    'shift_id' => $data['shift_id'] ?? session('active_shift_id'),
                    'voucher_number' => 'CV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
                    'type' => CashEntry::TYPE_CASH_IN,
                    'category' => 'CUSTOMER_PAYMENT',
                    'amount' => $amount,
                    'person_name' => $customer->name,
                    'reference_no' => $paymentNumber,
                    'notes' => 'Customer Udhaar Payment - ' . $customer->name,
                    'user_id' => $userId ?? auth()->id() ?? 1,
                    'status' => CashEntry::STATUS_APPROVED,
                    'approved_by' => $userId ?? auth()->id() ?? 1,
                    'entry_date' => Carbon::parse($date),
                ]);
            }

            return $payment;
        });
    }

    /**
     * Calculate Ageing Report for a customer using FIFO allocation.
     * Buckets: 0-30 days, 31-60 days, 61-90 days, 90+ days.
     */
    public function getCustomerAgeing(Customer $customer): array
    {
        $currentBalance = Money::n($customer->current_balance ?? $customer->outstandingBalance());

        $buckets = [
            '0_30' => '0.00',
            '31_60' => '0.00',
            '61_90' => '0.00',
            'over_90' => '0.00',
            'total' => $currentBalance,
        ];

        // If balance <= 0, no outstanding ageing
        if (Money::compare($currentBalance, '0.00') <= 0) {
            return $buckets;
        }

        $remainingToAllocate = $currentBalance;
        $now = Carbon::today();

        // Get debit entries in descending order of date (newest first)
        $debits = CustomerLedger::query()
            ->where('customer_id', $customer->id)
            ->where('debit', '>', 0)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        foreach ($debits as $entry) {
            if (Money::compare($remainingToAllocate, '0.00') <= 0) {
                break;
            }

            $debitAmount = Money::n($entry->debit);
            $allocatable = Money::compare($remainingToAllocate, $debitAmount) < 0
                ? $remainingToAllocate
                : $debitAmount;

            $entryDate = Carbon::parse($entry->date);
            $ageInDays = $entryDate->diffInDays($now, false);

            if ($ageInDays <= 30) {
                $buckets['0_30'] = Money::add($buckets['0_30'], $allocatable);
            } elseif ($ageInDays <= 60) {
                $buckets['31_60'] = Money::add($buckets['31_60'], $allocatable);
            } elseif ($ageInDays <= 90) {
                $buckets['61_90'] = Money::add($buckets['61_90'], $allocatable);
            } else {
                $buckets['over_90'] = Money::add($buckets['over_90'], $allocatable);
            }

            $remainingToAllocate = Money::subtract($remainingToAllocate, $allocatable);
        }

        // Any leftover unallocated balance (e.g. from opening balance) goes to 90+ days
        if (Money::compare($remainingToAllocate, '0.00') > 0) {
            $buckets['over_90'] = Money::add($buckets['over_90'], $remainingToAllocate);
        }

        return $buckets;
    }

    /**
     * Get ageing summary for all customers or a specific branch.
     */
    public function getAgeingReport(?int $branchId = null): array
    {
        $customers = Customer::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('status', Customer::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $rows = [];
        $totals = [
            '0_30' => '0.00',
            '31_60' => '0.00',
            '61_90' => '0.00',
            'over_90' => '0.00',
            'total' => '0.00',
        ];

        foreach ($customers as $customer) {
            $ageing = $this->getCustomerAgeing($customer);
            if (Money::compare($ageing['total'], '0.00') > 0) {
                $rows[] = [
                    'customer' => $customer,
                    'ageing' => $ageing,
                    'whatsapp_link' => $this->generateWhatsAppLink($customer, $ageing['total']),
                ];

                $totals['0_30'] = Money::add($totals['0_30'], $ageing['0_30']);
                $totals['31_60'] = Money::add($totals['31_60'], $ageing['31_60']);
                $totals['61_90'] = Money::add($totals['61_90'], $ageing['61_90']);
                $totals['over_90'] = Money::add($totals['over_90'], $ageing['over_90']);
                $totals['total'] = Money::add($totals['total'], $ageing['total']);
            }
        }

        return [
            'rows' => $rows,
            'totals' => $totals,
        ];
    }

    /**
     * Normalize a Pakistani mobile number to wa.me format (92XXXXXXXXXX).
     */
    public function normalizePhone(?string $rawPhone): string
    {
        $phone = preg_replace('/[^0-9]/', '', (string) $rawPhone);
        if (str_starts_with($phone, '03')) {
            $phone = '92' . substr($phone, 1);
        } elseif (str_starts_with($phone, '3')) {
            $phone = '92' . $phone;
        }

        return $phone;
    }

    /**
     * Build WhatsApp wa.me click-to-chat payment reminder link.
     */
    public function generateWhatsAppLink(Customer $customer, ?string $amount = null): string
    {
        $phone = $this->normalizePhone($customer->phone);

        $balance = $amount ?? $customer->current_balance ?? $customer->outstandingBalance();
        $formattedLakh = PakistaniCurrency::format($balance, true);
        $inWordsUrdu = PakistaniCurrency::toUrduWords($balance);

        $message = "محترم {$customer->name} صاحب،\n"
            . "مہر فلنگ اسٹیشن (وائٹل پٹرولیم فرنچائز، شیخوپورہ) کی جانب سے بقایا جات کی ادائیگی کا تقاضا:\n"
            . "آپ کا کل واجب الادا بیلنس: {$formattedLakh}\n"
            . "({$inWordsUrdu})\n\n"
            . "برائے مہربانی اپنی بقایا رقم جلد از جلد ادا فرمائیں۔ شکریہ!\n"
            . "Mehar Filling Station (Vital Petroleum), Sheikhupura.";

        return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
    }

    /**
     * Short SMS payment reminder text (used by the Collection screen
     * bulk/single SMS actions). Kept compact for a single SMS segment
     * mindset; the gateway decides segmentation.
     */
    public function smsReminderMessage(Customer $customer): string
    {
        $balance = $customer->current_balance ?? $customer->outstandingBalance();
        $formatted = PakistaniCurrency::format($balance);

        return "Mehar Filling Station (Vital Petroleum): Dear {$customer->name}, "
            . "your outstanding balance is {$formatted}. "
            . 'Please clear your dues at the earliest. Thank you. '
            . 'ادھار کی ادائیگی کی یاد دہانی — مہر فلنگ اسٹیشن';
    }

    /**
     * WhatsApp share link that sends the customer a URL (e.g. the
     * signed public statement link) with a short covering message.
     */
    public function whatsappShareUrl(Customer $customer, string $url): string
    {
        $phone = $this->normalizePhone($customer->phone);
        $balance = $customer->current_balance ?? $customer->outstandingBalance();
        $formatted = PakistaniCurrency::format($balance);

        $message = "محترم {$customer->name} صاحب،\n"
            . "مہر فلنگ اسٹیشن (وائٹل پٹرولیم) — آپ کے کھاتے کی اسٹیٹمنٹ:\n"
            . "{$url}\n"
            . "موجودہ بیلنس: {$formatted}\n"
            . 'Mehar Filling Station (Vital Petroleum), Sheikhupura.';

        return 'https://wa.me/' . $phone . '?text=' . urlencode($message);
    }

    /**
     * Collection (Wasooli) list: every active customer with a positive
     * balance, sorted by amount due (largest first), each row carrying
     * FIFO ageing buckets, last ledger activity date and a ready-made
     * WhatsApp reminder link.
     *
     * @return array{rows: array<int, array<string, mixed>>, totals: array<string, string>, overdue_count: int}
     */
    public function getCollectionList(?int $branchId = null): array
    {
        $report = $this->getAgeingReport($branchId);
        $rows = $report['rows'];

        $customerIds = array_map(fn (array $row) => $row['customer']->id, $rows);

        $lastActivity = [];
        if ($customerIds !== []) {
            $lastActivity = CustomerLedger::query()
                ->whereIn('customer_id', $customerIds)
                ->selectRaw('customer_id, MAX(date) as last_date')
                ->groupBy('customer_id')
                ->pluck('last_date', 'customer_id')
                ->all();
        }

        $overdueCount = 0;
        foreach ($rows as &$row) {
            $row['last_activity'] = $lastActivity[$row['customer']->id] ?? null;

            $olderThan30 = Money::add(
                Money::add(Money::n($row['ageing']['31_60']), Money::n($row['ageing']['61_90'])),
                Money::n($row['ageing']['over_90'])
            );
            if (Money::compare($olderThan30, '0.00') > 0) {
                $overdueCount++;
            }
        }
        unset($row);

        usort($rows, fn (array $a, array $b) => Money::compare(
            Money::n($b['ageing']['total']),
            Money::n($a['ageing']['total'])
        ));

        return [
            'rows' => $rows,
            'totals' => $report['totals'],
            'overdue_count' => $overdueCount,
        ];
    }

    /**
     * Generate customer statement across date range.
     */
    public function generateStatementData(Customer $customer, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? today()->startOfMonth()->toDateString();
        $endDate = $endDate ?? today()->toDateString();

        // Calculate opening balance as of startDate
        // Opening = customer.opening_balance + sum(debit before start) - sum(credit before start)
        $priorDebits = CustomerLedger::query()
            ->where('customer_id', $customer->id)
            ->where('date', '<', $startDate)
            ->sum('debit');

        $priorCredits = CustomerLedger::query()
            ->where('customer_id', $customer->id)
            ->where('date', '<', $startDate)
            ->sum('credit');

        $openingBalance = Money::add(
            Money::n($customer->opening_balance),
            Money::subtract(Money::n($priorDebits), Money::n($priorCredits))
        );

        // Transactions in period
        $transactions = CustomerLedger::query()
            ->where('customer_id', $customer->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = $openingBalance;
        $items = [];
        $totalDebits = '0.00';
        $totalCredits = '0.00';

        foreach ($transactions as $txn) {
            $running = Money::subtract(Money::add($running, Money::n($txn->debit)), Money::n($txn->credit));
            $totalDebits = Money::add($totalDebits, Money::n($txn->debit));
            $totalCredits = Money::add($totalCredits, Money::n($txn->credit));

            $items[] = [
                'id' => $txn->id,
                'date' => $txn->date->format('d/m/Y'),
                'description' => $txn->description,
                'reference_type' => $txn->reference_type,
                'reference_id' => $txn->reference_id,
                'debit' => $txn->debit,
                'credit' => $txn->credit,
                'balance' => $running,
            ];
        }

        $closingBalance = $running;

        return [
            'customer' => $customer,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'in_words_urdu' => PakistaniCurrency::toUrduWords($closingBalance),
            'formatted_closing' => PakistaniCurrency::format($closingBalance),
            'items' => $items,
        ];
    }

    /**
     * Recalculate customer running balance from scratch (reconciliation audit tool).
     */
    public function recalculateBalances(Customer $customer): string
    {
        return DB::transaction(function () use ($customer) {
            $lockedCustomer = Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $balance = Money::n($lockedCustomer->opening_balance);

            $entries = CustomerLedger::query()
                ->where('customer_id', $lockedCustomer->id)
                ->orderBy('date')
                ->orderBy('id')
                ->get();

            foreach ($entries as $entry) {
                $balance = Money::subtract(Money::add($balance, Money::n($entry->debit)), Money::n($entry->credit));
                $entry->update(['running_balance' => $balance]);
            }

            $lockedCustomer->update(['current_balance' => $balance]);
            return $balance;
        });
    }
}
