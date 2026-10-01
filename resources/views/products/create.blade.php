@extends('layouts.app')

@section('title', __('sales.product_create.title'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('products.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('sales.product_create.breadcrumb_products') }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('sales.product_create.breadcrumb_add') }}</span></li>
@endsection

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('sales.product_create.heading') }}</h1>
        <p>{{ __('sales.product_create.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('products.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('sales.product_create.back_to_products') }}</a>
        </div>
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
        <div class="glass-card space-y-4 p-6">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-sm font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300">
                {{ __('sales.product_create.identification') }}
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.code_label') }}</label>
                    <input type="text" name="code" value="{{ old('code') }}" required placeholder="{{ __('sales.product_create.code_placeholder') }}"
                           class="input-3d text-center font-mono text-xs uppercase">
                    @error('code') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.barcode_label') }}</label>
                    <input type="text" name="barcode" value="{{ old('barcode') }}" placeholder="{{ __('sales.product_create.barcode_placeholder') }}"
                           class="input-3d text-center font-mono text-xs">
                    @error('barcode') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d sm:col-span-2">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.name_label') }}</label>
                    <input type="text" name="name" value="{{ old('name') }}" required placeholder="{{ __('sales.product_create.name_placeholder') }}"
                           class="input-3d text-center text-xs">
                    @error('name') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.category_label') }}</label>
                    <select name="category" x-model="category" required
                            class="input-3d text-center text-xs">
                        <option value="LUBRICANT">{{ __('sales.product_create.cat_lubricant') }}</option>
                        <option value="FILTER">{{ __('sales.product_create.cat_filter') }}</option>
                        <option value="TUCK_SHOP">{{ __('sales.product_create.cat_tuck') }}</option>
                        <option value="SERVICE">{{ __('sales.product_create.cat_service') }}</option>
                        <option value="CAR_WASH">{{ __('sales.product_create.cat_car_wash') }}</option>
                        <option value="TYRE">{{ __('sales.product_create.cat_tyre') }}</option>
                    </select>
                    @error('category') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.unit_label') }}</label>
                    <select name="unit" required class="input-3d text-center text-xs">
                        <option value="CAN" @selected(old('unit') === 'CAN')>{{ __('sales.product_create.unit_can') }}</option>
                        <option value="BOTTLE" @selected(old('unit') === 'BOTTLE')>{{ __('sales.product_create.unit_bottle') }}</option>
                        <option value="PIECE" @selected(old('unit') === 'PIECE')>{{ __('sales.product_create.unit_piece') }}</option>
                        <option value="PACK" @selected(old('unit') === 'PACK')>{{ __('sales.product_create.unit_pack') }}</option>
                        <option value="SERVICE" @selected(old('unit') === 'SERVICE')>{{ __('sales.product_create.unit_service') }}</option>
                        <option value="LITRE" @selected(old('unit') === 'LITRE')>{{ __('sales.product_create.unit_litre') }}</option>
                    </select>
                    @error('unit') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Pricing & Margin Calculator --}}
        <div class="glass-card space-y-4 p-6">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-sm font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300">
                {{ __('sales.product_create.pricing_heading') }}
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.cost_label') }}</label>
                    <input type="number" step="0.01" name="cost_price" x-model="cost" required placeholder="{{ __('sales.product_create.cost_placeholder') }}"
                           class="input-3d text-center font-mono text-xs font-bold text-slate-900 dark:text-white">
                    @error('cost_price') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.selling_label') }}</label>
                    <input type="number" step="0.01" name="selling_price" x-model="selling" required placeholder="{{ __('sales.product_create.selling_placeholder') }}"
                           class="input-3d text-center font-mono text-xs font-bold text-slate-900 dark:text-white">
                    @error('selling_price') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            {{-- Margin Preview --}}
            <div class="glass-card p-4">
                <div class="flex flex-col items-center justify-between gap-2 text-center text-xs sm:flex-row sm:text-left">
                    <div>
                        <span class="text-slate-500">{{ __('sales.product_create.gross_profit') }}</span>
                        <span class="tabular ml-1 font-mono font-bold text-slate-900 dark:text-white">Rs. <span x-text="profit"></span></span>
                    </div>
                    <div>
                        <span class="text-slate-500">{{ __('sales.product_create.markup_margin') }}</span>
                        <span class="tabular ml-1 font-mono font-bold text-emerald-600 dark:text-emerald-400"><span x-text="margin"></span>%</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Inventory Tracking & Reorder Levels --}}
        <div class="glass-card space-y-4 p-6">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-sm font-bold uppercase tracking-wider text-slate-700 dark:border-slate-700/60 dark:text-slate-300">
                {{ __('sales.product_create.stock_heading') }}
            </h2>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.initial_stock_label') }}</label>
                    <input type="number" step="0.001" name="initial_stock" value="{{ old('initial_stock', '0') }}" placeholder="0.000"
                           class="input-3d text-center font-mono text-xs">
                    @error('initial_stock') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.min_label') }}</label>
                    <input type="number" step="0.001" name="min_stock_level" value="{{ old('min_stock_level', '5') }}" required placeholder="5.000"
                           class="input-3d text-center font-mono text-xs">
                    @error('min_stock_level') <span class="block text-center text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div class="field-3d">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.status_label') }}</label>
                    <select name="status" required class="input-3d text-center text-xs">
                        <option value="ACTIVE" @selected(old('status') !== 'INACTIVE')>{{ __('sales.product_create.status_active') }}</option>
                        <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>{{ __('sales.product_create.status_inactive') }}</option>
                    </select>
                </div>

                <div class="field-3d sm:col-span-3">
                    <label class="mb-1 block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">{{ __('sales.product_create.notes_label') }}</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="{{ __('sales.product_create.notes_placeholder') }}"
                           class="input-3d text-center text-xs">
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
            <a href="{{ route('products.index') }}" class="btn-3d btn-3d-ghost">
                {{ __('ui.actions.cancel') }}
            </a>
            <button type="submit" class="btn-3d btn-3d-primary">
                {{ __('sales.product_create.save_product') }}
            </button>
        </div>
    </form>
</div>
@endsection
