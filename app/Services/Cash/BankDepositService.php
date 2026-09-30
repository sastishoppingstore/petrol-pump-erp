<?php

namespace App\Services\Cash;

use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\Shift;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Money;
use App\Support\PermissionList;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Bank deposits (spec sections 8 and 14).
 *
 * A deposit moves cash out of the shift till and into a bank account. It is
 * therefore also a cash-out for the shift: the money leaves the drawer even
 * though it has not been spent, so the shift's expected cash must fall by the
 * same amount. Deposits are append-only — a wrong deposit is reversed, never
 * deleted.
 */
class BankDepositService
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly NumberSequenceService $sequences,
    ) {
    }

    /**
     * Record a deposit.
     *
     * @throws ValidationException
     */
    public function deposit(
        User $actor,
        int $branchId,
        BankAccount $account,
        string $amount,
        ?Shift $shift = null,
        ?string $reason = null,
        string $depositType = BankDeposit::TYPE_CASH,
        ?string $referenceNumber = null,
        ?\Carbon\CarbonInterface $depositedAt = null,
    ): BankDeposit {
        if (! $actor->hasAnyPermission([PermissionList::CASH_CREATE])) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'You do not have permission to record a bank deposit.',
            ]);
        }

        if (! $actor->canAccessBranch($branchId)) {
            throw ValidationException::withMessages([
                'branch_id' => 'You do not have access to that branch.',
            ]);
        }

        $amount = Money::round($amount);

        if (Money::compare($amount, '0') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Deposit amount must be greater than zero.',
            ]);
        }

        if (! $account->isActive()) {
            throw ValidationException::withMessages([
                'bank_account_id' => 'That bank account is inactive.',
            ]);
        }

        // The deposit must not exceed the cash actually in the drawer, or the
        // shift would close short for a reason that never happened.
        if ($shift && $shift->isOpen()) {
            $available = $this->availableCashInShift($shift);

            if (Money::compare($amount, $available) > 0) {
                throw ValidationException::withMessages([
                    'amount' => sprintf(
                        'Deposit of %s exceeds the %s currently available in shift %s.',
                        Money::format($amount),
                        Money::format($available),
                        $shift->shift_number,
                    ),
                ]);
            }
        }

        try {
            return DB::transaction(function () use (
                $actor, $branchId, $account, $amount, $shift,
                $reason, $depositType, $referenceNumber, $depositedAt
            ) {
                $locked = BankAccount::query()
                    ->whereKey($account->id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $balanceBefore = Money::add(
                    Money::n($locked->opening_balance),
                    Money::n($locked->deposits()->where('status', BankDeposit::STATUS_COMPLETED)->sum('amount')),
                );

                $balanceAfter = Money::add($balanceBefore, $amount);

                $deposit = BankDeposit::create([
                    'branch_id' => $branchId,
                    'bank_account_id' => $locked->id,
                    // Frozen so the report still names the bank if the account
                    // is later renamed or reassigned.
                    'bank_name' => $locked->bank?->name ?? 'Unknown bank',
                    'shift_id' => $shift?->id,
                    'amount' => $amount,
                    'balance_before' => $balanceBefore,
                    'balance_after' => $balanceAfter,
                    'reference_number' => $referenceNumber ?: $this->sequences->next('payment'),
                    'deposited_at' => $depositedAt ?? now(),
                    'reason' => $reason,
                    'deposit_type' => $depositType,
                    'deposited_by' => $actor->id,
                    'status' => BankDeposit::STATUS_COMPLETED,
                ]);

                // The cash leaves the drawer: reduce the shift's float so the
                // closing reconciliation balances.
                if ($shift) {
                    DB::table('shift_cash')->insert([
                        'shift_id' => $shift->id,
                        'entry_type' => 'BANK_DEPOSIT',
                        'amount' => $amount,
                        'notes' => "Deposit to {$deposit->bank_name} ({$deposit->reference_number})",
                        'user_id' => $actor->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->audit->record(
                    userId: $actor->id,
                    action: 'bank_deposit',
                    module: 'bank',
                    referenceType: BankDeposit::class,
                    referenceId: $deposit->id,
                    newData: [
                        'bank' => $deposit->bank_name,
                        'reference' => $deposit->reference_number,
                        'amount' => $deposit->amount,
                        'shift' => $shift?->shift_number,
                    ],
                );

                try {
                    app(\App\Services\Accounting\AccountingService::class)->postBankDeposit($deposit);
                } catch (\Throwable $e) {
                    Log::warning('GL postBankDeposit: ' . $e->getMessage());
                }

                return $deposit;
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Bank deposit failed', ['error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'amount' => 'Unable to record the bank deposit. No changes were saved.',
            ]);
        }
    }

    /**
     * Cash physically available to deposit right now: the shift's expected cash
     * minus anything already banked or handed over during it.
     */
    public function availableCashInShift(Shift $shift): string
    {
        $expected = Money::n(app(\App\Services\Shift\ShiftService::class)->summary($shift)['expected_cash']);

        $alreadyOut = Money::n(DB::table('shift_cash')
            ->where('shift_id', $shift->id)
            ->whereIn('entry_type', ['BANK_DEPOSIT', 'DROP', 'HANDOVER'])
            ->sum('amount'));

        $available = Money::subtract($expected, $alreadyOut);

        return Money::isNegative($available) ? '0.00' : $available;
    }

    /**
     * Reverse a deposit. Never deletes it (spec section 45).
     */
    public function reverse(BankDeposit $deposit, User $actor, string $reason): BankDeposit
    {
        if ($deposit->status === BankDeposit::STATUS_REVERSED) {
            throw ValidationException::withMessages([
                'status' => 'This deposit has already been reversed.',
            ]);
        }

        if (! $actor->hasAnyPermission([PermissionList::CASH_APPROVE])) {
            throw ValidationException::withMessages([
                'status' => 'You do not have permission to reverse a bank deposit.',
            ]);
        }

        return DB::transaction(function () use ($deposit, $actor, $reason) {
            $locked = BankDeposit::query()->whereKey($deposit->id)->lockForUpdate()->firstOrFail();

            $locked->update([
                'status' => BankDeposit::STATUS_REVERSED,
                'approved_by' => $actor->id,
            ]);

            // Return the cash to the drawer it left.
            if ($locked->shift_id) {
                DB::table('shift_cash')->insert([
                    'shift_id' => $locked->shift_id,
                    'entry_type' => 'DEPOSIT_REVERSAL',
                    'amount' => $locked->amount,
                    'notes' => "Reversal of deposit {$locked->reference_number}: {$reason}",
                    'user_id' => $actor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'bank_deposit_reverse',
                module: 'bank',
                referenceType: BankDeposit::class,
                referenceId: $locked->id,
                oldData: ['status' => BankDeposit::STATUS_COMPLETED],
                newData: ['status' => BankDeposit::STATUS_REVERSED, 'reason' => $reason],
            );

            return $locked->fresh();
        });
    }
}
