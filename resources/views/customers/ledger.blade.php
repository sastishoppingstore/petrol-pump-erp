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

    {{-- Customer Header with Running Balance --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Customer Info --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 p-4">
            <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Customer</h3>
            <h2 class="text-lg font-bold text-slate-800 dark:text-white mb-2">{{ $customer->name }}</h2>
            <div class="space-y-1 text-sm text-slate-600 dark:text-slate-400">
                <div>📱 {{ $customer->phone }}</div>
                <div>🆔 {{ $customer->cnic ?? 'N/A' }}</div>
                <div class="font-bold text-slate-800 dark:text-slate-200">Type: {{ ucfirst($customer->type) }}</div>
            </div>
        </div>

        {{-- Current Balance --}}
        <div class="bg-gradient-to-br from-amber-50 to-orange-50 dark:from-amber-950 dark:to-orange-950 rounded-lg border border-amber-200 dark:border-amber-800 p-4">
            <h3 class="text-xs font-bold text-amber-700 dark:text-amber-300 uppercase tracking-wider mb-2">Current Balance</h3>
            <div class="text-3xl font-black text-amber-900 dark:text-amber-100 tabular">
                {{ $customer->creditLimitFormatted() }}
            </div>
            <div class="text-xs text-amber-700 dark:text-amber-300 mt-2">
                @if ($customer->credit_limit_balance < 0)
                    <span class="text-red-600 dark:text-red-400 font-bold">🔴 Outstanding</span>
                @elseif ($customer->credit_limit_balance == 0)
                    <span class="text-green-600 dark:text-green-400 font-bold">✔ Settled</span>
                @else
                    <span class="text-blue-600 dark:text-blue-400">💙 Advance</span>
                @endif
            </div>
        </div>

        {{-- Credit Limit --}}
        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 dark:from-blue-950 dark:to-indigo-950 rounded-lg border border-blue-200 dark:border-blue-800 p-4">
            <h3 class="text-xs font-bold text-blue-700 dark:text-blue-300 uppercase tracking-wider mb-2">Credit Limit</h3>
            <div class="text-2xl font-black text-blue-900 dark:text-blue-100 tabular">
                Rs. {{ number_format($customer->credit_limit, 2) }}
            </div>
            <div class="text-xs text-blue-700 dark:text-blue-300 mt-2">
                Available: Rs. {{ number_format(max(0, $customer->credit_limit - abs($customer->credit_limit_balance)), 2) }}
            </div>
        </div>
    </div>

    {{-- Ledger Entries Table --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg border border-slate-200 dark:border-slate-800 overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 dark:border-slate-800">
            <h3 class="text-sm font-bold text-slate-800 dark:text-white">Transaction History</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 dark:bg-slate-800 border-b border-slate-200 dark:border-slate-700">
                    <tr>
                        <th class="px-4 py-2 text-left font-bold text-slate-700 dark:text-slate-300">Date</th>
                        <th class="px-4 py-2 text-left font-bold text-slate-700 dark:text-slate-300">Description</th>
                        <th class="px-4 py-2 text-right font-bold text-slate-700 dark:text-slate-300">Debit (In)</th>
                        <th class="px-4 py-2 text-right font-bold text-slate-700 dark:text-slate-300">Credit (Out)</th>
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
                                @if ($entry['type'] === 'debit')
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ $entry['amount'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular">
                                @if ($entry['type'] === 'credit')
                                    <span class="text-rose-600 dark:text-rose-400">{{ $entry['amount'] }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right font-bold tabular" :class="{
                                'text-red-600 dark:text-red-400': parseFloat('{{ $entry['balance'] }}') < 0,
                                'text-green-600 dark:text-green-400': parseFloat('{{ $entry['balance'] }}') > 0,
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

    {{-- Quick Action: Record Payment --}}
    <div class="flex gap-2">
        <a href="{{ route('customers.show', $customer) }}" class="flex-1 btn btn-sm btn-secondary">
            ← Back to Profile
        </a>
        @can('records.create', \App\Models\CustomerPayment::class)
            <a href="{{ route('customers.payments.create', $customer) }}" class="flex-1 btn btn-sm btn-primary">
                💵 Record Payment
            </a>
        @endcan
    </div>

</div>

<style>
    [x-cloak] { display: none; }
</style>
@endsection
