@extends('layouts.app')

@section('title', 'Non-Fuel Retail, Lubricants & Tuck Shop (Tile 11)')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Products</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header & Action Bar --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <span class="text-red-600">🛢️</span> Non-Fuel Retail & Lubricants
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Official ENEOS engine oils, Guard filters, convenience tuck shop products, car wash services, and stock valuation.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::STOCK_VIEW))
                <a href="{{ route('products.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition">
                    <span>+</span> Add New Product
                </a>
            @endif
        </div>
    </div>

    {{-- Valuation & Stock KPI Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Inventory Value (Cost Price)</div>
            <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white">
                {{ \App\Support\PakistaniCurrency::format($valuation['total_cost_value'] ?? '0.00') }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ \App\Support\PakistaniCurrency::toUrduWords($valuation['total_cost_value'] ?? '0.00') }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Retail Sales Potential</div>
            <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400">
                {{ \App\Support\PakistaniCurrency::format($valuation['total_retail_value'] ?? '0.00') }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Projected Profit: <strong>{{ \App\Support\PakistaniCurrency::format($valuation['projected_profit'] ?? '0.00') }}</strong>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Average Profit Margin</div>
            <div class="mt-2 text-2xl font-black text-red-600 dark:text-red-400">
                {{ $valuation['projected_margin_percent'] ?? '0.00' }}%
            </div>
            <div class="mt-1 text-xs text-slate-500">Across {{ $valuation['total_items_in_stock'] ?? 0 }} stocked items</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Low Stock Reorder Alerts</div>
            <div class="mt-2 text-2xl font-black {{ $lowStockCount > 0 ? 'text-red-600' : 'text-emerald-600' }}">
                {{ $lowStockCount }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ $lowStockCount > 0 ? 'Items below reorder threshold' : 'All inventory levels healthy' }}
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('products.index') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex flex-wrap items-center gap-3 flex-1">
                <div class="relative flex-1 min-w-[220px]">
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search by name, code, or barcode..."
                           class="w-full rounded-lg border-slate-300 py-1.5 pl-3 pr-8 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @if($search)
                        <a href="{{ route('products.index') }}" class="absolute right-2.5 top-1.5 text-slate-400 hover:text-slate-600">✕</a>
                    @endif
                </div>

                <div class="w-52">
                    <select name="category" onchange="this.form.submit()" class="w-full rounded-lg border-slate-300 py-1.5 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">All Categories</option>
                        <option value="LUBRICANT" @selected($category === 'LUBRICANT')>ENEOS Lubricants</option>
                        <option value="FILTER" @selected($category === 'FILTER')>Oil & Air Filters</option>
                        <option value="TUCK_SHOP" @selected($category === 'TUCK_SHOP')>Tuck Shop Items</option>
                        <option value="SERVICE" @selected($category === 'SERVICE')>Services</option>
                        <option value="CAR_WASH" @selected($category === 'CAR_WASH')>Car Wash</option>
                        <option value="TYRE" @selected($category === 'TYRE')>Tyre Services</option>
                    </select>
                </div>

                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                    <input type="checkbox" name="low_stock" value="1" onchange="this.form.submit()" @checked($lowStockOnly)
                           class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                    <span>Low Stock Only</span>
                </label>
            </div>

            @if($search || $category || $lowStockOnly)
                <a href="{{ route('products.index') }}" class="text-xs font-semibold text-red-600 hover:text-red-700 dark:text-red-400">
                    ✕ Reset Filters
                </a>
            @endif
        </form>
    </div>

    {{-- Products Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 font-bold uppercase tracking-wider text-slate-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Code & Name</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Unit</th>
                        <th class="px-5 py-3 text-right">Cost Price</th>
                        <th class="px-5 py-3 text-right">Selling Price</th>
                        <th class="px-5 py-3 text-right">Margin %</th>
                        <th class="px-5 py-3 text-right">Current Stock</th>
                        <th class="px-5 py-3 text-right">Total Valuation</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($products as $p)
                        @php
                            $isLow = $p->isLowStock();
                            $stockValuation = bcmul((string) $p->current_stock, (string) $p->cost_price, 2);
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3">
                                <a href="{{ route('products.show', $p) }}" class="font-bold text-slate-900 hover:text-red-600 dark:text-white dark:hover:text-red-400">
                                    {{ $p->name }}
                                </a>
                                <div class="text-[11px] font-mono text-slate-400">
                                    {{ $p->code }} @if($p->barcode) • Barcode: {{ $p->barcode }} @endif
                                </div>
                            </td>

                            <td class="px-5 py-3">
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

                            <td class="px-5 py-3 font-semibold text-slate-700 dark:text-slate-300">
                                {{ $p->unit }}
                            </td>

                            <td class="px-5 py-3 text-right font-mono">
                                Rs. {{ \App\Support\Money::format($p->cost_price) }}
                            </td>

                            <td class="px-5 py-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ \App\Support\Money::format($p->selling_price) }}
                            </td>

                            <td class="px-5 py-3 text-right font-mono font-semibold text-emerald-600 dark:text-emerald-400">
                                {{ $p->profitMargin() }}%
                            </td>

                            <td class="px-5 py-3 text-right">
                                <div class="font-mono font-bold {{ $isLow ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }}">
                                    {{ \App\Support\Quantity::format($p->current_stock) }} {{ $p->unit }}
                                </div>
                                @if($p->category !== 'SERVICE')
                                    <div class="text-[10px] {{ $isLow ? 'text-red-500 font-bold' : 'text-slate-400' }}">
                                        Min: {{ \App\Support\Quantity::format($p->min_stock_level) }}
                                        @if($isLow) • LOW STOCK @endif
                                    </div>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-right font-mono font-semibold text-slate-800 dark:text-slate-200">
                                {{ \App\Support\PakistaniCurrency::format($stockValuation) }}
                            </td>

                            <td class="px-5 py-3 text-center">
                                <span @class([
                                    'inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => $p->status === 'ACTIVE',
                                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => $p->status === 'INACTIVE',
                                ])>{{ $p->status }}</span>
                            </td>

                            <td class="px-5 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('products.show', $p) }}" class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 mr-1">
                                    Stock In / Out
                                </a>
                                <a href="{{ route('products.edit', $p) }}" class="rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="px-5 py-8 text-center text-slate-400">
                                No products registered yet. Click "Add New Product" to configure ENEOS lubricants, filters, or tuck shop items.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($products->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                {{ $products->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
