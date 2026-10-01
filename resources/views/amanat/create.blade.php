@extends('layouts.app')

@section('title', __('admin.amanat.create_title'))
@section('breadcrumb')
    <li><a href="{{ route('amanat.index') }}" class="hover:text-navy-700 dark:hover:text-slate-300">{{ __('admin.amanat.title') }}</a></li>
    <li class="text-slate-500">{{ __('admin.amanat.new_entry') }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="page-head">
        <h1>{{ __('admin.amanat.create_title') }}</h1>
        <p>{{ __('admin.amanat.create_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('amanat.store') }}" class="glass-card space-y-5 p-6">
        @csrf

        <div class="field-3d">
            <label for="customer_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.amanat.customer') }} *</label>
            <select id="customer_id" name="customer_id" required class="input-3d w-full text-center text-sm">
                <option value="">{{ __('admin.amanat.select_customer') }}</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((int) old('customer_id', $selectedCustomerId) === $customer->id)>
                        {{ $customer->name }} ({{ $customer->code }})
                    </option>
                @endforeach
            </select>
            @error('customer_id') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="field-3d">
                <label for="type" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.amanat.type_label') }} *</label>
                <select id="type" name="type" required class="input-3d w-full text-center text-sm">
                    <option value="deposit" @selected(old('type') === 'deposit')>{{ __('admin.amanat.type_deposit') }}</option>
                    <option value="deduction" @selected(old('type') === 'deduction')>{{ __('admin.amanat.type_deduction') }}</option>
                    <option value="adjustment" @selected(old('type') === 'adjustment')>{{ __('admin.amanat.type_adjustment') }}</option>
                </select>
                @error('type') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="field-3d">
                <label for="amount" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.employees.amount_rs') }} *</label>
                <input id="amount" type="number" step="0.01" name="amount" value="{{ old('amount') }}" required
                       placeholder="0.00" class="input-3d tabular w-full text-center font-mono text-sm">
                <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('admin.amanat.amount_hint') }}</span>
                @error('amount') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="field-3d">
            <label for="reference" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.amanat.reference_label') }}</label>
            <input id="reference" type="text" name="reference" value="{{ old('reference') }}" maxlength="100"
                   placeholder="{{ __('admin.amanat.reference_placeholder') }}" class="input-3d w-full text-center text-sm">
            @error('reference') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="field-3d">
            <label for="note" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.amanat.note_label') }}</label>
            <textarea id="note" name="note" rows="3" maxlength="500" class="input-3d w-full text-sm"
                      placeholder="{{ __('admin.amanat.note_placeholder') }}">{{ old('note') }}</textarea>
            @error('note') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-center gap-2 pt-2">
            <a href="{{ route('amanat.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
            <button type="submit" class="btn-3d btn-3d-success">{{ __('admin.amanat.save_entry') }}</button>
        </div>
    </form>
</div>
@endsection
