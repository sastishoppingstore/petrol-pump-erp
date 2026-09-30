<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\Shift;
use App\Services\Cash\BankDepositService;
use App\Services\Shift\ShiftService;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class BankDepositManager extends Component
{
    use WithPagination;

    public ?int $selectedBankId = null;
    public ?int $bankAccountId = null;
    public string $amount = '';
    public string $reason = '';
    public string $depositType = 'CASH';
    public string $referenceNumber = '';
    public ?string $depositedAt = null;

    public ?string $success = null;
    public ?string $error = null;

    public function mount(): void
    {
        $this->depositedAt = now()->format('Y-m-d\TH:i');
    }

    /**
     * Every bank a deposit could be made into, grouped so the operator can
     * find their bank quickly in a long list.
     *
     * @return array<string, \Illuminate\Support\Collection>
     */
    public function getBanksProperty()
    {
        return Bank::query()
            ->where('status', 'ACTIVE')
            ->orderBy('bank_type')
            ->orderBy('name')
            ->get()
            ->groupBy(fn (Bank $b) => match ($b->bank_type) {
                Bank::TYPE_COMMERCIAL => 'Commercial Banks',
                Bank::TYPE_ISLAMIC => 'Islamic Banks',
                Bank::TYPE_PUBLIC => 'Public Sector Banks',
                default => 'Digital / Specialised',
            });
    }

    public function getAccountsProperty()
    {
        return BankAccount::query()
            ->with('bank')
            ->where('status', 'ACTIVE')
            ->orderBy('account_title')
            ->get();
    }

    public function getActiveShiftProperty(): ?Shift
    {
        return app(ShiftService::class)->activeShiftFor(auth()->user());
    }

    /**
     * Cash actually available to deposit from the open shift.
     */
    public function getAvailableCashProperty(): string
    {
        $shift = $this->activeShift;

        if (! $shift) {
            return '0.00';
        }

        return app(BankDepositService::class)->availableCashInShift($shift);
    }

    public function updatedSelectedBankId(): void
    {
        // Changing the bank invalidates the chosen account, so clear it rather
        // than leaving a mismatched pair selected.
        $this->bankAccountId = null;
        $this->error = null;
    }

    public function deposit(): void
    {
        $this->resetMessages();

        try {
            if (! $this->bankAccountId) {
                throw ValidationException::withMessages([
                    'bankAccountId' => 'Select the bank account to deposit into.',
                ]);
            }

            $account = BankAccount::findOrFail($this->bankAccountId);

            $deposit = app(BankDepositService::class)->deposit(
                actor: auth()->user(),
                branchId: $account->branch_id ?? 1,
                account: $account,
                amount: $this->amount,
                shift: $this->activeShift,
                reason: $this->reason ?: null,
                depositType: $this->depositType,
                referenceNumber: $this->referenceNumber ?: null,
                depositedAt: $this->depositedAt ? \Carbon\Carbon::parse($this->depositedAt) : null,
            );

            $this->success = sprintf(
                'Deposited %s to %s (ref %s).',
                'Rs. '.Money::format($deposit->amount),
                $deposit->bank_name,
                $deposit->reference_number,
            );

            $this->amount = '';
            $this->reason = '';
            $this->referenceNumber = '';

            unset($this->banks, $this->accounts);
        } catch (ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first();
        }
    }

    public function reverse(int $depositId, string $reason): void
    {
        $this->resetMessages();

        try {
            $deposit = BankDeposit::findOrFail($depositId);

            app(BankDepositService::class)->reverse($deposit, auth()->user(), $reason);

            $this->success = "Deposit {$deposit->reference_number} reversed.";
        } catch (ValidationException $e) {
            $this->error = collect($e->errors())->flatten()->first();
        }
    }

    private function resetError(): void
    {
        $this->error = null;
    }

    public function render()
    {
        $query = BankDeposit::query()
            ->with(['bankAccount.bank', 'depositor', 'shift'])
            ->when(auth()->user()->isSuperAdmin(), fn ($q) => $q, fn ($q) => $q->whereIn(
                'branch_id',
                auth()->user()->accessibleBranchIds()
            ))
            ->orderByDesc('deposited_at');

        return view('livewire.bank-deposit-manager', [
            'deposits' => $query->paginate(15),
        ]);
    }
}
