@extends('layouts.app')

@section('title', __('finance.banks.title'))
@section('breadcrumb')
    <li class="text-slate-500">{{ __('finance.common.banks_word') }}</li>
@endsection

@section('content')
<div x-data="{ transferModal: false, withdrawModal: false, selectedAccountId: null, selectedAccountTitle: '' }" class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>{{ __('finance.banks.heading') }}</h1>
        <p>{{ __('finance.banks.subheading') }}</p>
        @can('cash.create')
            <div class="page-actions">
                <button @click="transferModal = true" class="btn-3d btn-3d-navy">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                    {{ __('finance.banks.bank_transfer') }}
                </button>
                <a href="{{ route('bank-deposits.index') }}" class="btn-3d btn-3d-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('finance.banks.deposit_cash') }}
                </a>
                <a href="{{ route('bank-accounts.create') }}" class="btn-3d btn-3d-success">
                    {{ __('finance.banks.add_account') }}
                </a>
            </div>
        @endcan
    </div>

    {{-- Top Summary Stats --}}
    @php
        $totalBalance = collect($balances ?? [])->sum(fn ($b) => (float) $b);
    @endphp
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('finance.banks.total_balance') }}</div>
            <div class="stat-value tabular">Rs. {{ number_format($totalBalance, 2) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalBalance) }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('finance.banks.active_accounts') }}</div>
            <div class="stat-value tabular">{{ $accounts->where('status', 'ACTIVE')->count() }} <span class="text-sm font-normal opacity-80">{{ __('finance.banks.accounts_word') }}</span></div>
            <div class="stat-sub">{{ __('finance.banks.current_savings') }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-slate">
            <div class="stat-label">{{ __('finance.banks.scheduled_banks') }}</div>
            <div class="stat-value tabular">{{ \App\Models\Bank::count() }} <span class="text-sm font-normal opacity-80">{{ __('finance.banks.sbp_licensed') }}</span></div>
            <div class="stat-sub">{{ __('finance.banks.types_line') }}</div>
        </div>
    </div>

    {{-- Station's Accounts Table --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 px-5 py-4 text-center dark:border-slate-700/60">
            <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ __('finance.banks.station_accounts') }}</h2>
            <span class="text-xs text-slate-500">{{ $accounts->total() }} {{ __('finance.banks.total_registered') }}</span>
        </div>
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('finance.banks.bank_name') }}</th>
                        <th>{{ __('finance.banks.account_title') }}</th>
                        <th>{{ __('finance.banks.account_no_iban') }}</th>
                        <th>{{ __('finance.common.type') }}</th>
                        <th>{{ __('finance.banks.ledger_balance') }}</th>
                        <th>{{ __('finance.common.status') }}</th>
                        <th>{{ __('finance.common.actions') }}</th>
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
                                Rs. {{ number_format((float) ($balances[$account->id] ?? $account->opening_balance), 2) }}
                            </td>
                            <td>
                                <span class="pill-status {{ $account->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }}"><span class="dot"></span>{{ $account->status }}</span>
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('banks.book', $account) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                        {{ __('finance.banks.bank_book') }}
                                    </a>
                                    <a href="{{ route('banks.reconciliation', $account) }}" class="btn-3d btn-3d-navy btn-3d-sm">
                                        {{ __('finance.banks.reconcile') }}
                                    </a>
                                    <button type="button" @click="selectedAccountId = {{ $account->id }}; selectedAccountTitle = '{{ addslashes($account->account_title) }}'; withdrawModal = true" class="btn-3d btn-3d-amber btn-3d-sm">
                                        {{ __('finance.banks.withdraw') }}
                                    </button>
                                    @can('cash.create')
                                        <a href="{{ route('bank-accounts.edit', $account) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                            {{ __('ui.actions.edit') }}
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-center text-slate-500">
                                {{ __('finance.banks.empty_accounts') }}
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
        <h2 class="mb-4 text-center text-base font-bold text-slate-900 dark:text-white">{{ __('finance.banks.directory_heading') }}</h2>
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
        <a href="{{ route('bank-accounts.create') }}" class="fab-3d" title="{{ __('finance.banks.fab_title') }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        </a>
    @endcan

    {{-- Withdrawal Modal --}}
    <div x-show="withdrawModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="withdrawModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-md p-6">
                <div class="border-b border-slate-200 pb-3 text-center dark:border-slate-700">
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('finance.banks.withdraw_heading') }}</h3>
                    <button @click="withdrawModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form :action="'/bank-accounts/' + selectedAccountId + '/withdraw'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.from_account') }}</label>
                        <input type="text" :value="selectedAccountTitle" disabled class="input-3d w-full text-center text-sm font-semibold">
                    </div>
                    <div class="field-3d">
                        <label for="withdraw_amount" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.amount_rs') }} *</label>
                        <input type="number" step="0.01" min="1" id="withdraw_amount" name="amount" required placeholder="{{ __('finance.banks.ph_50000') }}" class="input-3d tabular w-full text-center font-mono text-sm">
                    </div>
                    <div class="field-3d">
                        <label for="withdraw_reason" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.reason_purpose') }} *</label>
                        <input type="text" id="withdraw_reason" name="reason" required placeholder="{{ __('finance.banks.ph_reason') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label for="withdraw_reference" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.cheque_slip_ref') }}</label>
                        <input type="text" id="withdraw_reference" name="reference" placeholder="{{ __('finance.banks.ph_cheque_ref') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="withdrawModal = false" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-amber">{{ __('finance.banks.withdraw_cash') }}</button>
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
                    <h3 class="text-base font-bold text-slate-900 dark:text-white">{{ __('finance.banks.transfer_heading') }}</h3>
                    <button @click="transferModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">&times;</button>
                </div>
                <form action="{{ route('banks.transfer') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.from_account_source') }} *</label>
                        <select name="from_account_id" required class="input-3d w-full text-center text-sm">
                            <option value="">{{ __('finance.banks.select_source') }}</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }} ({{ __('finance.banks.bal') }}: Rs. {{ number_format((float) ($balances[$acc->id] ?? $acc->opening_balance), 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.to_account_dest') }} *</label>
                        <select name="to_account_id" required class="input-3d w-full text-center text-sm">
                            <option value="">{{ __('finance.banks.select_dest') }}</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="field-3d">
                            <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.transfer_amount') }} *</label>
                            <input type="number" step="0.01" min="1" name="amount" required placeholder="{{ __('finance.banks.ph_100000') }}" class="input-3d tabular w-full text-center font-mono text-sm">
                        </div>
                        <div class="field-3d">
                            <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.transfer_charges') }}</label>
                            <input type="number" step="0.01" min="0" name="charges" value="0.00" class="input-3d tabular w-full text-center font-mono text-sm">
                        </div>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.banks.reference_ft') }}</label>
                        <input type="text" name="reference" placeholder="{{ __('finance.banks.ph_ft') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.common.notes') }}</label>
                        <input type="text" name="notes" placeholder="{{ __('finance.banks.ph_notes') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="transferModal = false" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-navy">{{ __('finance.banks.transfer_funds') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
