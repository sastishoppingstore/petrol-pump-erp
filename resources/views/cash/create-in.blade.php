@extends('layouts.app')

@section('title', __('finance.cash.in_title') . ' — Vital Petroleum')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('cash.index') }}">{{ __('finance.cash.roznamcha_word') }}</a></li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.in_crumb') }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-6">

    <div class="rounded-2xl bg-gradient-to-br from-emerald-500 to-emerald-800 p-5 text-center text-white shadow-3d">
        <span class="rounded bg-white/20 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white">CRV</span>
        <h1 class="mt-2 text-xl font-bold tracking-tight">{{ __('finance.cash.in_heading') }}</h1>
        <p class="text-xs text-emerald-100">Vital Petroleum — Mehar Filling Station, Sheikhupura</p>
        <div class="mt-2 text-3xl">📥</div>
    </div>

    <div class="glass-card card-3d p-6">
        <form method="POST" action="{{ route('cash.store-in') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            @if ($errors->any())
                <div class="rounded-xl bg-red-50 p-4 text-center text-xs text-red-800 dark:bg-red-950/40 dark:text-red-300">
                    <ul class="list-disc space-y-1 pl-4 text-left">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Branch & Shift --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.station_branch') }} <span class="text-vital-primary">*</span></label>
                    <select name="branch_id" required class="input-3d mt-1 w-full text-center text-xs">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.active_shift') }}</label>
                    <div class="input-3d mt-1 p-2 text-center text-xs font-medium text-slate-700 dark:text-slate-300">
                        @if ($activeShift)
                            {{ __('finance.cash.shift_word') }}: <strong>{{ $activeShift->shift_number }}</strong>
                            <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                        @else
                            <span class="text-slate-500">{{ __('finance.cash.general_drawer') }}</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Amount (Rs.) --}}
            <div class="field-3d">
                <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.amount_received') }} <span class="text-vital-primary">*</span></label>
                <div class="input-3d mt-1 flex items-center px-3 py-2">
                    <span class="mr-2 font-bold text-slate-500">Rs.</span>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required
                           placeholder="0.00"
                           class="tabular w-full bg-transparent text-center font-mono text-xl font-black text-slate-900 focus:outline-none dark:text-white">
                </div>
            </div>

            {{-- Category & Person Name --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.receipt_category') }} <span class="text-vital-primary">*</span></label>
                    <select name="category" required class="input-3d mt-1 w-full text-center text-xs">
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" @selected(old('category') === $catKey)>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.received_from') }} <span class="text-vital-primary">*</span></label>
                    <input type="text" name="person_name" value="{{ old('person_name') }}" required
                           placeholder="{{ __('finance.cash.ph_person_in') }}"
                           class="input-3d mt-1 w-full text-center text-xs">
                </div>
            </div>

            {{-- Reference Number & Entry Date --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.reference_slip') }}</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                           placeholder="{{ __('finance.cash.ph_reference_in') }}"
                           class="input-3d mt-1 w-full text-center text-xs">
                </div>
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.transaction_date') }}</label>
                    <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}"
                           class="input-3d mt-1 w-full text-center text-xs">
                </div>
            </div>

            {{-- Notes --}}
            <div class="field-3d">
                <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.notes_remarks') }}</label>
                <textarea name="notes" rows="2" placeholder="{{ __('finance.cash.ph_notes') }}"
                          class="input-3d mt-1 w-full text-center text-xs">{{ old('notes') }}</textarea>
            </div>

            {{-- Attachment --}}
            <div class="field-3d">
                <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('finance.cash.attachment_in') }}</label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                       class="input-3d mt-1 block w-full text-xs text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 border-t border-slate-200 pt-3 dark:border-slate-800">
                <a href="{{ route('cash.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                    {{ __('ui.actions.cancel') }}
                </a>
                <button type="submit" class="btn-3d btn-3d-success btn-3d-sm">
                    {{ __('finance.cash.save_cash_in') }}
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
