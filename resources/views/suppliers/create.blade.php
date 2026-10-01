@extends('layouts.app')

@section('title', __('sales.supplier_create.title'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('suppliers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('sales.supplier_create.breadcrumb_suppliers') }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.breadcrumb_new') }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>🏭 {{ __('sales.supplier_create.heading') }}</h1>
        <p>{{ __('sales.supplier_create.subtitle') }}</p>
    </div>

    @if($errors->any())
        <div class="glass-card mx-auto max-w-3xl border-red-200 bg-red-50/80 p-4 text-center text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-400">
            <ul class="space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('suppliers.store') }}" class="glass-card mx-auto max-w-3xl space-y-6 p-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="field-3d">
                <label for="code" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.code_label') }}</label>
                <input type="text" id="code" name="code" value="{{ old('code', $suggestedCode) }}" required
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="name" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.name_label') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="{{ __('sales.supplier_create.name_placeholder') }}"
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="contact_person" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.contact_label') }}</label>
                <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person') }}" placeholder="{{ __('sales.supplier_create.contact_placeholder') }}"
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="phone" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.phone_label') }}</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="{{ __('sales.supplier_create.phone_placeholder') }}"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="ntn_number" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.ntn_label') }}</label>
                <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number') }}" placeholder="{{ __('sales.supplier_create.ntn_placeholder') }}"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="strn_number" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.strn_label') }}</label>
                <input type="text" id="strn_number" name="strn_number" value="{{ old('strn_number') }}" placeholder="{{ __('sales.supplier_create.strn_placeholder') }}"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="opening_balance" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.opening_label') }}</label>
                <input type="number" step="0.01" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0.00') }}"
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="status" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.status_label') }}</label>
                <select id="status" name="status" class="input-3d text-center">
                    <option value="ACTIVE" @selected(old('status') === 'ACTIVE')>{{ __('sales.supplier_create.status_active') }}</option>
                    <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>{{ __('sales.supplier_create.status_inactive') }}</option>
                </select>
            </div>

            <div class="field-3d sm:col-span-2">
                <label for="address" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.address_label') }}</label>
                <textarea id="address" name="address" rows="2" placeholder="{{ __('sales.supplier_create.address_placeholder') }}"
                          class="input-3d text-center">{{ old('address') }}</textarea>
            </div>

            <div class="field-3d sm:col-span-2">
                <label for="notes" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.supplier_create.notes_label') }}</label>
                <textarea id="notes" name="notes" rows="2" placeholder="{{ __('sales.supplier_create.notes_placeholder') }}"
                          class="input-3d text-center">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 border-t border-slate-200/70 pt-5 dark:border-slate-700/60">
            <a href="{{ route('suppliers.index') }}" class="btn-3d btn-3d-ghost">
                {{ __('ui.actions.cancel') }}
            </a>
            <button type="submit" class="btn-3d btn-3d-primary">
                {{ __('sales.supplier_create.save_supplier') }}
            </button>
        </div>
    </form>
</div>
@endsection
