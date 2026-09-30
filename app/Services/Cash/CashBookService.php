<?php

namespace App\Services\Cash;

use App\Models\Branch;
use App\Models\CashEntry;
use App\Models\Shift;
use App\Models\ShiftCash;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Shift\ShiftService;
use App\Services\System\NumberSequenceService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Roznamcha & Forecourt Cash Book Service.
 *
 * Enforces strict financial integrity:
 * - Cash cannot go negative on Cash Out payouts.
 * - Every transaction generates sequential voucher numbers (CRV-..., CPV-...).
 * - Tied directly to active shifts and audit logs.
 */
class CashBookService
{
    public function __construct(
        private readonly NumberSequenceService $sequences,
        private readonly ShiftService $shiftService,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Record Cash Inflow (Receipt).
     */
    public function recordCashIn(
        User $actor,
        int $branchId,
        string $amount,
        string $category,
        string $personName,
        ?int $shiftId = null,
        ?string $referenceNo = null,
        ?string $attachmentPath = null,
        ?string $notes = null,
        ?string $entryDate = null,
    ): CashEntry {
        $amount = Money::round($amount);

        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Cash in amount must be greater than zero.',
            ]);
        }

        if (trim($personName) === '') {
            throw ValidationException::withMessages([
                'person_name' => 'Name of the person paying cash is required.',
            ]);
        }

        $shift = $this->resolveShift($branchId, $shiftId, $actor);

        return DB::transaction(function () use (
            $actor, $branchId, $shift, $amount, $category, $personName,
            $referenceNo, $attachmentPath, $notes, $entryDate
        ) {
            $voucherNumber = $this->sequences->next('crv');

            $entry = CashEntry::create([
                'branch_id' => $branchId,
                'shift_id' => $shift?->id,
                'voucher_number' => $voucherNumber,
                'type' => CashEntry::TYPE_IN,
                'category' => $category,
                'amount' => $amount,
                'person_name' => $personName,
                'reference_no' => $referenceNo,
                'attachment_path' => $attachmentPath,
                'notes' => $notes,
                'user_id' => $actor->id,
                'status' => CashEntry::STATUS_APPROVED,
                'entry_date' => $entryDate ? \Carbon\Carbon::parse($entryDate) : now(),
            ]);

            // Link into shift cash ledger if an open shift is present
            if ($shift) {
                ShiftCash::create([
                    'shift_id' => $shift->id,
                    'entry_type' => 'CASH_IN',
                    'amount' => $amount,
                    'notes' => "CRV: {$voucherNumber} - {$personName} ({$category})",
                    'user_id' => $actor->id,
                ]);
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'cash_in',
                module: 'cash',
                referenceType: CashEntry::class,
                referenceId: $entry->id,
                newData: [
                    'voucher_number' => $voucherNumber,
                    'type' => CashEntry::TYPE_IN,
                    'category' => $category,
                    'amount' => $amount,
                    'person_name' => $personName,
                    'shift_id' => $shift?->id,
                ],
            );

            return $entry;
        });
    }

    /**
     * Record Cash Outflow (Payment/Expense/Deposit).
     *
     * RULE: Cash cannot go negative! Payout cannot exceed current till cash.
     */
    public function recordCashOut(
        User $actor,
        int $branchId,
        string $amount,
        string $category,
        string $personName,
        ?int $shiftId = null,
        ?string $referenceNo = null,
        ?string $attachmentPath = null,
        ?string $notes = null,
        ?string $entryDate = null,
    ): CashEntry {
        $amount = Money::round($amount);

        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Cash out amount must be greater than zero.',
            ]);
        }

        if (trim($personName) === '') {
            throw ValidationException::withMessages([
                'person_name' => 'Name of the recipient is required.',
            ]);
        }

        $shift = $this->resolveShift($branchId, $shiftId, $actor);

        // Check available cash in till / shift to prevent negative cash balance
        $available = $this->availableCash($branchId, $shift?->id);

        if (Money::compare($amount, $available) > 0) {
            throw ValidationException::withMessages([
                'amount' => sprintf(
                    'Cash cannot go negative. Current available cash is %s, but payout is %s.',
                    PakistaniCurrency::format($available),
                    PakistaniCurrency::format($amount),
                ),
            ]);
        }

        return DB::transaction(function () use (
            $actor, $branchId, $shift, $amount, $category, $personName,
            $referenceNo, $attachmentPath, $notes, $entryDate
        ) {
            $voucherNumber = $this->sequences->next('cpv');

            $entry = CashEntry::create([
                'branch_id' => $branchId,
                'shift_id' => $shift?->id,
                'voucher_number' => $voucherNumber,
                'type' => CashEntry::TYPE_OUT,
                'category' => $category,
                'amount' => $amount,
                'person_name' => $personName,
                'reference_no' => $referenceNo,
                'attachment_path' => $attachmentPath,
                'notes' => $notes,
                'user_id' => $actor->id,
                'status' => CashEntry::STATUS_APPROVED,
                'entry_date' => $entryDate ? \Carbon\Carbon::parse($entryDate) : now(),
            ]);

            // Link into shift cash ledger
            if ($shift) {
                ShiftCash::create([
                    'shift_id' => $shift->id,
                    'entry_type' => 'CASH_OUT',
                    'amount' => $amount,
                    'notes' => "CPV: {$voucherNumber} - {$personName} ({$category})",
                    'user_id' => $actor->id,
                ]);
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'cash_out',
                module: 'cash',
                referenceType: CashEntry::class,
                referenceId: $entry->id,
                newData: [
                    'voucher_number' => $voucherNumber,
                    'type' => CashEntry::TYPE_OUT,
                    'category' => $category,
                    'amount' => $amount,
                    'person_name' => $personName,
                    'shift_id' => $shift?->id,
                ],
            );

            return $entry;
        });
    }

    /**
     * Compute available cash for the active shift or station drawer.
     */
    public function availableCash(int $branchId, ?int $shiftId = null): string
    {
        if ($shiftId) {
            $shift = Shift::find($shiftId);
            if ($shift && $shift->isOpen()) {
                $summary = $this->shiftService->summary($shift);
                return Money::round((string) ($summary['expected_cash'] ?? '0.00'));
            }
        }

        // Active open shift for the branch
        $activeShift = Shift::where('branch_id', $branchId)
            ->where('status', Shift::STATUS_OPEN)
            ->latest('opened_at')
            ->first();

        if ($activeShift) {
            $summary = $this->shiftService->summary($activeShift);
            return Money::round((string) ($summary['expected_cash'] ?? '0.00'));
        }

        // Fallback: branch total Cash In - Cash Out
        $in = CashEntry::where('branch_id', $branchId)
            ->where('type', CashEntry::TYPE_IN)
            ->where('status', CashEntry::STATUS_APPROVED)
            ->sum('amount');

        $out = CashEntry::where('branch_id', $branchId)
            ->where('type', CashEntry::TYPE_OUT)
            ->where('status', CashEntry::STATUS_APPROVED)
            ->sum('amount');

        $balance = Money::subtract((string) $in, (string) $out);
        return Money::isNegative($balance) ? '0.00' : $balance;
    }

    /**
     * Get Roznamcha (daily cash book register) data for a given branch and date.
     */
    public function dailyRoznamcha(int $branchId, ?string $date = null): array
    {
        $date = $date ? \Carbon\Carbon::parse($date)->toDateString() : now()->toDateString();

        $entries = CashEntry::query()
            ->with(['user', 'shift'])
            ->where('branch_id', $branchId)
            ->whereDate('entry_date', $date)
            ->orderBy('created_at', 'asc')
            ->get();

        $totalIn = '0.00';
        $totalOut = '0.00';

        foreach ($entries as $e) {
            if ($e->isCashIn()) {
                $totalIn = Money::add($totalIn, (string) $e->amount);
            } else {
                $totalOut = Money::add($totalOut, (string) $e->amount);
            }
        }

        // Prior day closing balance = opening balance of today
        $priorIn = CashEntry::where('branch_id', $branchId)
            ->where('type', CashEntry::TYPE_IN)
            ->where('status', CashEntry::STATUS_APPROVED)
            ->whereDate('entry_date', '<', $date)
            ->sum('amount');

        $priorOut = CashEntry::where('branch_id', $branchId)
            ->where('type', CashEntry::TYPE_OUT)
            ->where('status', CashEntry::STATUS_APPROVED)
            ->whereDate('entry_date', '<', $date)
            ->sum('amount');

        $openingCash = Money::subtract((string) $priorIn, (string) $priorOut);
        if (Money::isNegative($openingCash)) {
            $openingCash = '0.00';
        }

        $closingCash = Money::subtract(Money::add($openingCash, $totalIn), $totalOut);

        return [
            'date' => $date,
            'opening_cash' => $openingCash,
            'entries' => $entries,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'closing_cash' => $closingCash,
        ];
    }

    private function resolveShift(int $branchId, ?int $shiftId, User $actor): ?Shift
    {
        if ($shiftId) {
            return Shift::where('id', $shiftId)->where('branch_id', $branchId)->first();
        }

        return $this->shiftService->activeShiftFor($actor, $branchId);
    }
}
