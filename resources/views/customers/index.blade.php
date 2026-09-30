@extends('layouts.app')

@section('title', 'Customers & Udhaar (Tile 3)')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Customers</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header & KPI Bar --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <span class="text-red-600">🧑</span> Customers & Udhaar Management
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Customer credit accounts, vehicle fleet registration, credit limits, and running balance ledgers.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('customers.ageing') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-amber-300 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 hover:bg-amber-100 transition shadow-sm">
                <span>⏱️</span> Udhaar Ageing Report
            </a>
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::CUSTOMER_CREATE))
                <a href="{{ route('customers.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition shadow-sm">
                    <span>+</span> New Customer
                </a>
            @endif
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Udhaar Outstanding</div>
            <div class="mt-2 text-2xl font-bold text-red-600 dark:text-red-500">
                {{ \App\Support\PakistaniCurrency::format($totalOutstanding) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ \App\Support\PakistaniCurrency::toUrduWords($totalOutstanding) }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Approved Credit Limit</div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                {{ \App\Support\PakistaniCurrency::format($totalCreditLimit) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">Across all registered credit clients</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Customer Accounts</div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                {{ number_format($activeCustomersCount) }}
            </div>
            <div class="mt-1 text-xs text-emerald-600 font-medium">Verified CNIC & Phone records</div>
        </div>
    </div>

    {{-- Filter and Search --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('customers.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by customer name, code, phone (03XX), or CNIC..."
                       class="w-full rounded-lg border-slate-300 py-2 pl-3 pr-10 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                @if($search)
                    <a href="{{ route('customers.index') }}" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">✕</a>
                @endif
            </div>

            <div class="w-full sm:w-44">
                <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300 py-2 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Active</option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>Inactive</option>
                </select>
            </div>

            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-medium text-white hover:bg-slate-900 dark:bg-slate-700">
                Filter
            </button>
        </form>
    </div>

    {{-- Customers Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Code / Name</th>
                        <th class="px-5 py-3">Phone & CNIC</th>
                        <th class="px-5 py-3">Vehicles</th>
                        <th class="px-5 py-3 text-right">Credit Limit</th>
                        <th class="px-5 py-3 text-right">Outstanding Udhaar</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($customers as $c)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $c->name }}</div>
                                <div class="text-xs font-mono text-red-600 dark:text-red-400 font-semibold">{{ $c->code }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs text-slate-900 dark:text-white font-mono">{{ $c->phone ?? 'N/A' }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $c->cnic ?? 'No CNIC' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-800 dark:bg-slate-800 dark:text-slate-300">
                                    🚗 {{ $c->vehicles_count }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right font-medium">
                                @if($c->creditLimitIsUnlimited())
                                    <span class="text-slate-400">Unlimited</span>
                                @else
                                    {{ \App\Support\PakistaniCurrency::format($c->credit_limit, true, 0) }}
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="font-bold {{ \App\Support\Money::compare($c->current_balance, '0.00') > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ \App\Support\PakistaniCurrency::format($c->current_balance) }}
                                </div>
                                @if(! $c->creditLimitIsUnlimited())
                                    @php
                                        $limit = (float) $c->credit_limit;
                                        $bal = (float) $c->current_balance;
                                        $pct = $limit > 0 ? min(100, round(($bal / $limit) * 100)) : 0;
                                    @endphp
                                    <div class="mt-1 flex items-center justify-end gap-1.5 text-[11px] text-slate-400">
                                        <div class="w-16 h-1.5 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                            <div class="h-full {{ $pct > 90 ? 'bg-red-600' : ($pct > 70 ? 'bg-amber-500' : 'bg-emerald-500') }}" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span>{{ $pct }}%</span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300' => $c->status === 'ACTIVE',
                                    'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300' => $c->status === 'INACTIVE',
                                ])>
                                    {{ $c->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('customers.show', $c) }}" class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                                        Ledger & Profile
                                    </a>
                                    <a href="{{ route('customers.statement', $c) }}" class="rounded bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-100 dark:bg-red-950/40 dark:text-red-400" title="Print Statement">
                                        Statement
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">
                                No customer records found matching the criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">
                {{ $customers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
