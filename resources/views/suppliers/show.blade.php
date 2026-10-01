@extends('layouts.app')

@section('title', $supplier->name . ' — ' . __('sales.supplier_show.ledger_suffix'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('suppliers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('sales.supplier_show.breadcrumb_suppliers') }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ $supplier->code }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>{{ $supplier->name }}</h1>
        <p>
            <span class="inline-block rounded-md bg-red-100 px-2 py-0.5 align-middle font-mono text-xs font-bold text-red-800 dark:bg-red-950/60 dark:text-red-300">{{ $supplier->code }}</span>
            <span class="pill-status {{ $supplier->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }} align-middle">
                <span class="dot" aria-hidden="true"></span>{{ $supplier->status }}
            </span>
        </p>
        <p>{{ __('sales.supplier_show.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('purchases.create') }}?supplier_id={{ $supplier->id }}" class="btn-3d btn-3d-primary">
                <span aria-hidden="true">📥</span> {{ __('sales.supplier_show.new_decantation') }}
            </a>
            <a href="{{ route('suppliers.statement', $supplier) }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">📄</span> {{ __('sales.supplier_show.statement') }}
            </a>
            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">✏️</span> {{ __('ui.actions.edit') }}
            </a>
        </div>
    </div>

    {{-- ================= Info Cards ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.supplier_show.current_balance') }}</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($supplier->current_balance) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords($supplier->current_balance) }}</div>
        </div>

        <div class="glass-card card-3d p-5">
            <div class="text-center text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('sales.supplier_show.tax_identity') }}</div>
            <dl class="mt-3 space-y-1.5 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('sales.supplier_show.ntn') }}</dt>
                    <dd class="font-mono font-bold text-slate-900 dark:text-white">{{ $supplier->ntn_number ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('sales.supplier_show.strn') }}</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $supplier->strn_number ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">{{ __('sales.supplier_show.contact_person') }}</dt>
                    <dd class="text-slate-900 dark:text-white">{{ $supplier->contact_person ?? 'Direct' }}</dd>
                </div>
            </dl>
        </div>

        <div class="glass-card card-3d p-5 text-center">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ __('sales.supplier_show.terminal_contact') }}</div>
            <div class="mt-2 text-xs text-slate-700 dark:text-slate-300">
                <div><strong>{{ __('sales.supplier_show.phone_label') }}</strong> {{ $supplier->phone ?? 'N/A' }}</div>
                <div class="mt-1"><strong>{{ __('sales.supplier_show.email_label') }}</strong> {{ $supplier->email ?? 'N/A' }}</div>
                <div class="mt-1"><strong>{{ __('sales.supplier_show.address_label') }}</strong> {{ $supplier->address ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    {{-- ================= Supplier Payment Form ================= --}}
    @if(auth()->user()->hasPermission(\App\Support\PermissionList::SUPPLIER_PAYMENT))
        <div class="glass-card p-5" x-data="{ method: 'BANK_TRANSFER' }">
            <div class="border-b border-slate-200/70 pb-3 text-center dark:border-slate-700/60">
                <h2 class="flex items-center justify-center gap-2 text-base font-bold text-slate-900 dark:text-white">
                    <span aria-hidden="true">💳</span> {{ __('sales.supplier_show.payment_heading') }}
                </h2>
                <p class="text-xs text-slate-500">{{ __('sales.supplier_show.payment_subtitle') }}</p>
            </div>

            <form method="POST" action="{{ route('suppliers.payments.store', $supplier) }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
                @csrf
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.supplier_show.payment_date') }}</label>
                    <input type="date" name="payment_date" value="{{ today()->toDateString() }}" required
                           class="input-3d text-center text-xs">
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.supplier_show.payment_method') }}</label>
                    <select name="payment_method" x-model="method" required
                            class="input-3d text-center text-xs">
                        <option value="BANK_TRANSFER">{{ __('sales.supplier_show.method_bank') }}</option>
                        <option value="CHEQUE">{{ __('sales.supplier_show.method_cheque') }}</option>
                        <option value="CASH">{{ __('sales.supplier_show.method_cash') }}</option>
                    </select>
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.supplier_show.amount_label') }}</label>
                    <input type="number" step="0.01" name="amount" required placeholder="{{ __('sales.supplier_show.amount_placeholder') }}"
                           class="input-3d text-center text-xs font-bold text-slate-900 dark:text-white">
                </div>

                <div class="field-3d" x-show="method === 'BANK_TRANSFER'">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.supplier_show.bank_account_label') }}</label>
                    <select name="bank_account_id" class="input-3d text-center text-xs">
                        <option value="">{{ __('sales.supplier_show.select_bank') }}</option>
                        @foreach($bankAccounts as $acc)
                            <option value="{{ $acc->id }}">{{ $acc->bank_name ?? 'Bank' }} - {{ $acc->account_number }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field-3d" x-show="method === 'CHEQUE'">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.supplier_show.cheque_label') }}</label>
                    <input type="text" name="cheque_number" placeholder="{{ __('sales.supplier_show.cheque_placeholder') }}"
                           class="input-3d text-center font-mono text-xs">
                </div>

                <div class="field-3d sm:col-span-3">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.supplier_show.notes_label') }}</label>
                    <input type="text" name="notes" placeholder="{{ __('sales.supplier_show.notes_placeholder') }}"
                           class="input-3d text-center text-xs">
                </div>

                <div class="flex items-end">
                    <button type="submit" class="btn-3d btn-3d-success w-full text-xs">
                        {{ __('sales.supplier_show.confirm_payment') }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    {{-- ================= Supplier Ledger Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="flex flex-col items-center justify-between gap-2 border-b border-slate-200/70 p-5 text-center dark:border-slate-700/60 sm:flex-row sm:text-left">
            <div>
                <h2 class="flex items-center justify-center gap-2 text-base font-bold text-slate-900 dark:text-white sm:justify-start">
                    <span aria-hidden="true">📖</span> {{ __('sales.supplier_show.ledger_heading') }}
                </h2>
                <p class="text-xs text-slate-500">{{ __('sales.supplier_show.ledger_subtitle') }}</p>
            </div>
            <a href="{{ route('suppliers.statement', $supplier) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                {{ __('sales.supplier_show.view_statement') }} →
            </a>
        </div>

        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>{{ __('sales.supplier_show.th_date') }}</th>
                        <th>{{ __('sales.supplier_show.th_description') }}</th>
                        <th>{{ __('sales.supplier_show.th_debit') }}</th>
                        <th>{{ __('sales.supplier_show.th_credit') }}</th>
                        <th>{{ __('sales.supplier_show.th_balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgerEntries as $entry)
                        <tr>
                            <td class="tabular font-mono">{{ $entry->date->format('d/m/Y') }}</td>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $entry->description }}</div>
                                @if($entry->reference_type)
                                    <div class="text-[10px] text-slate-400">{{ __('sales.supplier_show.ref') }} {{ class_basename($entry->reference_type) }} #{{ $entry->reference_id }}</div>
                                @endif
                            </td>
                            <td class="tabular font-medium text-emerald-600 dark:text-emerald-400">
                                {{ \App\Support\Money::compare($entry->debit, '0.00') > 0 ? \App\Support\PakistaniCurrency::format($entry->debit, false) : '-' }}
                            </td>
                            <td class="tabular font-medium text-red-600 dark:text-red-400">
                                {{ \App\Support\Money::compare($entry->credit, '0.00') > 0 ? \App\Support\PakistaniCurrency::format($entry->credit, false) : '-' }}
                            </td>
                            <td class="tabular font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\PakistaniCurrency::format($entry->running_balance) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-slate-500">
                                {{ __('sales.supplier_show.no_ledger') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($ledgerEntries->hasPages())
            <div class="border-t border-slate-200/70 p-4 dark:border-slate-700/60">
                {{ $ledgerEntries->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
