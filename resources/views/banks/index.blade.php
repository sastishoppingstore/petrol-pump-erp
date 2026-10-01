@extends('layouts.app')

@section('title', 'Banks & Accounts')
@section('breadcrumb')
    <li class="text-slate-500">Banks</li>
@endsection

@section('content')
<div x-data="{ transferModal: false, withdrawModal: false, selectedAccountId: null, selectedAccountTitle: '' }" class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>Pakistani Banking &amp; Accounts</h1>
        <p>Manage Pakistani scheduled banks, station accounts, cash deposits, inter-bank transfers &amp; reconciliation.</p>
        @can('cash.create')
            <div class="page-actions">
                <button @click="transferModal = true" class="btn-3d btn-3d-navy">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    Bank Transfer
                </button>
                <a href="{{ route('bank-deposits.index') }}" class="btn-3d btn-3d-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Deposit Cash
                </a>
                <a href="{{ route('bank-accounts.create') }}" class="btn-3d btn-3d-success">
                    Add Account
                </a>
            </div>
        @endcan
    </div>

    {{-- Top Summary Stats --}}
    @php
        $totalBalance = $accounts->sum(fn ($a) => (float) $a->currentBalance());
    @endphp
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Total Liquid Bank Balance</div>
            <div class="stat-value tabular">Rs. {{ number_format($totalBalance, 2) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalBalance) }}</div>
        </div>
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Active Station Accounts</div>
            <div class="stat-value tabular">{{ $accounts->where('status', 'ACTIVE')->count() }} <span class="text-sm font-normal opacity-80">accounts</span></div>
            <div class="stat-sub">Current &amp; Savings in Pakistani Banks</div>
        </div>
        <div class="stat-tile-3d stat-slate">
            <div class="stat-label">Pakistani Scheduled Banks</div>
            <div class="stat-value tabular">{{ \App\Models\Bank::count() }} <span class="text-sm font-normal opacity-80">SBP licensed</span></div>
            <div class="stat-sub">Commercial, Islamic, Public, Digital</div>
        </div>
    </div>

    {{-- Station's Accounts Table --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 px-5 py-4 text-center dark:border-slate-700/60">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">Station Bank Accounts</h2>
            <span class="text-xs text-slate-500">{{ $accounts->total() }} total registered</span>
        </div>
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Bank Name</th>
                        <th>Account Title</th>
                        <th>Account No. / IBAN</th>
                        <th>Type</th>
                        <th>Ledger Balance</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr>
                            <td>
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $account->bank?->name }}</div>
                                <div class="text-xs text-slate-400">{{ $account->bank?->short_name }} &bull; {{ $account->bank?->bank_type }}</div>
                            </td>
                            <td class="font-medium text-slate-800 dark:text-slate-200">
                                {{ $account->account_title }}
                            </td>
                            <td class="font-mono text-xs text-slate-600 dark:text-slate-300">
                                <div>{{ $account->account_number }}</div>
                                @if ($account->iban)
                                    <div class="text-[11px] text-slate-400">{{ $account->iban }}</div>
                                @endif
                            </td>
                            <td>
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $account->account_type }}
                                </span>
                            </td>
                            <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $account->currentBalance(), 2) }}
                            </td>
                            <td>
                                <span class="pill-status {{ $account->isActive() ? 'pill-active' : 'pill-inactive' }}"><span class="dot"></span>{{ $account->status }}</span>
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('banks.book', $account) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                        Bank Book
                                    </a>
                                    <a href="{{ route('banks.reconciliation', $account) }}" class="btn-3d btn-3d-navy btn-3d-sm">
                                        Reconcile
                                    </a>
                                    <button type="button" @click="selectedAccountId = {{ $account->id }}; selectedAccountTitle = '{{ addslashes($account->account_title) }}'; withdrawModal = true" class="btn-3d btn-3d-amber btn-3d-sm">
                                        Withdraw
                                    </button>
                                    @can('cash.create')
                                        <a href="{{ route('bank-accounts.edit', $account) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                            Edit
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-500">
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
    <div class="glass-card p-5">
        <h2 class="mb-4 text-center text-base font-bold text-slate-900 dark:text-white">State Bank of Pakistan Scheduled Banks Directory (25+)</h2>
        <div class="space-y-6">
            @foreach ($banks as $group => $groupBanks)
                <div>
                    <h3 class="mb-3 text-center text-xs font-bold uppercase tracking-wider text-vital-primary">
                        {{ $group }} ({{ $groupBanks->count() }})
                    </h3>
                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($groupBanks as $bank)
                            <div class="flex items-center justify-between rounded-xl border border-white/60 bg-white/50 px-3 py-2 text-xs shadow-sm dark:border-slate-700 dark:bg-slate-800/40">
                                <span class="font-medium text-slate-800 dark:text-slate-200">{{ $bank->name }}</span>
                                <span class="font-mono rounded bg-white px-1.5 py-0.5 font-bold text-slate-600 shadow-sm dark:bg-slate-700 dark:text-slate-300">
                                    {{ $bank->short_name }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @can('cash.create')
        <a href="{{ route('bank-accounts.create') }}" class="fab-3d" title="Add a new bank account">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        </a>
    @endcan

    {{-- Withdrawal Modal --}}
    <div x-show="withdrawModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="withdrawModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-md p-6">
                <div class="border-b border-slate-200 pb-3 text-center dark:border-slate-700">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Bank Withdrawal (کیش نکلوائیں)</h3>
                    <button @click="withdrawModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/bank-accounts/' + selectedAccountId + '/withdraw'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">From Account</label>
                        <input type="text" :value="selectedAccountTitle" disabled class="input-3d w-full text-center text-sm font-semibold">
                    </div>
                    <div class="field-3d">
                        <label for="withdraw_amount" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Amount (Rs.) *</label>
                        <input type="number" step="0.01" min="1" id="withdraw_amount" name="amount" required placeholder="e.g. 50000" class="input-3d tabular w-full text-center font-mono text-sm">
                    </div>
                    <div class="field-3d">
                        <label for="withdraw_reason" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Reason / Purpose *</label>
                        <input type="text" id="withdraw_reason" name="reason" required placeholder="e.g. Cash replenishment for till float" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label for="withdraw_reference" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Cheque / Slip Ref #</label>
                        <input type="text" id="withdraw_reference" name="reference" placeholder="e.g. CHQ-89021" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="withdrawModal = false" class="btn-3d btn-3d-ghost">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-amber">Withdraw Cash</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Transfer Modal --}}
    <div x-show="transferModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="transferModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-lg p-6">
                <div class="border-b border-slate-200 pb-3 text-center dark:border-slate-700">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">Inter-Bank Transfer (بینک ٹرانسفر)</h3>
                    <button @click="transferModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form action="{{ route('banks.transfer') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">From Account (Source) *</label>
                        <select name="from_account_id" required class="input-3d w-full text-center text-sm">
                            <option value="">Select source account…</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }} (Bal: Rs. {{ number_format((float) $acc->currentBalance(), 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">To Account (Destination) *</label>
                        <select name="to_account_id" required class="input-3d w-full text-center text-sm">
                            <option value="">Select destination account…</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="field-3d">
                            <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Transfer Amount (Rs.) *</label>
                            <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 100000" class="input-3d tabular w-full text-center font-mono text-sm">
                        </div>
                        <div class="field-3d">
                            <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Bank Transfer Charges (Rs.)</label>
                            <input type="number" step="0.01" min="0" name="charges" value="0.00" class="input-3d tabular w-full text-center font-mono text-sm">
                        </div>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Reference / FT Number</label>
                        <input type="text" name="reference" placeholder="e.g. 1LINK-FT-89320" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Notes</label>
                        <input type="text" name="notes" placeholder="e.g. Fund transfer for PSO product indent payment" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="transferModal = false" class="btn-3d btn-3d-ghost">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-navy">Transfer Funds</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
