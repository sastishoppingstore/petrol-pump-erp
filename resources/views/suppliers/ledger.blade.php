@extends('layouts.app')

@section('title', $supplier->name . ' — ' . __('sales.supplier_ledger.title_suffix'))

@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('suppliers.index') }}">{{ __('sales.supplier_ledger.breadcrumb_suppliers') }}</a></li>
    <li><a href="{{ route('suppliers.show', $supplier) }}">{{ $supplier->name }}</a></li>
    <li class="font-semibold">{{ __('sales.supplier_ledger.breadcrumb_ledger') }}</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>📖 {{ $supplier->name }} — {{ __('sales.supplier_ledger.title_suffix') }}</h1>
        <p>{{ __('sales.supplier_ledger.subtitle') }}</p>
    </div>

    {{-- ================= Header Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- Supplier Info --}}
        <div class="glass-card card-3d p-5 text-center">
            <div class="text-[0.7rem] font-extrabold uppercase tracking-[0.08em] text-slate-500">{{ __('sales.supplier_ledger.supplier_label') }}</div>
            <h2 class="mt-1 text-lg font-bold text-slate-800 dark:text-white">{{ $supplier->name }}</h2>
            <div class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <div>📱 {{ $supplier->phone ?? 'N/A' }}</div>
                <div>🏢 {{ $supplier->company_name ?? 'N/A' }}</div>
                <div>📧 {{ $supplier->email ?? 'N/A' }}</div>
            </div>
        </div>

        {{-- Current Payable --}}
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.supplier_ledger.we_owe') }}</div>
            <div class="stat-value tabular">
                Rs. {{ number_format($supplier->getPayableBalance(), 2) }}
            </div>
            <div class="stat-sub">
                @if ($supplier->getPayableBalance() > 0)
                    <span class="font-bold">🔴 {{ __('sales.supplier_ledger.amount_due') }}</span>
                @else
                    <span class="font-bold">✔ {{ __('sales.supplier_ledger.settled') }}</span>
                @endif
            </div>
        </div>

        {{-- Status --}}
        <div class="stat-tile-3d tilt-3d stat-slate">
            <div class="stat-label">{{ __('sales.supplier_ledger.status_label') }}</div>
            <div class="mt-2 space-y-1.5 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="opacity-85">{{ __('sales.supplier_ledger.active_since') }}</span>
                    <span class="font-bold">{{ $supplier->created_at->format('M Y') }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="opacity-85">{{ __('sales.supplier_ledger.transactions') }}</span>
                    <span class="font-bold">{{ $transactionCount ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Ledger Entries Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 px-4 py-3 text-center dark:border-slate-700/60">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">{{ __('sales.supplier_ledger.history_heading') }}</h3>
        </div>

        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('sales.supplier_ledger.th_date') }}</th>
                        <th>{{ __('sales.supplier_ledger.th_description') }}</th>
                        <th>{{ __('sales.supplier_ledger.th_purchases') }}</th>
                        <th>{{ __('sales.supplier_ledger.th_payments') }}</th>
                        <th>{{ __('sales.supplier_ledger.th_balance') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledger as $entry)
                        <tr>
                            <td class="tabular font-mono text-xs text-slate-600 dark:text-slate-400">
                                {{ $entry['date'] }}
                            </td>
                            <td>
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $entry['description'] }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('sales.supplier_ledger.ref') }} {{ $entry['reference'] ?? 'N/A' }}</div>
                            </td>
                            <td class="tabular font-bold">
                                @if ($entry['type'] === 'purchase')
                                    <span class="text-rose-600 dark:text-rose-400">{{ $entry['amount'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="tabular font-bold">
                                @if ($entry['type'] === 'payment')
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ $entry['amount'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="tabular font-bold" :class="{
                                'text-rose-600 dark:text-rose-400': parseFloat('{{ $entry['balance'] }}') > 0,
                                'text-green-600 dark:text-green-400': parseFloat('{{ $entry['balance'] }}') < 0,
                                'text-slate-600 dark:text-slate-400': parseFloat('{{ $entry['balance'] }}') === 0
                            }">
                                {{ $entry['balance'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-slate-500 dark:text-slate-400">
                                {{ __('sales.supplier_ledger.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= Quick Actions ================= --}}
    <div class="flex flex-col gap-3 sm:flex-row">
        <a href="{{ route('suppliers.show', $supplier) }}" class="btn-3d btn-3d-ghost flex-1">
            ← {{ __('sales.supplier_ledger.back_to_profile') }}
        </a>
        @can('purchases.create')
            <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}" class="btn-3d btn-3d-primary flex-1">
                🚚 {{ __('sales.supplier_ledger.new_purchase') }}
            </a>
        @endcan
        @can('payments.create')
            <a href="{{ route('suppliers.payments.create', $supplier) }}" class="btn-3d btn-3d-success flex-1">
                💳 {{ __('sales.supplier_ledger.record_payment') }}
            </a>
        @endcan
    </div>
</div>
@endsection
