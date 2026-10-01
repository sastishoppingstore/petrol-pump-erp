@extends('layouts.app')

@section('title', 'Edit ' . $product->name)

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('products.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Products</a></li>
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('products.show', $product) }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ $product->code }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Edit</span></li>
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>✏️ Edit {{ $product->name }}</h1>
        <p>Update product pricing, barcode, alert thresholds and specifications.</p>
        <div class="page-actions">
            <a href="{{ route('products.show', $product) }}" class="btn-3d btn-3d-ghost btn-3d-sm">← Back to Product</a>
        </div>
    </div>

    <form method="POST" action="{{ route('products.update', $product) }}"
          x-data="{
              cost: '{{ old('cost_price', $product->cost_price) }}',
              selling: '{{ old('selling_price', $product->selling_price) }}',
              category: '{{ old('category', $product->category) }}',

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
        @method('PUT')

        {{-- Basic Information --}}
        <div class="glass-card space-y-4 p-6">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-sm font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300">
                Product Identification
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Product Code / SKU *</label>
                    <input type="text" name="code" value="{{ old('code', $product->code) }}" required
                           class="input-3d text-center font-mono text-xs uppercase">
                    @error('code') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Barcode / EAN</label>
                    <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}"
                           class="input-3d text-center font-mono text-xs">
                    @error('barcode') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d sm:col-span-2">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Product Name *</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required
                           class="input-3d text-center text-xs">
                    @error('name') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Category *</label>
                    <select name="category" x-model="category" required
                            class="input-3d text-center text-xs">
                        <option value="LUBRICANT" @selected(old('category', $product->category) === 'LUBRICANT')>ENEOS Lubricants / Motor Oils</option>
                        <option value="FILTER" @selected(old('category', $product->category) === 'FILTER')>Oil &amp; Air Filters (Guard)</option>
                        <option value="TUCK_SHOP" @selected(old('category', $product->category) === 'TUCK_SHOP')>Tuck Shop / Convenience Items</option>
                        <option value="SERVICE" @selected(old('category', $product->category) === 'SERVICE')>Forecourt Services</option>
                        <option value="CAR_WASH" @selected(old('category', $product->category) === 'CAR_WASH')>Car Wash Services</option>
                        <option value="TYRE" @selected(old('category', $product->category) === 'TYRE')>Tyre Services</option>
                    </select>
                    @error('category') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Unit of Measure *</label>
                    <select name="unit" required class="input-3d text-center text-xs">
                        <option value="CAN" @selected(old('unit', $product->unit) === 'CAN')>CAN (4L/3L)</option>
                        <option value="BOTTLE" @selected(old('unit', $product->unit) === 'BOTTLE')>BOTTLE (1L/0.7L/Drinks)</option>
                        <option value="PIECE" @selected(old('unit', $product->unit) === 'PIECE')>PIECE (Filters/Accessories)</option>
                        <option value="PACK" @selected(old('unit', $product->unit) === 'PACK')>PACK</option>
                        <option value="SERVICE" @selected(old('unit', $product->unit) === 'SERVICE')>SERVICE</option>
                        <option value="LITRE" @selected(old('unit', $product->unit) === 'LITRE')>LITRE</option>
                    </select>
                    @error('unit') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Pricing & Margin Calculator --}}
        <div class="glass-card space-y-4 p-6">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-sm font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300">
                Pricing &amp; Profit Margin
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Cost Price (Rs.) *</label>
                    <input type="number" step="0.01" name="cost_price" x-model="cost" required
                           class="input-3d text-center font-mono text-xs font-bold text-slate-900 dark:text-white">
                    @error('cost_price') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Selling Price / MRP (Rs.) *</label>
                    <input type="number" step="0.01" name="selling_price" x-model="selling" required
                           class="input-3d text-center font-mono text-xs font-bold text-slate-900 dark:text-white">
                    @error('selling_price') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Margin Preview --}}
            <div class="glass-card p-4">
                <div class="flex flex-col items-center justify-between gap-2 text-center text-xs sm:flex-row sm:text-left">
                    <div>
                        <span class="text-slate-500">Gross Profit per Unit:</span>
                        <span class="tabular ml-1 font-mono font-bold text-slate-900 dark:text-white">Rs. <span x-text="profit"></span></span>
                    </div>
                    <div>
                        <span class="text-slate-500">Markup Margin:</span>
                        <span class="tabular ml-1 font-mono font-bold text-emerald-600 dark:text-emerald-400"><span x-text="margin"></span>%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inventory Tracking & Reorder Levels --}}
        <div class="glass-card space-y-4 p-6">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-sm font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300">
                Stock Levels &amp; Thresholds
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Minimum Stock Level (Alert Threshold) *</label>
                    <input type="number" step="0.001" name="min_stock_level" value="{{ old('min_stock_level', $product->min_stock_level) }}" required
                           class="input-3d text-center font-mono text-xs">
                    @error('min_stock_level') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Status *</label>
                    <select name="status" required class="input-3d text-center text-xs">
                        <option value="ACTIVE" @selected(old('status', $product->status) === 'ACTIVE')>Active</option>
                        <option value="INACTIVE" @selected(old('status', $product->status) === 'INACTIVE')>Inactive</option>
                    </select>
                </div>

                <div class="field-3d sm:col-span-2">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Technical Specifications / Notes</label>
                    <input type="text" name="notes" value="{{ old('notes', $product->notes) }}"
                           class="input-3d text-center text-xs">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
            <a href="{{ route('products.show', $product) }}" class="btn-3d btn-3d-ghost">
                Cancel
            </a>
            <button type="submit" class="btn-3d btn-3d-primary">
                Update Product →
            </button>
        </div>
    </form>
</div>
@endsection
