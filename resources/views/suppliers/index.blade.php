@extends('layouts.app')

@section('title', 'Suppliers & OMC Accounts (Tile 8)')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Suppliers</span></li>
@endsection

@section('content')
<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <span class="text-red-600">🏭</span> Fuel Suppliers & OMC Accounts
            </h1>
            <p class="text-sm text-slate-500">
                Oil Marketing Companies (Vital Petroleum, PSO, Parco), lubricant distributors, and local station vendors.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('purchases.create') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 bg-red-50 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-100 transition shadow-sm">
                <span>📥</span> New Fuel Decantation
            </a>
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::SUPPLIER_CREATE))
                <a href="{{ route('suppliers.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white hover:bg-red-700 transition shadow-sm">
                    <span>+</span> New Supplier
                </a>
            @endif
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Payable to Suppliers</div>
            <div class="mt-2 text-2xl font-bold text-red-600 dark:text-red-500">
                {{ \App\Support\PakistaniCurrency::format($totalPayable) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ \App\Support\PakistaniCurrency::toUrduWords($totalPayable) }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Supply Partners</div>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                {{ number_format($activeSuppliersCount) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">OMC depots & lubricant vendors</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Primary Franchise Partner</div>
            <div class="mt-2 text-base font-bold text-slate-900 dark:text-white">
                Vital Petroleum (Pvt) Ltd
            </div>
            <div class="mt-1 text-xs text-emerald-600 font-medium">Franchise Terminal: Sheikhupura</div>
        </div>
    </div>

    {{-- Suppliers Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Code / Supplier Name</th>
                        <th class="px-5 py-3">Contact Person & Phone</th>
                        <th class="px-5 py-3">NTN / STRN</th>
                        <th class="px-5 py-3 text-center">Decantations</th>
                        <th class="px-5 py-3 text-right">Current Payable</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($suppliers as $s)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-4">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $s->name }}</div>
                                <div class="text-xs font-mono text-red-600 font-semibold">{{ $s->code }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs text-slate-900 dark:text-white font-medium">{{ $s->contact_person ?? 'Direct' }}</div>
                                <div class="text-xs text-slate-500 font-mono">{{ $s->phone ?? 'N/A' }}</div>
                            </td>
                            <td class="px-5 py-4">
                                <div class="text-xs text-slate-700 dark:text-slate-300 font-mono">NTN: {{ $s->ntn_number ?? 'N/A' }}</div>
                                @if($s->strn_number)
                                    <div class="text-[11px] text-slate-400 font-mono">STRN: {{ $s->strn_number }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-800 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $s->purchases_count }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="font-bold text-slate-900 dark:text-white">
                                    {{ \App\Support\PakistaniCurrency::format($s->current_balance) }}
                                </div>
                            </td>
                            <td class="px-5 py-4 text-center">
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800">
                                    {{ $s->status }}
                                </span>
                            </td>
                            <td class="px-5 py-4 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('suppliers.show', $s) }}" class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                                        Ledger & Payments
                                    </a>
                                    <a href="{{ route('suppliers.statement', $s) }}" class="rounded bg-red-50 px-2.5 py-1 text-xs font-semibold text-red-700 hover:bg-red-100">
                                        Statement
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-5 py-8 text-center text-sm text-slate-500">
                                No suppliers registered yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($suppliers->hasPages())
            <div class="border-t border-slate-200 px-5 py-3 dark:border-slate-800">
                {{ $suppliers->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
