@extends('layouts.app')

@section('title', $supplier->name . ' — Ledger / کھاتہ')

@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('suppliers.index') }}">Suppliers</a></li>
    <li><a href="{{ route('suppliers.show', $supplier) }}">{{ $supplier->name }}</a></li>
    <li class="font-semibold">Ledger</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>📖 {{ $supplier->name }} — Ledger / کھاتہ</h1>
        <p>Purchase &amp; payment history with running payable balance.</p>
    </div>

    {{-- ================= Header Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        {{-- Supplier Info --}}
        <div class="glass-card card-3d p-5 text-center">
            <div class="text-[0.7rem] font-extrabold uppercase tracking-[0.08em] text-slate-500">Supplier</div>
            <h2 class="mt-1 text-lg font-bold text-slate-800 dark:text-white">{{ $supplier->name }}</h2>
            <div class="mt-2 space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <div>📱 {{ $supplier->phone ?? 'N/A' }}</div>
                <div>🏢 {{ $supplier->company_name ?? 'N/A' }}</div>
                <div>📧 {{ $supplier->email ?? 'N/A' }}</div>
            </div>
        </div>

        {{-- Current Payable --}}
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">We Owe (Payable)</div>
            <div class="stat-value tabular">
                Rs. {{ number_format($supplier->getPayableBalance(), 2) }}
            </div>
            <div class="stat-sub">
                @if ($supplier->getPayableBalance() > 0)
                    <span class="font-bold">🔴 Amount Due</span>
                @else
                    <span class="font-bold">✔ Settled</span>
                @endif
            </div>
        </div>

        {{-- Status --}}
        <div class="stat-tile-3d stat-slate">
            <div class="stat-label">Status</div>
            <div class="mt-2 space-y-1.5 text-sm">
                <div class="flex items-center justify-between gap-3">
                    <span class="opacity-85">Active Since:</span>
                    <span class="font-bold">{{ $supplier->created_at->format('M Y') }}</span>
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="opacity-85">Transactions:</span>
                    <span class="font-bold">{{ $transactionCount ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Ledger Entries Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 px-4 py-3 text-center dark:border-slate-700/60">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Purchase &amp; Payment History</h3>
        </div>

        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Description</th>
                        <th>Purchases (We Owe)</th>
                        <th>Payments (We Paid)</th>
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
        <a href="{{ route('suppliers.show', $supplier) }}" class="btn-3d btn-3d-ghost flex-1">
            ← Back to Profile
        </a>
        @can('purchases.create')
            <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}" class="btn-3d btn-3d-primary flex-1">
                🚚 New Purchase
            </a>
        @endcan
        @can('payments.create')
            <a href="{{ route('suppliers.payments.create', $supplier) }}" class="btn-3d btn-3d-success flex-1">
                💳 Record Payment
            </a>
        @endcan
    </div>
</div>
@endsection
