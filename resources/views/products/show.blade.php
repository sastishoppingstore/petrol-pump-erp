@extends('layouts.app')

@section('title', $product->name . ' — Stock & Ledger')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('products.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Products</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ $product->code }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>{{ $product->name }}</h1>
        <p>
            <span class="inline-block rounded-md bg-slate-100 px-2 py-0.5 align-middle font-mono text-xs font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200">{{ $product->code }}</span>
            <span class="pill-status {{ $product->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }} align-middle">
                <span class="dot" aria-hidden="true"></span>{{ $product->status }}
            </span>
        </p>
        <p>
            Category: <strong class="text-slate-700 dark:text-slate-300">{{ $product->category }}</strong> • Unit: {{ $product->unit }}
            @if($product->barcode) • Barcode: <span class="font-mono">{{ $product->barcode }}</span> @endif
        </p>
        <div class="page-actions">
            <a href="{{ route('products.index') }}" class="btn-3d btn-3d-ghost">← All Products</a>
            <a href="{{ route('products.edit', $product) }}" class="btn-3d btn-3d-primary">✏️ Edit Product</a>
        </div>
    </div>

    {{-- ================= Stock & Pricing KPI Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $isLow = $product->isLowStock();
            $stockValuation = bcmul((string) $product->current_stock, (string) $product->cost_price, 2);
        @endphp
        <div class="stat-tile-3d {{ $isLow ? 'stat-red' : 'stat-navy' }}">
            <div class="stat-label">Physical Stock on Hand</div>
            <div class="stat-value tabular font-mono">
                {{ \App\Support\Quantity::format($product->current_stock) }} <span class="text-base font-bold">{{ $product->unit }}</span>
            </div>
            <div class="stat-sub">
                Min Alert Level: {{ \App\Support\Quantity::format($product->min_stock_level) }} {{ $product->unit }}
                @if($isLow) (REORDER NEEDED) @endif
            </div>
        </div>

        <div class="stat-tile-3d stat-slate">
            <div class="stat-label">Cost Price vs MRP</div>
            <div class="stat-value tabular font-mono text-xl">
                Rs. {{ \App\Support\Money::format($product->cost_price) }} <span class="text-xs font-bold opacity-80">/ cost</span>
            </div>
            <div class="stat-sub tabular font-mono font-semibold">
                Rs. {{ \App\Support\Money::format($product->selling_price) }} / selling
            </div>
        </div>

        <div class="stat-tile-3d stat-green">
            <div class="stat-label">Gross Margin</div>
            <div class="stat-value tabular font-mono">{{ $product->profitMargin() }}%</div>
            <div class="stat-sub">
                Profit: Rs. {{ \App\Support\Money::format($product->profitAmount()) }} per {{ $product->unit }}
            </div>
        </div>

        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Total Stock Valuation</div>
            <div class="stat-value tabular font-mono">{{ \App\Support\PakistaniCurrency::format($stockValuation) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords($stockValuation) }}</div>
        </div>
    </div>

    {{-- ================= Stock Operations ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Quick Stock In --}}
        <div class="glass-card p-5">
            <h2 class="flex items-center justify-center gap-2 border-b border-slate-200/70 pb-3 text-center text-sm font-bold text-slate-900 dark:border-slate-700/60 dark:text-white">
                <span aria-hidden="true">📥</span> Quick Purchase / Stock In
            </h2>
            <form method="POST" action="{{ route('products.stock-in', $product) }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Received Quantity ({{ $product->unit }}) *</label>
                        <input type="number" step="0.001" name="quantity" required placeholder="e.g. 12"
                               class="input-3d text-center font-mono text-xs font-bold text-slate-900 dark:text-white">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Unit Purchase Cost (Rs.) *</label>
                        <input type="number" step="0.01" name="unit_cost" value="{{ $product->cost_price }}" required
                               class="input-3d text-center font-mono text-xs">
                    </div>
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Supplier / Delivery Notes</label>
                    <input type="text" name="notes" placeholder="e.g. Invoice #9021 from ENEOS Distributor"
                           class="input-3d text-center text-xs">
                </div>

                <div class="flex justify-center pt-1">
                    <button type="submit" class="btn-3d btn-3d-success text-xs">
                        Receive Stock In
                    </button>
                </div>
            </form>
        </div>

        {{-- Manual Stock Adjustment --}}
        <div class="glass-card p-5">
            <h2 class="flex items-center justify-center gap-2 border-b border-slate-200/70 pb-3 text-center text-sm font-bold text-slate-900 dark:border-slate-700/60 dark:text-white">
                <span aria-hidden="true">⚖️</span> Inventory Adjustment (Damage / Breakage / Audit)
            </h2>
            <form method="POST" action="{{ route('products.adjust-stock', $product) }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Adjustment Type *</label>
                        <select name="type" required class="input-3d text-center text-xs">
                            <option value="ADJUSTMENT_IN">Stock In (+) / Surplus</option>
                            <option value="ADJUSTMENT_OUT">Stock Out (-) / Loss / Expired</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Quantity ({{ $product->unit }}) *</label>
                        <input type="number" step="0.001" name="quantity" required placeholder="e.g. 1"
                               class="input-3d text-center font-mono text-xs font-bold text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Reason / Audit Justification *</label>
                    <input type="text" name="notes" required placeholder="e.g. Can leaking during transit / physical inventory count"
                           class="input-3d text-center text-xs">
                </div>

                <div class="flex justify-center pt-1">
                    <button type="submit" class="btn-3d btn-3d-navy text-xs">
                        Record Adjustment
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= Stock Movements History Ledger ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 p-4 text-center dark:border-slate-700/60">
            <h2 class="flex items-center justify-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                <span aria-hidden="true">📜</span> Stock Movement Ledger History
            </h2>
        </div>

        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Movement Type</th>
                        <th>Quantity</th>
                        <th>Before</th>
                        <th>After Stock</th>
                        <th>Unit Cost</th>
                        <th>Notes &amp; Reference</th>
                        <th>By</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td class="tabular whitespace-nowrap font-mono">
                                {{ $m->created_at->format('d/m/Y h:i A') }}
                            </td>

                            <td class="whitespace-nowrap">
                                <span @class([
                                    'inline-flex items-center rounded px-2 py-0.5 text-[10px] font-bold uppercase',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => in_array($m->type, ['PURCHASE', 'ADJUSTMENT_IN', 'RETURN']),
                                    'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300' => $m->type === 'SALE',
                                    'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' => $m->type === 'ADJUSTMENT_OUT',
                                ])>{{ $m->type }}</span>
                            </td>

                            <td class="tabular whitespace-nowrap font-mono font-bold {{ in_array($m->type, ['SALE', 'ADJUSTMENT_OUT']) ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ in_array($m->type, ['SALE', 'ADJUSTMENT_OUT']) ? '-' : '+' }}{{ \App\Support\Quantity::format($m->quantity) }}
                            </td>

                            <td class="tabular whitespace-nowrap font-mono">
                                {{ \App\Support\Quantity::format($m->before_stock) }}
                            </td>

                            <td class="tabular whitespace-nowrap font-mono font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\Quantity::format($m->after_stock) }}
                            </td>

                            <td class="tabular whitespace-nowrap font-mono">
                                Rs. {{ \App\Support\Money::format($m->unit_cost) }}
                            </td>

                            <td>
                                <div>{{ $m->notes }}</div>
                                @if($m->reference_type)
                                    <div class="font-mono text-[10px] text-slate-400">Ref: {{ $m->reference_type }} #{{ $m->reference_id }}</div>
                                @endif
                            </td>

                            <td class="whitespace-nowrap text-slate-500">
                                {{ $m->creator->name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-slate-400">
                                No stock movements recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="border-t border-slate-200/70 p-4 dark:border-slate-700/60">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
