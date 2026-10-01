@extends('layouts.app')

@section('title', 'Fuel Purchases & Decantation (Tile 8 & 9)')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Purchases</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>⛽ Fuel Purchases &amp; Tanker Decantations</h1>
        <p>OMC tanker arrivals, dip before/after verification, shortage claims, weighted-average costing, and supplier ledgers.</p>
        <div class="page-actions">
            <a href="{{ route('suppliers.index') }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">🏢</span> Suppliers Directory
            </a>
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::PURCHASE_CREATE))
                <a href="{{ route('purchases.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> Record Tanker Arrival
                </a>
            @endif
        </div>
    </div>

    {{-- ================= Stats Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Total Purchase Value (Approved)</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($totalPurchasedValue) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords($totalPurchasedValue) }}</div>
        </div>

        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Total Fuel Received</div>
            <div class="stat-value">{{ \App\Support\Quantity::format($totalLitresReceived) }} <span class="text-sm font-bold">Litres</span></div>
            <div class="stat-sub">Into underground storage tanks</div>
        </div>

        <div class="stat-tile-3d {{ $pendingApprovalsCount > 0 ? 'stat-amber' : 'stat-green' }}">
            <div class="stat-label">Pending Approvals</div>
            <div class="stat-value">{{ $pendingApprovalsCount }}</div>
            <div class="stat-sub">
                {{ $pendingApprovalsCount > 0 ? 'Awaiting decantation sign-off' : 'All decantations approved' }}
            </div>
        </div>

        <div class="stat-tile-3d {{ $activeShortagesCount > 0 ? 'stat-red' : 'stat-green' }}">
            <div class="stat-label">Active Shortage Claims</div>
            <div class="stat-value">{{ $activeShortagesCount }}</div>
            <div class="stat-sub">
                {{ $activeShortagesCount > 0 ? 'Tanker transit losses claimed' : 'No pending shortage claims' }}
            </div>
        </div>
    </div>

    {{-- ================= Filter Bar ================= --}}
    <div class="glass-card p-4">
        <form method="GET" action="{{ route('purchases.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center justify-center gap-3">
                <div class="field-3d w-48">
                    <select name="status" onchange="this.form.submit()" class="input-3d text-xs">
                        <option value="">All Statuses</option>
                        <option value="RECEIVED" @selected($status === 'RECEIVED')>Received (Pending Approval)</option>
                        <option value="APPROVED" @selected($status === 'APPROVED')>Approved (Stock Added)</option>
                        <option value="VOID" @selected($status === 'VOID')>Void</option>
                    </select>
                </div>

                <div class="field-3d w-56">
                    <select name="supplier_id" onchange="this.form.submit()" class="input-3d text-xs">
                        <option value="">All Suppliers / OMCs</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected($supplierId == $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>

                <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="shortage_only" value="1" onchange="this.form.submit()" @checked($shortageOnly)
                           class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                    <span>Show Shortages Only</span>
                </label>
            </div>

            @if($status || $supplierId || $shortageOnly)
                <a href="{{ route('purchases.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                    ✕ Reset Filters
                </a>
            @endif
        </form>
    </div>

    {{-- ================= Purchases Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>Date &amp; Number</th>
                        <th>Supplier &amp; Challan</th>
                        <th>Fuel &amp; Destination</th>
                        <th>Tanker &amp; Driver</th>
                        <th>Challan vs Recv (L)</th>
                        <th>Rate / Litre</th>
                        <th>Total Amount</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($purchases as $p)
                        <tr>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('purchases.show', $p) }}" class="font-mono font-bold text-slate-900 hover:text-vital-primary dark:text-white dark:hover:text-red-400">
                                    {{ $p->purchase_number }}
                                </a>
                                <div class="text-[11px] text-slate-400">{{ $p->purchase_date->format('d M Y') }}</div>
                            </td>

                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $p->supplier->name ?? 'Unknown' }}</div>
                                <div class="font-mono text-[11px] text-slate-400">Challan: <strong class="text-slate-700 dark:text-slate-300">{{ $p->challan_number }}</strong></div>
                            </td>

                            <td>
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $p->fuelProduct->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500">Tank #{{ $p->tank->tank_number ?? 'N/A' }}</div>
                            </td>

                            <td>
                                <div class="font-mono text-slate-900 dark:text-white">{{ $p->tanker_number }}</div>
                                <div class="text-[11px] text-slate-400">{{ $p->driver_name }}</div>
                            </td>

                            <td class="whitespace-nowrap">
                                <div class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                    {{ \App\Support\Quantity::format($p->volume_received) }} L
                                </div>
                                @if(\App\Support\Quantity::compare($p->volume_ordered, $p->volume_received) > 0)
                                    <div class="tabular font-mono text-[11px] font-bold text-red-600 dark:text-red-400">
                                        Short: -{{ \App\Support\Quantity::format($p->shortage_litres) }} L
                                    </div>
                                @else
                                    <div class="text-[10px] font-medium text-emerald-600">Full Challan</div>
                                @endif
                            </td>

                            <td class="tabular whitespace-nowrap font-mono">
                                Rs. {{ \App\Support\Money::format($p->purchase_rate) }}
                            </td>

                            <td class="tabular whitespace-nowrap font-mono font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\PakistaniCurrency::format($p->total_amount) }}
                            </td>

                            <td class="whitespace-nowrap">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold',
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' => $p->status === 'RECEIVED',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' => $p->status === 'APPROVED',
                                    'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' => $p->status === 'VOID',
                                ])>
                                    {{ $p->status }}
                                </span>
                                @if($p->shortage_claimed)
                                    <div class="mt-0.5 text-[10px] font-semibold text-red-600">Claim: {{ $p->shortage_claim_status }}</div>
                                @endif
                            </td>

                            <td class="whitespace-nowrap">
                                <a href="{{ route('purchases.show', $p) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                    View Details →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10">
                                <div class="text-4xl" aria-hidden="true">⛽</div>
                                <p class="mt-3 font-semibold text-slate-400">No fuel purchases recorded yet. Record a tanker arrival above.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="border-t border-slate-200/70 p-4 dark:border-slate-700/60">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ================= Floating Action Button ================= --}}
@if(auth()->user()->hasPermission(\App\Support\PermissionList::PURCHASE_CREATE))
    <a href="{{ route('purchases.create') }}" class="fab-3d" title="Record a tanker arrival">
        <span class="text-xl leading-none" aria-hidden="true">＋</span> Tanker Arrival
    </a>
@endif
@endsection
