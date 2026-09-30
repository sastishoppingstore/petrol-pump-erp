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

    {{-- Supplier Header with Payable Balance --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Supplier Info --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Supplier</h3>
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-2">{{ $supplier->name }}</h2>
            <div class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <div>📱 {{ $supplier->phone ?? 'N/A' }}</div>
                <div>🏢 {{ $supplier->company_name ?? 'N/A' }}</div>
                <div>📧 {{ $supplier->email ?? 'N/A' }}</div>
            </div>
        </div>

        {{-- Current Payable --}}
        <div class="bg-gradient-to-br from-rose-50 to-red-50 dark:from-rose-950 dark:to-red-950 rounded-lg border border-rose-200 dark:border-rose-800 p-4">
            <h3 class="text-xs font-bold text-rose-700 dark:text-rose-300 uppercase tracking-wider mb-2">We Owe (Payable)</h3>
            <div class="text-3xl font-black text-rose-900 dark:text-rose-100 tabular">
                Rs. {{ number_format($supplier->getPayableBalance(), 2) }}
            </div>
            <div class="text-xs text-rose-700 dark:text-rose-300 mt-2">
                @if ($supplier->getPayableBalance() > 0)
                    <span class="font-bold">🔴 Amount Due</span>
                @else
                    <span class="text-green-600 dark:text-green-400 font-bold">✔ Settled</span>
                @endif
            </div>
        </div>

        {{-- Recent Transactions --}}
        <div class="bg-gradient-to-br from-slate-50 to-slate-100 dark:from-slate-800 dark:to-slate-900 rounded-lg border border-slate-200 dark:border-slate-700 p-4">
            <h3 class="text-xs font-bold text-slate-700 dark:text-slate-300 uppercase tracking-wider mb-2">Status</h3>
            <div class="space-y-1 text-sm">
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Active Since:</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $supplier->created_at->format('M Y') }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-600 dark:text-slate-400">Transactions:</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $transactionCount ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Ledger Entries Table --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Purchase & Payment History</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-bold text-slate-700 dark:text-slate-300">Date</th>
                        <th class="px-4 py-2 text-left font-bold text-slate-700 dark:text-slate-300">Description</th>
                        <th class="px-4 py-2 text-right font-bold text-slate-700 dark:text-slate-300">Purchases (We Owe)</th>
                        <th class="px-4 py-2 text-right font-bold text-slate-700 dark:text-slate-300">Payments (We Paid)</th>
                        <th class="px-4 py-2 text-right font-bold text-slate-700 dark:text-slate-300">Balance</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($ledger as $entry)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                            <td class="px-4 py-3 font-mono text-xs text-slate-600 dark:text-slate-400">
                                {{ $entry['date'] }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $entry['description'] }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">Ref: {{ $entry['reference'] ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular">
                                @if ($entry['type'] === 'purchase')
                                    <span class="text-rose-600 dark:text-rose-400">{{ $entry['amount'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular">
                                @if ($entry['type'] === 'payment')
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ $entry['amount'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular" :class="{
                                'text-rose-600 dark:text-rose-400': parseFloat('{{ $entry['balance'] }}') > 0,
                                'text-green-600 dark:text-green-400': parseFloat('{{ $entry['balance'] }}') < 0,
                                'text-slate-600 dark:text-slate-400': parseFloat('{{ $entry['balance'] }}') === 0
                            }">
                                {{ $entry['balance'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-8 text-center text-slate-500 dark:text-slate-400">
                                No transactions found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Quick Actions --}}
    <div class="flex gap-2">
        <a href="{{ route('suppliers.show', $supplier) }}" class="flex-1 btn btn-sm btn-secondary">
            ← Back to Profile
        </a>
        @can('purchases.create')
            <a href="{{ route('purchases.create', ['supplier_id' => $supplier->id]) }}" class="flex-1 btn btn-sm btn-primary">
                🚚 New Purchase
            </a>
        @endcan
        @can('payments.create')
            <a href="{{ route('suppliers.payments.create', $supplier) }}" class="flex-1 btn btn-sm btn-success">
                💳 Record Payment
            </a>
        @endcan
    </div>

</div>

<style>
    [x-cloak] { display: none; }
</style>
@endsection
