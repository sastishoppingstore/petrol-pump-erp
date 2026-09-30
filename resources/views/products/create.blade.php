@extends('layouts.app')

@section('title', 'Add Retail Product / Lubricant')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('products.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Products</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Add Product</span></li>
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <span class="text-red-600">🛢️</span> Add Retail Product / Lubricant
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Register ENEOS engine oils, oil filters, convenience items, or forecourt service products.
            </p>
        </div>
        <a href="{{ route('products.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 transition shadow-sm">
            ← Back to Products
        </a>
    </div>

    <form method="POST" action="{{ route('products.store') }}"
          x-data="{
              cost: '{{ old('cost_price', '0') }}',
              selling: '{{ old('selling_price', '0') }}',
              category: '{{ old('category', 'LUBRICANT') }}',

              get profit() {
                  const c = parseFloat(this.cost) || 0;
                  const s = parseFloat(this.selling) || 0;
                  return (s - c).toFixed(2);
              },
              get margin() {
                  const c = parseFloat(this.cost) || 0;
                  const s = parseFloat(this.selling) || 0;
                  if (s <= 0) return '0.00';
                  return (((s - c) / s) * 100).toFixed(2);
              }
          }"
          class="space-y-6">
        @csrf

        {{-- Basic Information --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-100 pb-2 dark:border-slate-800">
                Product Identification
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Product Code / SKU *</label>
                    <input type="text" name="code" value="{{ old('code') }}" required placeholder="e.g. ENEOS-5W30-4L"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono uppercase focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('code') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Barcode / EAN (Optional)</label>
                    <input type="text" name="barcode" value="{{ old('barcode') }}" placeholder="e.g. 4984245100012"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('barcode') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Product Name *</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="e.g. ENEOS Sustina 5W-30 Fully Synthetic (4L)"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('name') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Category *</label>
                    <select name="category" x-model="category" required
                            class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="LUBRICANT">ENEOS Lubricants / Motor Oils</option>
                        <option value="FILTER">Oil & Air Filters (Guard)</option>
                        <option value="TUCK_SHOP">Tuck Shop / Convenience Items</option>
                        <option value="SERVICE">Forecourt Services (Tyre, Alignment)</option>
                        <option value="CAR_WASH">Car Wash Services</option>
                        <option value="TYRE">Tyre Shop / Air Services</option>
                    </select>
                    @error('category') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Unit of Measure *</label>
                    <select name="unit" required class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="CAN" @selected(old('unit') === 'CAN')>CAN (4L/3L)</option>
                        <option value="BOTTLE" @selected(old('unit') === 'BOTTLE')>BOTTLE (1L/0.7L/Drinks)</option>
                        <option value="PIECE" @selected(old('unit') === 'PIECE')>PIECE (Filters/Accessories)</option>
                        <option value="PACK" @selected(old('unit') === 'PACK')>PACK</option>
                        <option value="SERVICE" @selected(old('unit') === 'SERVICE')>SERVICE</option>
                        <option value="LITRE" @selected(old('unit') === 'LITRE')>LITRE</option>
                    </select>
                    @error('unit') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Pricing & Margin Calculator --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-100 pb-2 dark:border-slate-800">
                Pricing & Profit Margin
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Cost Price (Rs.) *</label>
                    <input type="number" step="0.01" name="cost_price" x-model="cost" required placeholder="e.g. 8200.00"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('cost_price') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Selling Price / MRP (Rs.) *</label>
                    <input type="number" step="0.01" name="selling_price" x-model="selling" required placeholder="e.g. 9800.00"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('selling_price') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Margin Preview --}}
            <div class="rounded-lg bg-slate-50 p-4 border border-slate-200 dark:bg-slate-800/60 dark:border-slate-800">
                <div class="flex items-center justify-between text-xs">
                    <div>
                        <span class="text-slate-500">Gross Profit per Unit:</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-white ml-1">Rs. <span x-text="profit"></span></span>
                    </div>
                    <div>
                        <span class="text-slate-500">Markup Margin:</span>
                        <span class="font-mono font-bold text-emerald-600 dark:text-emerald-400 ml-1"><span x-text="margin"></span>%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inventory Tracking & Reorder Levels --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-100 pb-2 dark:border-slate-800">
                Stock Levels & Thresholds
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Opening / Initial Stock</label>
                    <input type="number" step="0.001" name="initial_stock" value="{{ old('initial_stock', '0') }}" placeholder="0.000"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('initial_stock') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Minimum Stock Level (Alert Threshold) *</label>
                    <input type="number" step="0.001" name="min_stock_level" value="{{ old('min_stock_level', '5') }}" required placeholder="5.000"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('min_stock_level') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Status *</label>
                    <select name="status" required class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="ACTIVE" @selected(old('status') !== 'INACTIVE')>Active</option>
                        <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>Inactive</option>
                    </select>
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Technical Specifications / Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Japanese fully synthetic engine oil for 10,000 km drain interval"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('products.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-red-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-red-700 shadow-md transition">
                Save Product →
            </button>
        </div>
    </form>
</div>
@endsection
