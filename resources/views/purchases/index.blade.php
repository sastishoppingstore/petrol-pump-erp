@extends('layouts.app')

@section('title', 'Fuel Purchases & Decantation (Tile 8 & 9)')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Purchases</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header & Actions --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <span class="text-red-600">⛽</span> Fuel Purchases & Tanker Decantations
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                OMC tanker arrivals, dip before/after verification, shortage claims, weighted-average costing, and supplier ledgers.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('suppliers.index') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 shadow-sm transition">
                <span>🏢</span> Suppliers Directory
            </a>
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::PURCHASE_CREATE))
                <a href="{{ route('purchases.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition">
                    <span>+</span> Record Tanker Arrival
                </a>
            @endif
        </div>
    </div>

    {{-- Stats Overview Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Purchase Value (Approved)</div>
            <div class="mt-2 text-2xl font-black text-red-600 dark:text-red-500">
                {{ \App\Support\PakistaniCurrency::format($totalPurchasedValue) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ \App\Support\PakistaniCurrency::toUrduWords($totalPurchasedValue) }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Fuel Received</div>
            <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                {{ \App\Support\Quantity::format($totalLitresReceived) }} <span class="text-sm font-normal text-slate-500">Litres</span>
            </div>
            <div class="mt-1 text-xs text-slate-500">Into underground storage tanks</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Pending Approvals</div>
            <div class="mt-2 text-2xl font-bold {{ $pendingApprovalsCount > 0 ? 'text-amber-600' : 'text-slate-900 dark:text-white' }}">
                {{ $pendingApprovalsCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ $pendingApprovalsCount > 0 ? 'Awaiting decantation sign-off' : 'All decantations approved' }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Active Shortage Claims</div>
            <div class="mt-2 text-2xl font-bold {{ $activeShortagesCount > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                {{ $activeShortagesCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ $activeShortagesCount > 0 ? 'Tanker transit losses claimed' : 'No pending shortage claims' }}
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('purchases.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <div class="w-48">
                    <select name="status" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300 py-1.5 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">All Statuses</option>
                        <option value="RECEIVED" @selected($status === 'RECEIVED')>Received (Pending Approval)</option>
                        <option value="APPROVED" @selected($status === 'APPROVED')>Approved (Stock Added)</option>
                        <option value="VOID" @selected($status === 'VOID')>Void</option>
                    </select>
                </div>

                <div class="w-56">
                    <select name="supplier_id" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300 py-1.5 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">All Suppliers / OMCs</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected($supplierId == $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>

                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                    <input type="checkbox" name="shortage_only" value="1" onchange="this.form.submit()" @checked($shortageOnly)
                           class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                    <span>Show Shortages Only</span>
                </label>
            </div>

            @if($status || $supplierId || $shortageOnly)
                <a href="{{ route('purchases.index') }}" class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400">
                    ✕ Reset Filters
                </a>
            @endif
        </form>
    </div>

    {{-- Purchases Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 font-bold uppercase tracking-wider text-slate-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Date & Number</th>
                        <th class="px-5 py-3">Supplier & Challan</th>
                        <th class="px-5 py-3">Fuel & Destination</th>
                        <th class="px-5 py-3">Tanker & Driver</th>
                        <th class="px-5 py-3 text-right">Challan vs Recv (L)</th>
                        <th class="px-5 py-3 text-right">Rate / Litre</th>
                        <th class="px-5 py-3 text-right">Total Amount</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($purchases as $p)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3 whitespace-nowrap">
                                <a href="{{ route('purchases.show', $p) }}" class="font-bold text-slate-900 hover:text-red-600 dark:text-white dark:hover:text-red-400 font-mono">
                                    {{ $p->purchase_number }}
                                </a>
                                <div class="text-[11px] text-slate-400">{{ $p->purchase_date->format('d M Y') }}</div>
                            </td>

                            <td class="px-5 py-3">
                                <div class="font-medium text-slate-900 dark:text-white">{{ $p->supplier->name ?? 'Unknown' }}</div>
                                <div class="text-[11px] font-mono text-slate-400">Challan: <strong class="text-slate-700 dark:text-slate-300">{{ $p->challan_number }}</strong></div>
                            </td>

                            <td class="px-5 py-3">
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $p->fuelProduct->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500">Tank #{{ $p->tank->tank_number ?? 'N/A' }}</div>
                            </td>

                            <td class="px-5 py-3">
                                <div class="font-mono text-slate-900 dark:text-white">{{ $p->tanker_number }}</div>
                                <div class="text-[11px] text-slate-400">{{ $p->driver_name }}</div>
                            </td>

                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <div class="font-mono font-bold text-slate-900 dark:text-white">
                                    {{ \App\Support\Quantity::format($p->volume_received) }} L
                                </div>
                                @if(\App\Support\Quantity::compare($p->volume_ordered, $p->volume_received) > 0)
                                    <div class="text-[11px] font-bold text-red-600 dark:text-red-400 font-mono">
                                        Short: -{{ \App\Support\Quantity::format($p->shortage_litres) }} L
                                    </div>
                                @else
                                    <div class="text-[10px] text-emerald-600 font-medium">Full Challan</div>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-right font-mono whitespace-nowrap">
                                Rs. {{ \App\Support\Money::format($p->purchase_rate) }}
                            </td>

                            <td class="px-5 py-3 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ \App\Support\PakistaniCurrency::format($p->total_amount) }}
                            </td>

                            <td class="px-5 py-3 text-center whitespace-nowrap">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' => $p->status === 'RECEIVED',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' => $p->status === 'APPROVED',
                                    'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' => $p->status === 'VOID',
                                ])>
                                    {{ $p->status }}
                                </span>
                                @if($p->shortage_claimed)
                                    <div class="text-[10px] font-semibold text-red-600 mt-0.5">Claim: {{ $p->shortage_claim_status }}</div>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('purchases.show', $p) }}" class="rounded bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                                    View Details →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-8 text-center text-slate-400">
                                No fuel purchases recorded yet. Record a tanker arrival above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
