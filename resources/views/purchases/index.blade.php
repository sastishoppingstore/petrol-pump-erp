@extends('layouts.app')

@section('title', __('sales.purchases.title'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('sales.purchases.breadcrumb') }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('sales.purchases.heading') }}</h1>
        <p>{{ __('sales.purchases.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('suppliers.index') }}" class="btn-3d btn-3d-ghost">
                <span aria-hidden="true">🏢</span> {{ __('sales.purchases.suppliers_directory') }}
            </a>
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::PURCHASE_CREATE))
                <a href="{{ route('purchases.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> {{ __('sales.purchases.record_tanker_arrival') }}
                </a>
            @endif
        </div>
    </div>

    {{-- ================= Stats Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.purchases.total_purchase_value') }}</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($totalPurchasedValue) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords($totalPurchasedValue) }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('sales.purchases.total_fuel_received') }}</div>
            <div class="stat-value">{{ \App\Support\Quantity::format($totalLitresReceived) }} <span class="text-sm font-bold">{{ __('sales.purchases.litres') }}</span></div>
            <div class="stat-sub">{{ __('sales.purchases.into_tanks') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d {{ $pendingApprovalsCount > 0 ? 'stat-amber' : 'stat-green' }}">
            <div class="stat-label">{{ __('sales.purchases.pending_approvals') }}</div>
            <div class="stat-value kpi-num">{{ $pendingApprovalsCount }}</div>
            <div class="stat-sub">
                {{ $pendingApprovalsCount > 0 ? __('sales.purchases.awaiting_signoff') : __('sales.purchases.all_approved') }}
            </div>
        </div>

        <div class="stat-tile-3d tilt-3d {{ $activeShortagesCount > 0 ? 'stat-red' : 'stat-green' }}">
            <div class="stat-label">{{ __('sales.purchases.active_shortage_claims') }}</div>
            <div class="stat-value kpi-num">{{ $activeShortagesCount }}</div>
            <div class="stat-sub">
                {{ $activeShortagesCount > 0 ? __('sales.purchases.tanker_losses_claimed') : __('sales.purchases.no_shortage_claims') }}
            </div>
        </div>
    </div>

    {{-- ================= Filter Bar ================= --}}
    <div class="glass-card p-4">
        <form method="GET" action="{{ route('purchases.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center justify-center gap-3">
                <div class="field-3d w-48">
                    <select name="status" onchange="this.form.submit()" class="input-3d text-xs">
                        <option value="">{{ __('sales.purchases.all_statuses') }}</option>
                        <option value="RECEIVED" @selected($status === 'RECEIVED')>{{ __('sales.purchases.received_pending') }}</option>
                        <option value="APPROVED" @selected($status === 'APPROVED')>{{ __('sales.purchases.approved_stock_added') }}</option>
                        <option value="VOID" @selected($status === 'VOID')>{{ __('sales.purchases.void') }}</option>
                    </select>
                </div>

                <div class="field-3d w-56">
                    <select name="supplier_id" onchange="this.form.submit()" class="input-3d text-xs">
                        <option value="">{{ __('sales.purchases.all_suppliers') }}</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected($supplierId == $s->id)>{{ $s->name }} ({{ $s->code }})</option>
                        @endforeach
                    </select>
                </div>

                <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="shortage_only" value="1" onchange="this.form.submit()" @checked($shortageOnly)
                           class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                    <span>{{ __('sales.purchases.show_shortages_only') }}</span>
                </label>
            </div>

            @if($status || $supplierId || $shortageOnly)
                <a href="{{ route('purchases.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                    {{ __('sales.purchases.reset_filters') }}
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
                        <th>{{ __('sales.purchases.th_date_number') }}</th>
                        <th>{{ __('sales.purchases.th_supplier_challan') }}</th>
                        <th>{{ __('sales.purchases.th_fuel_destination') }}</th>
                        <th>{{ __('sales.purchases.th_tanker_driver') }}</th>
                        <th>{{ __('sales.purchases.th_challan_vs_recv') }}</th>
                        <th>{{ __('sales.purchases.th_rate_litre') }}</th>
                        <th>{{ __('sales.purchases.th_total_amount') }}</th>
                        <th>{{ __('sales.purchases.th_status') }}</th>
                        <th>{{ __('sales.purchases.th_actions') }}</th>
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
                                <div class="font-medium text-slate-900 dark:text-white">{{ $p->supplier->name ?? __('sales.purchases.unknown') }}</div>
                                <div class="font-mono text-[11px] text-slate-400">{{ __('sales.purchases.challan_label') }} <strong class="text-slate-700 dark:text-slate-300">{{ $p->challan_number }}</strong></div>
                            </td>

                            <td>
                                <div class="font-semibold text-slate-800 dark:text-slate-200">{{ $p->fuelProduct->name ?? 'N/A' }}</div>
                                <div class="text-[11px] text-slate-500">{{ __('sales.purchases.tank_number', ['number' => $p->tank->tank_number ?? 'N/A']) }}</div>
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
                                        {{ __('sales.purchases.short_label') }} -{{ \App\Support\Quantity::format($p->shortage_litres) }} L
                                    </div>
                                @else
                                    <div class="text-[10px] font-medium text-emerald-600">{{ __('sales.purchases.full_challan') }}</div>
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
                                    <div class="mt-0.5 text-[10px] font-semibold text-red-600">{{ __('sales.purchases.claim_label') }} {{ $p->shortage_claim_status }}</div>
                                @endif
                            </td>

                            <td class="whitespace-nowrap">
                                <a href="{{ route('purchases.show', $p) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                    {{ __('sales.purchases.view_details') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-10">
                                <div class="text-4xl" aria-hidden="true">⛽</div>
                                <p class="mt-3 font-semibold text-slate-400">{{ __('sales.purchases.empty') }}</p>
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
    <a href="{{ route('purchases.create') }}" class="fab-3d" title="{{ __('sales.purchases.fab_title') }}">
        <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('sales.purchases.fab_tanker_arrival') }}
    </a>
@endif
@endsection
