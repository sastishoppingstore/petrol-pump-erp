@extends('layouts.app')

@section('title', $product->name . ' — Stock & Ledger')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('products.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Products</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ $product->code }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header Banner --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ $product->name }}</h1>
                <span class="rounded bg-slate-100 px-2.5 py-0.5 font-mono text-xs font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200">{{ $product->code }}</span>
                <span @class([
                    'inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium',
                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => $product->status === 'ACTIVE',
                    'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-400' => $product->status === 'INACTIVE',
                ])>{{ $product->status }}</span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                Category: <strong class="text-slate-700 dark:text-slate-300">{{ $product->category }}</strong> • Unit: {{ $product->unit }}
                @if($product->barcode) • Barcode: <span class="font-mono">{{ $product->barcode }}</span> @endif
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('products.index') }}" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition shadow-sm">
                ← All Products
            </a>
            <a href="{{ route('products.edit', $product) }}" class="rounded-lg bg-red-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-red-700 shadow-sm transition">
                ✏️ Edit Product
            </a>
        </div>
    </div>

    {{-- Stock & Pricing KPI Grid --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        @php
            $isLow = $product->isLowStock();
            $stockValuation = bcmul((string) $product->current_stock, (string) $product->cost_price, 2);
        @endphp
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Physical Stock on Hand</div>
            <div class="mt-2 text-3xl font-extrabold {{ $isLow ? 'text-red-600 dark:text-red-400' : 'text-slate-900 dark:text-white' }} font-mono">
                {{ \App\Support\Quantity::format($product->current_stock) }} <span class="text-base font-normal text-slate-500">{{ $product->unit }}</span>
            </div>
            <div class="mt-1 text-xs {{ $isLow ? 'text-red-600 font-bold' : 'text-slate-500' }}">
                Min Alert Level: {{ \App\Support\Quantity::format($product->min_stock_level) }} {{ $product->unit }}
                @if($isLow) (REORDER NEEDED) @endif
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cost Price vs MRP</div>
            <div class="mt-2 text-xl font-bold text-slate-900 dark:text-white font-mono">
                Rs. {{ \App\Support\Money::format($product->cost_price) }} <span class="text-xs font-normal text-slate-400">/ cost</span>
            </div>
            <div class="mt-1 text-xs text-emerald-600 font-semibold font-mono">
                Rs. {{ \App\Support\Money::format($product->selling_price) }} <span class="text-slate-400 font-normal">/ selling</span>
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Gross Margin</div>
            <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                {{ $product->profitMargin() }}%
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Profit: Rs. {{ \App\Support\Money::format($product->profitAmount()) }} per {{ $product->unit }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Stock Valuation</div>
            <div class="mt-2 text-2xl font-black text-red-600 dark:text-red-400 font-mono">
                {{ \App\Support\PakistaniCurrency::format($stockValuation) }}
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ \App\Support\PakistaniCurrency::toUrduWords($stockValuation) }}
            </div>
        </div>
    </div>

    {{-- Stock Operations (Quick Stock In & Manual Adjustment) --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Quick Stock In --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                <span>📥</span> Quick Purchase / Stock In
            </h2>
            <form method="POST" action="{{ route('products.stock-in', $product) }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Received Quantity ({{ $product->unit }}) *</label>
                        <input type="number" step="0.001" name="quantity" required placeholder="e.g. 12"
                               class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Unit Purchase Cost (Rs.) *</label>
                        <input type="number" step="0.01" name="unit_cost" value="{{ $product->cost_price }}" required
                               class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Supplier / Delivery Notes</label>
                    <input type="text" name="notes" placeholder="e.g. Invoice #9021 from ENEOS Distributor"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-xs font-bold text-white hover:bg-emerald-700 shadow-sm transition">
                        Receive Stock In
                    </button>
                </div>
            </form>
        </div>

        {{-- Manual Stock Adjustment --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                <span>⚖️</span> Inventory Adjustment (Damage / Breakage / Audit)
            </h2>
            <form method="POST" action="{{ route('products.adjust-stock', $product) }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Adjustment Type *</label>
                        <select name="type" required class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                            <option value="ADJUSTMENT_IN">Stock In (+) / Surplus</option>
                            <option value="ADJUSTMENT_OUT">Stock Out (-) / Loss / Expired</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Quantity ({{ $product->unit }}) *</label>
                        <input type="number" step="0.001" name="quantity" required placeholder="e.g. 1"
                               class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Reason / Audit Justification *</label>
                    <input type="text" name="notes" required placeholder="e.g. Can leaking during transit / physical inventory count"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-900 shadow-sm transition dark:bg-slate-700 dark:hover:bg-slate-600">
                        Record Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Stock Movements History Ledger --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>📜</span> Stock Movement Ledger History
            </h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-50 font-bold uppercase tracking-wider text-slate-600 dark:border-slate-800 dark:bg-slate-800/60 dark:text-slate-400">
                    <tr>
                        <th class="px-5 py-3">Timestamp</th>
                        <th class="px-5 py-3">Movement Type</th>
                        <th class="px-5 py-3 text-right">Quantity</th>
                        <th class="px-5 py-3 text-right">Before</th>
                        <th class="px-5 py-3 text-right">After Stock</th>
                        <th class="px-5 py-3 text-right">Unit Cost</th>
                        <th class="px-5 py-3">Notes & Reference</th>
                        <th class="px-5 py-3">By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($movements as $m)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-5 py-3 font-mono whitespace-nowrap">
                                {{ $m->created_at->format('d/m/Y h:i A') }}
                            </td>

                            <td class="px-5 py-3 whitespace-nowrap">
                                <span @class([
                                    'inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold uppercase',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => in_array($m->type, ['PURCHASE', 'ADJUSTMENT_IN', 'RETURN']),
                                    'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' => $m->type === 'SALE',
                                    'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' => $m->type === 'ADJUSTMENT_OUT',
                                ])>{{ $m->type }}</span>
                            </td>

                            <td class="px-5 py-3 text-right font-mono font-bold whitespace-nowrap {{ in_array($m->type, ['SALE', 'ADJUSTMENT_OUT']) ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ in_array($m->type, ['SALE', 'ADJUSTMENT_OUT']) ? '-' : '+' }}{{ \App\Support\Quantity::format($m->quantity) }}
                            </td>

                            <td class="px-5 py-3 text-right font-mono whitespace-nowrap">
                                {{ \App\Support\Quantity::format($m->before_stock) }}
                            </td>

                            <td class="px-5 py-3 text-right font-mono font-bold text-slate-900 dark:text-white whitespace-nowrap">
                                {{ \App\Support\Quantity::format($m->after_stock) }}
                            </td>

                            <td class="px-5 py-3 text-right font-mono whitespace-nowrap">
                                Rs. {{ \App\Support\Money::format($m->unit_cost) }}
                            </td>

                            <td class="px-5 py-3">
                                <div>{{ $m->notes }}</div>
                                @if($m->reference_type)
                                    <div class="text-[10px] text-slate-400 font-mono">Ref: {{ $m->reference_type }} #{{ $m->reference_id }}</div>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-slate-500 whitespace-nowrap">
                                {{ $m->creator->name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-8 text-center text-slate-400">
                                No stock movements recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="border-t border-slate-200 p-4 dark:border-slate-800">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
