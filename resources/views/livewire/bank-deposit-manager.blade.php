<div class="space-y-4">

    @if ($success)
        <div class="rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
             data-auto-dismiss="8000">{{ $success }}</div>
    @endif
    @if ($error)
        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800 dark:border-red-900 dark:bg-red-950 dark:text-red-200"
             data-auto-dismiss="8000">{{ $error }}</div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">

        {{-- ================= Deposit form ================= --}}
        <div class="lg:col-span-1">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-1 text-base font-bold">Record a Bank Deposit</h2>
                <p class="mb-4 text-xs text-slate-500">
                    Cash leaves the shift drawer and enters the bank account.
                    The shift's expected cash falls by the same amount.
                </p>

                <div class="mb-3 rounded-md bg-slate-50 px-3 py-2 text-sm dark:bg-slate-800">
                    <span class="text-slate-500">Available cash in current shift:</span>
                    <strong class="tabular text-navy-800 dark:text-white">Rs. {{ number_format((float) $availableCash, 2) }}</strong>
                    @unless ($activeShift)
                        <div class="text-xs text-slate-500">No open shift — deposit will not affect the till.</div>
                    @endunless
                </div>

                <div class="mb-3">
                    <label for="bank" class="mb-1 block text-sm font-medium">Bank <span class="text-red-600">*</span></label>
                    <select id="bank" wire:model.live="selectedBankId"
                            class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                        <option value="">Select bank…</option>
                        @foreach ($this->banks as $group => $groupBanks)
                            <optgroup label="{{ $group }}">
                                @foreach ($groupBanks as $bank)
                                    <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label for="account" class="mb-1 block text-sm font-medium">Account <span class="text-red-600">*</span></label>
                    <select id="account" wire:model="bankAccountId"
                            class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                        <option value="">Select account…</option>
                        @foreach ($accounts->where('bank_id', (int) $selectedBankId) as $account)
                            <option value="{{ $account->id }}">
                                {{ $account->account_title }} — {{ $account->maskedAccountNumber() }}
                            </option>
                        @endforeach
                    </select>
                    @if (! $accounts->count())
                        <p class="mt-1 text-xs text-amber-600">
                            No bank accounts yet — add one from the Banks screen first.
                        </p>
                    @endif
                </div>

                <div class="mb-3 grid grid-cols-2 gap-3">
                    <div>
                        <label for="amount" class="mb-1 block text-sm font-medium">Amount (Rs.) <span class="text-red-600">*</span></label>
                        <input type="number" step="0.01" min="0.01" id="amount" wire:model="amount"
                               class="tabular w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label for="deposited_at" class="mb-1 block text-sm font-medium">Date &amp; time</label>
                        <input type="datetime-local" id="deposited_at" wire:model="depositedAt"
                               class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                    </div>
                </div>

                <div class="mb-3 grid grid-cols-2 gap-3">
                    <div>
                        <label for="deposit_type" class="mb-1 block text-sm font-medium">Method</label>
                        <select id="deposit_type" wire:model="depositType"
                                class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                            <option value="CASH">Cash</option>
                            <option value="CHEQUE">Cheque</option>
                        </select>
                    </div>
                    <div>
                        <label for="reference" class="mb-1 block text-sm font-medium">Reference</label>
                        <input type="text" id="reference" wire:model="referenceNumber"
                               placeholder="Auto-generated"
                               class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                    </div>
                </div>

                <div class="mb-4">
                    <label for="reason" class="mb-1 block text-sm font-medium">Reason</label>
                    <input type="text" id="reason" wire:model="reason" placeholder="Evening cash deposit"
                           class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                </div>

                <button type="button" wire:click="deposit"
                        class="w-full rounded-md bg-navy-800 py-2 font-semibold text-white hover:bg-navy-900 dark:bg-navy-700">
                    Record Deposit
                </button>
            </div>
        </div>

        {{-- ================= History ================= --}}
        <div class="lg:col-span-2">
            <div class="rounded-lg border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="border-b border-slate-200 px-4 py-3 text-base font-bold dark:border-slate-800">
                    Deposit History
                </h2>

                <div class="overflow-x-auto">
                    <table class="mb-0 w-full text-sm">
                        <thead class="bg-slate-50 text-xs uppercase dark:bg-slate-800">
                            <tr>
                                <th class="px-4 py-2 text-left">When</th>
                                <th class="px-4 py-2 text-left">Bank</th>
                                <th class="px-4 py-2 text-left">Reference</th>
                                <th class="px-4 py-2 text-right">Amount</th>
                                <th class="px-4 py-2 text-left">Shift</th>
                                <th class="px-4 py-2 text-left">By</th>
                                <th class="px-4 py-2 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($deposits as $deposit)
                                <tr class="border-t border-slate-100 dark:border-slate-800">
                                    <td class="px-4 py-2 whitespace-nowrap text-xs">
                                        {{ $deposit->deposited_at?->format('d M Y H:i') }}
                                    </td>
                                    <td class="px-4 py-2">{{ $deposit->bank_name }}</td>
                                    <td class="px-4 py-2 font-mono text-xs">{{ $deposit->reference_number }}</td>
                                    <td class="tabular px-4 py-2 text-right font-semibold">
                                        {{ number_format((float) $deposit->amount, 2) }}
                                    </td>
                                    <td class="px-4 py-2 text-xs">
                                        {{ $deposit->shift?->shift_number ?? '—' }}
                                    </td>
                                    <td class="px-4 py-2 text-xs">{{ $deposit->depositor?->name ?? '—' }}</td>
                                    <td class="px-4 py-2 text-center">
                                        <span @class([
                                            'rounded px-2 py-0.5 text-xs font-semibold',
                                            'bg-emerald-100 text-emerald-800' => $deposit->status === 'COMPLETED',
                                            'bg-red-100 text-red-800' => $deposit->status === 'REVERSED',
                                            'bg-amber-100 text-amber-800' => $deposit->status === 'PENDING_APPROVAL',
                                        ])>{{ $deposit->status }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                        No bank deposits recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3">{{ $deposits->links() }}</div>
            </div>
        </div>
    </div>
</div>
