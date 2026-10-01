@extends('layouts.app')

@section('title', $customer->name . ' — Ledger / کھاتہ')

@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('customers.index') }}">Customers</a></li>
    <li><a href="{{ route('customers.show', $customer) }}">{{ $customer->name }}</a></li>
    <li class="font-semibold">Ledger</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>📖 {{ $customer->name }} — Ledger / کھاتہ</h1>
        <p>Complete transaction history with running balance.</p>
    </div>

    {{-- ================= Header Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- Customer Info --}}
        <div class="glass-card card-3d p-5 text-center">
            <div class="text-[0.7rem] font-extrabold uppercase tracking-[0.08em] text-slate-500">Customer</div>
            <h2 class="mt-1 text-lg font-bold text-slate-800 dark:text-white">{{ $customer->name }}</h2>
            <div class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <div>📱 {{ $customer->phone }}</div>
                <div>🆔 {{ $customer->cnic ?? 'N/A' }}</div>
                <div class="font-bold text-slate-800 dark:text-slate-200">Type: {{ ucfirst($customer->type) }}</div>
            </div>
        </div>

        {{-- Current Balance --}}
        <div class="stat-tile-3d stat-amber">
            <div class="stat-label">Current Balance</div>
            <div class="stat-value tabular">
                {{ $customer->creditLimitFormatted() }}
            </div>
            <div class="stat-sub">
                @if ($customer->credit_limit_balance < 0)
                    <span class="font-bold">🔴 Outstanding</span>
                @elseif ($customer->credit_limit_balance == 0)
                    <span class="font-bold">✔ Settled</span>
                @else
                    <span>💙 Advance</span>
                @endif
            </div>
        </div>

        {{-- Credit Limit --}}
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Credit Limit</div>
            <div class="stat-value tabular">
                Rs. {{ number_format($customer->credit_limit, 2) }}
            </div>
            <div class="stat-sub">
                Available: Rs. {{ number_format(max(0, $customer->credit_limit - abs($customer->credit_limit_balance)), 2) }}
            </div>
        </div>
    </div>

    {{-- ================= Ledger Entries Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 px-4 py-3 text-center dark:border-slate-700/60">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Transaction History</h3>
        </div>

        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Debit (In)</th>
                        <th>Credit (Out)</th>
                        <th>Balance</th>
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
                                <div class="text-xs text-slate-500 dark:text-slate-400">Ref: {{ $entry['reference'] ?? 'N/A' }}</div>
                            </td>
                            <td class="tabular font-bold">
                                @if ($entry['type'] === 'debit')
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ $entry['amount'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="tabular font-bold">
                                @if ($entry['type'] === 'credit')
                                    <span class="text-rose-600 dark:text-rose-400">{{ $entry['amount'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="tabular font-bold" :class="{
                                'text-red-600 dark:text-red-400': parseFloat('{{ $entry['balance'] }}') < 0,
                                'text-green-600 dark:text-green-400': parseFloat('{{ $entry['balance'] }}') > 0,
                                'text-slate-600 dark:text-slate-400': parseFloat('{{ $entry['balance'] }}') === 0
                            }">
                                {{ $entry['balance'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-slate-500 dark:text-slate-400">
                                No transactions found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ================= Quick Actions ================= --}}
    <div class="flex flex-col gap-3 sm:flex-row">
        <a href="{{ route('customers.show', $customer) }}" class="btn-3d btn-3d-ghost flex-1">
            ← Back to Profile
        </a>
        @can('records.create', \App\Models\CustomerPayment::class)
            <a href="{{ route('customers.payments.create', $customer) }}" class="btn-3d btn-3d-primary flex-1">
                💵 Record Payment
            </a>
        @endcan
    </div>
</div>
@endsection
