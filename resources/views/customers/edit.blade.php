@extends('layouts.app')

@section('title', __('sales.customer_edit.title_prefix') . ' ' . $customer->name)

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('sales.customer_edit.breadcrumb_customers') }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.show', $customer) }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ $customer->code }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('ui.actions.edit') }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>✏️ {{ __('sales.customer_edit.heading') }}</h1>
        <p>{{ __('sales.customer_edit.subtitle_prefix') }} {{ $customer->name }}.</p>
        <div class="page-actions">
            <a href="{{ route('customers.show', $customer) }}" class="btn-3d btn-3d-ghost btn-3d-sm">← {{ __('sales.customer_edit.back_to_profile') }}</a>
        </div>
    </div>

    @if($errors->any())
        <div class="glass-card mx-auto max-w-3xl border-red-200 bg-red-50/80 p-4 text-center text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-400">
            <div class="mb-1 font-bold">{{ __('sales.customer_edit.errors_heading') }}</div>
            <ul class="space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="glass-card mx-auto max-w-3xl space-y-6 p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="field-3d">
                <label for="code" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.code_label') }}</label>
                <input type="text" id="code" name="code" value="{{ old('code', $customer->code) }}" required
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="name" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.name_label') }}</label>
                <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="phone" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.phone_label') }}</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="cnic" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.cnic_label') }}</label>
                <input type="text" id="cnic" name="cnic" value="{{ old('cnic', $customer->cnic) }}"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="credit_limit" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.credit_limit_label') }}</label>
                <input type="number" step="0.01" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" required
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="ntn_number" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.ntn_label') }}</label>
                <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number', $customer->ntn_number) }}"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="email" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.email_label') }}</label>
                <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}"
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="status" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.status_label') }}</label>
                <select id="status" name="status" class="input-3d text-center">
                    <option value="ACTIVE" @selected(old('status', $customer->status) === 'ACTIVE')>{{ __('sales.customer_edit.status_active') }}</option>
                    <option value="INACTIVE" @selected(old('status', $customer->status) === 'INACTIVE')>{{ __('sales.customer_edit.status_inactive_option') }}</option>
                </select>
            </div>

            <div class="field-3d sm:col-span-2">
                <label for="address" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.address_label') }}</label>
                <textarea id="address" name="address" rows="2"
                          class="input-3d text-center">{{ old('address', $customer->address) }}</textarea>
            </div>

            <div class="flex items-center justify-center gap-2">
                <input type="checkbox" id="is_tax_liable" name="is_tax_liable" value="1" @checked(old('is_tax_liable', $customer->is_tax_liable))
                       class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                <label for="is_tax_liable" class="text-sm font-medium text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.tax_liable_label') }}</label>
            </div>

            <div class="field-3d sm:col-span-2">
                <label for="notes" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.customer_edit.notes_label') }}</label>
                <textarea id="notes" name="notes" rows="2"
                          class="input-3d text-center">{{ old('notes', $customer->notes) }}</textarea>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 border-t border-slate-200/70 pt-5 dark:border-slate-700/60">
            <a href="{{ route('customers.show', $customer) }}" class="btn-3d btn-3d-ghost">
                {{ __('ui.actions.cancel') }}
            </a>
            <button type="submit" class="btn-3d btn-3d-primary">
                {{ __('sales.customer_edit.update_button') }}
            </button>
        </div>
    </form>
</div>
@endsection
