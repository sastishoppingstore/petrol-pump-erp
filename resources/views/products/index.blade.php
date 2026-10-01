@extends('layouts.app')

@section('title', __('sales.products.title'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('sales.products.breadcrumb') }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('sales.products.heading') }}</h1>
        <p>{{ __('sales.products.subtitle') }}</p>
        <div class="page-actions">
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::STOCK_VIEW))
                <a href="{{ route('products.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> {{ __('sales.products.add_new_product') }}
                </a>
            @endif
        </div>
    </div>

    {{-- ================= Valuation & Stock KPI Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('sales.products.inventory_value') }}</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($valuation['total_cost_value'] ?? '0.00') }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords($valuation['total_cost_value'] ?? '0.00') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('sales.products.retail_potential') }}</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($valuation['total_retail_value'] ?? '0.00') }}</div>
            <div class="stat-sub">{{ __('sales.products.projected_profit') }} <strong>{{ \App\Support\PakistaniCurrency::format($valuation['projected_profit'] ?? '0.00') }}</strong></div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.products.avg_margin') }}</div>
            <div class="stat-value">{{ $valuation['projected_margin_percent'] ?? '0.00' }}%</div>
            <div class="stat-sub">{{ __('sales.products.across_stocked', ['count' => $valuation['total_items_in_stock'] ?? 0]) }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d {{ $lowStockCount > 0 ? 'stat-red' : 'stat-green' }}">
            <div class="stat-label">{{ __('sales.products.low_stock_alerts') }}</div>
            <div class="stat-value kpi-num">{{ $lowStockCount }}</div>
            <div class="stat-sub">{{ $lowStockCount > 0 ? __('sales.products.items_below') : __('sales.products.all_healthy') }}</div>
        </div>
    </div>

    {{-- ================= Filter Bar ================= --}}
    <div class="glass-card p-4">
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-1 flex-wrap items-center justify-center gap-3">
                <div class="field-3d relative min-w-[220px] flex-1">
                    <input type="text" name="search" value="{{ $search }}" placeholder="{{ __('sales.products.search_placeholder') }}"
                           class="input-3d pr-8 text-xs">
                    @if($search)
                        <a href="{{ route('products.index') }}" class="absolute right-2.5 top-3 text-slate-400 hover:text-slate-600">✕</a>
                    @endif
                </div>

                <div class="field-3d w-52">
                    <select name="category" onchange="this.form.submit()" class="input-3d text-xs">
                        <option value="">{{ __('sales.products.all_categories') }}</option>
                        <option value="LUBRICANT" @selected($category === 'LUBRICANT')>{{ __('sales.products.cat_lubricants') }}</option>
                        <option value="FILTER" @selected($category === 'FILTER')>{{ __('sales.products.cat_filters') }}</option>
                        <option value="TUCK_SHOP" @selected($category === 'TUCK_SHOP')>{{ __('sales.products.cat_tuck') }}</option>
                        <option value="SERVICE" @selected($category === 'SERVICE')>{{ __('sales.products.cat_services') }}</option>
                        <option value="CAR_WASH" @selected($category === 'CAR_WASH')>{{ __('sales.products.cat_car_wash') }}</option>
                        <option value="TYRE" @selected($category === 'TYRE')>{{ __('sales.products.cat_tyre') }}</option>
                    </select>
                </div>

                <label class="inline-flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300">
                    <input type="checkbox" name="low_stock" value="1" onchange="this.form.submit()" @checked($lowStockOnly)
                           class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                    <span>{{ __('sales.products.low_stock_only') }}</span>
                </label>
            </div>

            @if($search || $category || $lowStockOnly)
                <a href="{{ route('products.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                    {{ __('sales.products.reset_filters') }}
                </a>
            @endif
        </form>
    </div>

    {{-- ================= 3D Product Cards ================= --}}
    @if($products->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach($products as $p)
                @php
                    $isLow = $p->isLowStock();
                    $stockValuation = bcmul((string) $p->current_stock, (string) $p->cost_price, 2);
                @endphp
                <article class="glass-card card-3d relative overflow-hidden p-5 text-center">
                    <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-vital-primary to-vital-darkred" aria-hidden="true"></div>

                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-vital-primary/10 text-2xl shadow-inner" aria-hidden="true">🛢️</div>
                    <h2 class="mt-3 text-base font-black text-slate-900 dark:text-white">
                        <a href="{{ route('products.show', $p) }}" class="hover:text-vital-primary dark:hover:text-red-400">{{ $p->name }}</a>
                    </h2>
                    <div class="mt-1 font-mono text-[11px] text-slate-400">
                        {{ $p->code }} @if($p->barcode) • {{ __('sales.products.barcode_label') }} {{ $p->barcode }} @endif
                    </div>

                    <div class="mt-3">
                        <span @class([
                            'inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold uppercase',
                            'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' => $p->category === 'LUBRICANT',
                            'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' => $p->category === 'FILTER',
                            'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' => $p->category === 'TUCK_SHOP',
                            'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' => in_array($p->category, ['SERVICE', 'CAR_WASH', 'TYRE']),
                        ])>
                            {{ $p->category }}
                        </span>
                        <span class="pill-status {{ $p->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }} ml-1">
                            <span class="dot" aria-hidden="true"></span>{{ $p->status }}
                        </span>
                    </div>

                    <div class="tabular mt-4 flex items-baseline justify-center gap-1.5">
                        <span class="text-sm font-bold text-slate-400">Rs</span>
                        <span class="text-3xl font-black tracking-tight text-slate-900 dark:text-white">{{ \App\Support\Money::format($p->selling_price) }}</span>
                        <span class="text-sm font-semibold text-slate-400">/ {{ strtolower($p->unit) }}</span>
                    </div>

                    <dl class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                        <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('sales.products.cost') }}</dt>
                            <dd class="tabular mt-0.5 font-mono text-sm font-bold text-slate-700 dark:text-slate-200">{{ \App\Support\Money::format($p->cost_price) }}</dd>
                        </div>
                        <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('sales.products.margin') }}</dt>
                            <dd class="tabular mt-0.5 font-mono text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $p->profitMargin() }}%</dd>
                        </div>
                        <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                            <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('sales.products.stock') }}</dt>
                            <dd class="tabular mt-0.5 font-mono text-sm font-bold {{ $isLow ? 'text-red-600 dark:text-red-400' : 'text-slate-700 dark:text-slate-200' }}">{{ \App\Support\Quantity::format($p->current_stock) }}</dd>
                        </div>
                    </dl>

                    @if($p->category !== 'SERVICE')
                        <div class="mt-2 text-[10px] {{ $isLow ? 'font-bold text-red-500' : 'text-slate-400' }}">
                            {{ __('sales.products.min_label') }} {{ \App\Support\Quantity::format($p->min_stock_level) }} {{ $p->unit }} @if($isLow) {{ __('sales.products.low_stock_badge') }} @endif
                        </div>
                    @endif

                    <div class="tabular mt-2 text-xs font-semibold text-slate-600 dark:text-slate-300">
                        {{ __('sales.products.valuation_label') }} {{ \App\Support\PakistaniCurrency::format($stockValuation) }}
                    </div>

                    <div class="mt-4 flex flex-wrap justify-center gap-2">
                        <a href="{{ route('products.show', $p) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('sales.products.stock_in_out') }}</a>
                        <a href="{{ route('products.edit', $p) }}" class="btn-3d btn-3d-primary btn-3d-sm">{{ __('ui.actions.edit') }}</a>
                    </div>
                </article>
            @endforeach
        </div>
    @endif

    {{-- ================= Products Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>{{ __('sales.products.th_code_name') }}</th>
                        <th>{{ __('sales.products.th_category') }}</th>
                        <th>{{ __('sales.products.th_unit') }}</th>
                        <th>{{ __('sales.products.th_cost') }}</th>
                        <th>{{ __('sales.products.th_selling') }}</th>
                        <th>{{ __('sales.products.th_margin') }}</th>
                        <th>{{ __('sales.products.th_current_stock') }}</th>
                        <th>{{ __('sales.products.th_total_valuation') }}</th>
                        <th>{{ __('sales.products.th_status') }}</th>
                        <th>{{ __('sales.products.th_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $p)
                        @php
                            $isLow = $p->isLowStock();
                            $stockValuation = bcmul((string) $p->current_stock, (string) $p->cost_price, 2);
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('products.show', $p) }}" class="font-bold text-slate-900 hover:text-vital-primary dark:text-white dark:hover:text-red-400">
                                    {{ $p->name }}
                                </a>
                                <div class="font-mono text-[11px] text-slate-400">
                                    {{ $p->code }} @if($p->barcode) • {{ __('sales.products.barcode_label') }} {{ $p->barcode }} @endif
                                </div>
                            </td>

                            <td>
                                <span @class([
                                    'inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold uppercase',
                                    'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' => $p->category === 'LUBRICANT',
                                    'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' => $p->category === 'FILTER',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' => $p->category === 'TUCK_SHOP',
                                    'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' => in_array($p->category, ['SERVICE', 'CAR_WASH', 'TYRE']),
                                ])>
                                    {{ $p->category }}
                                </span>
                            </td>

                            <td class="font-semibold text-slate-700 dark:text-slate-300">
                                {{ $p->unit }}
                            </td>

                            <td class="tabular font-mono">
                                Rs. {{ \App\Support\Money::format($p->cost_price) }}
                            </td>

                            <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ \App\Support\Money::format($p->selling_price) }}
                            </td>

                            <td class="tabular font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $p->profitMargin() }}%
                            </td>

                            <td>
                                <div class="tabular font-mono font-bold {{ $isLow ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ \App\Support\Quantity::format($p->current_stock) }} {{ $p->unit }}
                                </div>
                                @if($p->category !== 'SERVICE')
                                    <div class="text-[10px] {{ $isLow ? 'font-bold text-red-500' : 'text-slate-400' }}">
                                        {{ __('sales.products.min_label') }} {{ \App\Support\Quantity::format($p->min_stock_level) }}
                                        @if($isLow) {{ __('sales.products.low_stock_badge') }} @endif
                                    </div>
                                @endif
                            </td>

                            <td class="tabular font-mono font-semibold text-slate-800 dark:text-slate-200">
                                {{ \App\Support\PakistaniCurrency::format($stockValuation) }}
                            </td>

                            <td>
                                <span class="pill-status {{ $p->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }}">
                                    <span class="dot" aria-hidden="true"></span>{{ $p->status }}
                                </span>
                            </td>

                            <td class="whitespace-nowrap">
                                <div class="inline-flex items-center justify-center gap-2">
                                    <a href="{{ route('products.show', $p) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                        {{ __('sales.products.stock_in_out') }}
                                    </a>
                                    <a href="{{ route('products.edit', $p) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                                        {{ __('ui.actions.edit') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-10">
                                <div class="text-4xl" aria-hidden="true">🛢️</div>
                                <p class="mt-3 font-semibold text-slate-400">{{ __('sales.products.empty') }}</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="border-t border-slate-200/70 p-4 dark:border-slate-700/60">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ================= Floating Action Button ================= --}}
@if(auth()->user()->hasPermission(\App\Support\PermissionList::STOCK_VIEW))
    <a href="{{ route('products.create') }}" class="fab-3d" title="{{ __('sales.products.fab_title') }}">
        <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('sales.products.fab_add_product') }}
    </a>
@endif
@endsection
