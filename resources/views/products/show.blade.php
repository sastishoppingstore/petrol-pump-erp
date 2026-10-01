@extends('layouts.app')

@section('title', __('sales.product_show.title', ['name' => $product->name]))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('products.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('sales.product_show.breadcrumb_products') }}</a></li>
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
            {{ __('sales.product_show.category_label') }} <strong class="text-slate-700 dark:text-slate-300">{{ $product->category }}</strong> • {{ __('sales.product_show.unit_label') }} {{ $product->unit }}
            @if($product->barcode) • {{ __('sales.product_show.barcode_label') }} <span class="font-mono">{{ $product->barcode }}</span> @endif
        </p>
        <div class="page-actions">
            <a href="{{ route('products.index') }}" class="btn-3d btn-3d-ghost">{{ __('sales.product_show.all_products') }}</a>
            <a href="{{ route('products.edit', $product) }}" class="btn-3d btn-3d-primary">{{ __('sales.product_show.edit_product') }}</a>
        </div>
    </div>

    {{-- ================= Stock & Pricing KPI Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @php
            $isLow = $product->isLowStock();
            $stockValuation = bcmul((string) $product->current_stock, (string) $product->cost_price, 2);
        @endphp
        <div class="stat-tile-3d tilt-3d {{ $isLow ? 'stat-red' : 'stat-navy' }}">
            <div class="stat-label">{{ __('sales.product_show.physical_stock') }}</div>
            <div class="stat-value tabular font-mono">
                {{ \App\Support\Quantity::format($product->current_stock) }} <span class="text-base font-bold">{{ $product->unit }}</span>
            </div>
            <div class="stat-sub">
                {{ __('sales.product_show.min_alert', ['stock' => \App\Support\Quantity::format($product->min_stock_level), 'unit' => $product->unit]) }}
                @if($isLow) {{ __('sales.product_show.reorder_needed') }} @endif
            </div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-slate">
            <div class="stat-label">{{ __('sales.product_show.cost_vs_mrp') }}</div>
            <div class="stat-value tabular font-mono text-xl">
                Rs. {{ \App\Support\Money::format($product->cost_price) }} <span class="text-xs font-bold opacity-80">{{ __('sales.product_show.per_cost') }}</span>
            </div>
            <div class="stat-sub tabular font-mono font-semibold">
                Rs. {{ \App\Support\Money::format($product->selling_price) }} {{ __('sales.product_show.per_selling') }}
            </div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('sales.product_show.gross_margin') }}</div>
            <div class="stat-value tabular font-mono">{{ $product->profitMargin() }}%</div>
            <div class="stat-sub">
                {{ __('sales.product_show.profit_per', ['amount' => \App\Support\Money::format($product->profitAmount()), 'unit' => $product->unit]) }}
            </div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.product_show.total_valuation') }}</div>
            <div class="stat-value tabular font-mono">{{ \App\Support\PakistaniCurrency::format($stockValuation) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords($stockValuation) }}</div>
        </div>
    </div>

    {{-- ================= Stock Operations ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Quick Stock In --}}
        <div class="glass-card p-5">
            <h2 class="flex items-center justify-center gap-2 border-b border-slate-200/70 pb-3 text-center text-sm font-bold text-slate-900 dark:border-slate-700/60 dark:text-white">
                <span aria-hidden="true">📥</span> {{ __('sales.product_show.stock_in_heading') }}
            </h2>
            <form method="POST" action="{{ route('products.stock-in', $product) }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_show.received_qty', ['unit' => $product->unit]) }}</label>
                        <input type="number" step="0.001" name="quantity" required placeholder="{{ __('sales.product_show.received_placeholder') }}"
                               class="input-3d text-center font-mono text-xs font-bold text-slate-900 dark:text-white">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_show.unit_cost_label') }}</label>
                        <input type="number" step="0.01" name="unit_cost" value="{{ $product->cost_price }}" required
                               class="input-3d text-center font-mono text-xs">
                    </div>
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_show.supplier_notes_label') }}</label>
                    <input type="text" name="notes" placeholder="{{ __('sales.product_show.notes_placeholder') }}"
                           class="input-3d text-center text-xs">
                </div>

                <div class="flex justify-center pt-1">
                    <button type="submit" class="btn-3d btn-3d-success text-xs">
                        {{ __('sales.product_show.receive_stock') }}
                    </button>
                </div>
            </form>
        </div>

        {{-- Manual Stock Adjustment --}}
        <div class="glass-card p-5">
            <h2 class="flex items-center justify-center gap-2 border-b border-slate-200/70 pb-3 text-center text-sm font-bold text-slate-900 dark:border-slate-700/60 dark:text-white">
                <span aria-hidden="true">⚖️</span> {{ __('sales.product_show.adjust_heading') }}
            </h2>
            <form method="POST" action="{{ route('products.adjust-stock', $product) }}" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_show.adjust_type_label') }}</label>
                        <select name="type" required class="input-3d text-center text-xs">
                            <option value="ADJUSTMENT_IN">{{ __('sales.product_show.adj_in') }}</option>
                            <option value="ADJUSTMENT_OUT">{{ __('sales.product_show.adj_out') }}</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_show.qty_label', ['unit' => $product->unit]) }}</label>
                        <input type="number" step="0.001" name="quantity" required placeholder="{{ __('sales.product_show.qty_placeholder') }}"
                               class="input-3d text-center font-mono text-xs font-bold text-slate-900 dark:text-white">
                    </div>
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_show.reason_label') }}</label>
                    <input type="text" name="notes" required placeholder="{{ __('sales.product_show.reason_placeholder') }}"
                           class="input-3d text-center text-xs">
                </div>

                <div class="flex justify-center pt-1">
                    <button type="submit" class="btn-3d btn-3d-navy text-xs">
                        {{ __('sales.product_show.record_adjustment') }}
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= Stock Movements History Ledger ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 p-4 text-center dark:border-slate-700/60">
            <h2 class="flex items-center justify-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                <span aria-hidden="true">📜</span> {{ __('sales.product_show.ledger_heading') }}
            </h2>
        </div>

        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>{{ __('sales.product_show.th_timestamp') }}</th>
                        <th>{{ __('sales.product_show.th_movement_type') }}</th>
                        <th>{{ __('sales.product_show.th_quantity') }}</th>
                        <th>{{ __('sales.product_show.th_before') }}</th>
                        <th>{{ __('sales.product_show.th_after') }}</th>
                        <th>{{ __('sales.product_show.th_unit_cost') }}</th>
                        <th>{{ __('sales.product_show.th_notes_ref') }}</th>
                        <th>{{ __('sales.product_show.th_by') }}</th>
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
                                    <div class="font-mono text-[10px] text-slate-400">{{ __('sales.product_show.ref_label') }} {{ $m->reference_type }} #{{ $m->reference_id }}</div>
                                @endif
                            </td>

                            <td class="whitespace-nowrap text-slate-500">
                                {{ $m->creator->name ?? __('sales.product_show.system') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-slate-400">
                                {{ __('sales.product_show.empty_movements') }}
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
