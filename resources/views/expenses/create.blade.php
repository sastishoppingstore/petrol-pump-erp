@extends('layouts.app')

@section('title', __('finance.expenses.create_title'))
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('expenses.index') }}">{{ __('finance.expenses.expenses_word') }}</a></li>
    <li class="text-slate-500">{{ __('finance.expenses.create_voucher') }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl" x-data="{ method: '{{ old('payment_method', 'CASH') }}' }">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('finance.expenses.create_heading') }}</h1>
        <p>Vital Petroleum — Mehar Filling Station {{ __('finance.expenses.create_sub_tail') }}</p>
    </div>

    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data"
          class="glass-card p-6">
        @csrf

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label for="category_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.expense_category') }} *</label>
                    <select id="category_id" name="category_id" required class="input-3d text-center text-sm">
                        <option value="">{{ __('finance.expenses.select_category') }}</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                                {{ $cat->name }} @if($cat->urdu_name) ({{ $cat->urdu_name }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-center text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="field-3d">
                    <label for="date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.common.date') }} *</label>
                    <input type="date" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                           class="input-3d text-center text-sm">
                    @error('date') <p class="mt-1 text-center text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="field-3d">
                <label for="title" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.title_label') }} *</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                       placeholder="{{ __('finance.expenses.ph_title') }}"
                       class="input-3d text-center text-sm">
                @error('title') <p class="mt-1 text-center text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label for="amount" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.banks.amount_rs') }} *</label>
                    <input type="number" step="0.01" min="1" id="amount" name="amount" value="{{ old('amount') }}" required
                           placeholder="{{ __('finance.expenses.ph_4500') }}"
                           class="input-3d text-center font-mono text-base font-bold text-slate-900 dark:text-white">
                    @error('amount') <p class="mt-1 text-center text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="field-3d">
                    <label for="payment_method" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.payment_source') }} *</label>
                    <select id="payment_method" name="payment_method" x-model="method" required
                            class="input-3d text-center text-sm">
                        <option value="CASH">{{ __('finance.expenses.opt_cash') }}</option>
                        <option value="BANK_TRANSFER">{{ __('finance.expenses.opt_bank') }}</option>
                        <option value="CHEQUE">{{ __('finance.cheques.cheque_word') }}</option>
                    </select>
                </div>
            </div>

            {{-- Conditional: Tied to open shift --}}
            <div x-show="method === 'CASH'" class="glass-card p-4">
                <div class="field-3d">
                    <label for="shift_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.shift_float') }}</label>
                    <select id="shift_id" name="shift_id" class="input-3d text-center text-sm">
                        <option value="">{{ __('finance.expenses.none_direct') }}</option>
                        @foreach ($shifts as $s)
                            <option value="{{ $s->id }}" @selected(old('shift_id') == $s->id)>
                                {{ $s->shift_number }} ({{ __('finance.expenses.opened_word') }}: {{ $s->opened_at?->format('d M H:i') }})
                            </option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('finance.expenses.shift_hint') }}</span>
                </div>
            </div>

            {{-- Conditional: Tied to bank account --}}
            <div x-show="method === 'BANK_TRANSFER'" class="glass-card p-4">
                <div class="field-3d">
                    <label for="bank_account_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.station_bank_account') }} *</label>
                    <select id="bank_account_id" name="bank_account_id" class="input-3d text-center text-sm">
                        <option value="">{{ __('finance.bank_deposits.select_bank_account') }}</option>
                        @foreach ($bankAccounts as $acc)
                            <option value="{{ $acc->id }}" @selected(old('bank_account_id') == $acc->id)>
                                {{ $acc->bank?->short_name }} — {{ $acc->account_title }} ({{ __('finance.banks.bal') }}: Rs. {{ number_format((float) $acc->currentBalance(), 2) }})
                            </option>
                        @endforeach
                    </select>
                    <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('finance.expenses.bank_hint') }}</span>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label for="payee" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.payee_vendor') }}</label>
                    <input type="text" id="payee" name="payee" value="{{ old('payee') }}"
                           placeholder="{{ __('finance.expenses.ph_payee') }}"
                           class="input-3d text-center text-sm">
                </div>

                <div class="field-3d">
                    <label for="receipt_number" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.bill_receipt_ref') }}</label>
                    <input type="text" id="receipt_number" name="receipt_number" value="{{ old('receipt_number') }}"
                           placeholder="{{ __('finance.expenses.ph_receipt_no') }}"
                           class="input-3d text-center text-sm">
                </div>
            </div>

            <div class="field-3d">
                <label for="attachment" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.bill_photo') }}</label>
                <input type="file" id="attachment" name="attachment" accept="image/*,.pdf"
                       class="input-3d text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold hover:file:bg-slate-200">
                <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('finance.expenses.photo_hint') }}</span>
                @error('attachment') <p class="mt-1 text-center text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="notes" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.expenses.audit_notes') }}</label>
                <textarea id="notes" name="notes" rows="2" placeholder="{{ __('finance.expenses.ph_notes') }}"
                          class="input-3d text-center text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                <a href="{{ route('expenses.index') }}" class="btn-3d btn-3d-ghost">
                    {{ __('ui.actions.cancel') }}
                </a>
                <button type="submit" class="btn-3d btn-3d-primary">
                    {{ __('finance.expenses.save_voucher') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
