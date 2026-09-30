@extends('layouts.app')

@section('title', 'Banks & Accounts')
@section('breadcrumb')
    <li class="text-slate-500">Banks</li>
@endsection

@section('content')
<div x-data="{ transferModal: false, withdrawModal: false, selectedAccountId: null, selectedAccountTitle: '' }" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Pakistani Banking &amp; Accounts</h1>
            <p class="mt-1 text-sm text-slate-500">
                Manage Pakistani scheduled banks, station accounts, cash deposits, inter-bank transfers &amp; reconciliation.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            @can('cash.create')
                <button @click="transferModal = true" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Bank Transfer
                </button>
                <a href="{{ route('bank-deposits.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Deposit Cash
                </a>
                <a href="{{ route('bank-accounts.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-navy-900 dark:bg-navy-700">
                    Add Account
                </a>
            @endcan
        </div>
    </div>

    {{-- Top Summary Stats --}}
    @php
        $totalBalance = $accounts->sum(fn ($a) => (float) $a->currentBalance());
    @endphp
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Liquid Bank Balance</span>
            <div class="mt-2 font-mono text-2xl font-bold text-slate-900 dark:text-white">
                Rs. {{ number_format($totalBalance, 2) }}
            </div>
            <div class="mt-1 text-xs text-red-600 font-medium">
                {{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalBalance) }}
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Station Accounts</span>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                {{ $accounts->where('status', 'ACTIVE')->count() }} <span class="text-sm font-normal text-slate-400">accounts</span>
            </div>
            <div class="mt-1 text-xs text-slate-400">Current &amp; Savings in Pakistani Banks</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pakistani Scheduled Banks</span>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                {{ \App\Models\Bank::count() }} <span class="text-sm font-normal text-slate-400">SBP licensed</span>
            </div>
            <div class="mt-1 text-xs text-slate-400">Commercial, Islamic, Public, Digital</div>
        </div>
    </div>

    {{-- Station's Accounts Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 dark:border-slate-800">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Station Bank Accounts</h2>
            <span class="text-xs text-slate-500">{{ $accounts->total() }} total registered</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Bank Name</th>
                        <th class="px-5 py-3">Account Title</th>
                        <th class="px-5 py-3">Account No. / IBAN</th>
                        <th class="px-5 py-3">Type</th>
                        <th class="px-5 py-3 text-right">Ledger Balance</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($accounts as $account)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="px-5 py-3.5">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $account->bank?->name }}</div>
                                <div class="text-xs text-slate-400">{{ $account->bank?->short_name }} &bull; {{ $account->bank?->bank_type }}</div>
                            </td>
                            <td class="px-5 py-3.5 font-medium text-slate-800 dark:text-slate-200">
                                {{ $account->account_title }}
                            </td>
                            <td class="px-5 py-3.5 font-mono text-xs text-slate-600 dark:text-slate-300">
                                <div>{{ $account->account_number }}</div>
                                @if ($account->iban)
                                    <div class="text-[11px] text-slate-400">{{ $account->iban }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-3.5">
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $account->account_type }}
                                </span>
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $account->currentBalance(), 2) }}
                            </td>
                            <td class="px-5 py-3.5 text-center">
                                <span @class([
                                    'rounded px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' => $account->isActive(),
                                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => ! $account->isActive(),
                                ])>{{ $account->status }}</span>
                            </td>
                            <td class="whitespace-nowrap px-5 py-3.5 text-right text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('banks.book', $account) }}" class="rounded bg-slate-100 px-2.5 py-1 font-medium text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200">
                                        Bank Book
                                    </a>
                                    <a href="{{ route('banks.reconciliation', $account) }}" class="rounded bg-sky-50 px-2.5 py-1 font-medium text-sky-700 hover:bg-sky-100 dark:bg-sky-900/30 dark:text-sky-300">
                                        Reconcile
                                    </a>
                                    <button type="button" @click="selectedAccountId = {{ $account->id }}; selectedAccountTitle = '{{ addslashes($account->account_title) }}'; withdrawModal = true" class="rounded bg-amber-50 px-2.5 py-1 font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300">
                                        Withdraw
                                    </button>
                                    @can('cash.create')
                                        <a href="{{ route('bank-accounts.edit', $account) }}" class="rounded border border-slate-300 px-2.5 py-1 font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                                            Edit
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-10 text-center text-slate-500">
                                No bank accounts set up yet. Add an account to start recording deposits.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-5 py-3 border-t border-slate-100 dark:border-slate-800">{{ $accounts->links() }}</div>
    </div>

    {{-- Bank Reference Directory --}}
    <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <h2 class="mb-4 text-base font-bold text-slate-900 dark:text-white">State Bank of Pakistan Scheduled Banks Directory (25+)</h2>
        <div class="space-y-6">
            @foreach ($banks as $group => $groupBanks)
                <div>
                    <h3 class="mb-3 text-xs font-bold uppercase tracking-wider text-red-600 dark:text-red-400">
                        {{ $group }} ({{ $groupBanks->count() }})
                    </h3>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($groupBanks as $bank)
                            <div class="flex items-center justify-between rounded-lg border border-slate-100 bg-slate-50/50 px-3 py-2 text-xs dark:border-slate-800 dark:bg-slate-800/40">
                                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $bank->name }}</span>
                                <span class="font-mono rounded bg-white px-1.5 py-0.5 font-bold text-slate-600 shadow-xs dark:bg-slate-700 dark:text-slate-300">
                                    {{ $bank->short_name }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Withdrawal Modal --}}
    <div x-show="withdrawModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="withdrawModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Bank Withdrawal (کیش نکلوائیں)</h3>
                    <button @click="withdrawModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/bank-accounts/' + selectedAccountId + '/withdraw'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">From Account</label>
                        <input type="text" :value="selectedAccountTitle" disabled class="w-full rounded-lg border-slate-200 bg-slate-100 text-sm font-semibold dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label for="withdraw_amount" class="block text-xs font-semibold uppercase text-slate-500">Amount (Rs.) *</label>
                        <input type="number" step="0.01" min="1" id="withdraw_amount" name="amount" required placeholder="e.g. 50000" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label for="withdraw_reason" class="block text-xs font-semibold uppercase text-slate-500">Reason / Purpose *</label>
                        <input type="text" id="withdraw_reason" name="reason" required placeholder="e.g. Cash replenishment for till float" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label for="withdraw_reference" class="block text-xs font-semibold uppercase text-slate-500">Cheque / Slip Ref #</label>
                        <input type="text" id="withdraw_reference" name="reference" placeholder="e.g. CHQ-89021" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="withdrawModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Withdraw Cash</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Transfer Modal --}}
    <div x-show="transferModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="transferModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Inter-Bank Transfer (بینک ٹرانسفر)</h3>
                    <button @click="transferModal = false" class="text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form action="{{ route('banks.transfer') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">From Account (Source) *</label>
                        <select name="from_account_id" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Select source account…</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }} (Bal: Rs. {{ number_format((float) $acc->currentBalance(), 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">To Account (Destination) *</label>
                        <select name="to_account_id" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Select destination account…</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500">Transfer Amount (Rs.) *</label>
                            <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 100000" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold uppercase text-slate-500">Bank Transfer Charges (Rs.)</label>
                            <input type="number" step="0.01" min="0" name="charges" value="0.00" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Reference / FT Number</label>
                        <input type="text" name="reference" placeholder="e.g. 1LINK-FT-89320" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Notes</label>
                        <input type="text" name="notes" placeholder="e.g. Fund transfer for PSO product indent payment" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="transferModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-900">Transfer Funds</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
