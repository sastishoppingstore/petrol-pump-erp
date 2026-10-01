@extends('layouts.app')

@section('title', $type === 'RECEIVED' ? __('finance.cheques.receive_title') : __('finance.cheques.issue_title'))
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('cheques.index') }}">{{ __('finance.cheques.cheques_word') }}</a></li>
    <li class="text-slate-500">{{ $type === 'RECEIVED' ? __('finance.cheques.receive_word') : __('finance.cheques.issue_word') }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-3xl">
    <div class="page-head">
        <h1>{{ $type === 'RECEIVED' ? __('finance.cheques.receive_heading') : __('finance.cheques.issue_heading') }}</h1>
        <p>{{ $type === 'RECEIVED' ? __('finance.cheques.receive_sub') : __('finance.cheques.issue_sub') }}</p>
        <div class="page-actions">
            <a href="{{ route('cheques.create', ['type' => $type === 'RECEIVED' ? 'ISSUED' : 'RECEIVED']) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                {{ __('finance.cheques.switch_to') }} {{ $type === 'RECEIVED' ? __('finance.cheques.issue_word') : __('finance.cheques.receive_word') }}
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('cheques.store') }}" enctype="multipart/form-data"
          class="glass-card card-3d p-6">
        @csrf
        <input type="hidden" name="type" value="{{ $type }}">

        <div class="space-y-4">
            @if ($type === 'RECEIVED')
                <div class="field-3d">
                    <label for="customer_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.customer_label') }} *</label>
                    <select id="customer_id" name="customer_id" required class="input-3d w-full text-center text-sm">
                        <option value="">{{ __('finance.cheques.select_customer') }}</option>
                        @foreach ($customers as $c)
                            <option value="{{ $c->id }}" @selected(old('customer_id') == $c->id)>
                                {{ $c->name }} ({{ $c->company_name ?? __('finance.cheques.individual') }})
                            </option>
                        @endforeach
                    </select>
                    @error('customer_id') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
                </div>

                <div class="field-3d">
                    <label for="bank_name" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.drawee_bank_label') }} *</label>
                    <input type="text" id="bank_name" name="bank_name" value="{{ old('bank_name') }}" required
                           placeholder="{{ __('finance.cheques.ph_bank_names') }}"
                           class="input-3d w-full text-center text-sm">
                    @error('bank_name') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
                </div>
            @else
                <div class="field-3d">
                    <label for="supplier_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.supplier_label') }} *</label>
                    <select id="supplier_id" name="supplier_id" required class="input-3d w-full text-center text-sm">
                        <option value="">{{ __('finance.cheques.select_supplier') }}</option>
                        @foreach ($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id') == $s->id)>
                                {{ $s->name }} ({{ $s->company_name ?? 'OMC' }})
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
                </div>

                <div class="field-3d">
                    <label for="bank_account_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.drawn_on') }} *</label>
                    <select id="bank_account_id" name="bank_account_id" required class="input-3d w-full text-center text-sm">
                        <option value="">{{ __('finance.cheques.select_station_account') }}</option>
                        @foreach ($bankAccounts as $acc)
                            <option value="{{ $acc->id }}" @selected(old('bank_account_id') == $acc->id)>
                                {{ $acc->bank?->name }} — {{ $acc->account_title }} ({{ __('finance.banks.bal') }}: Rs. {{ number_format((float) $acc->currentBalance(), 2) }})
                            </option>
                        @endforeach
                    </select>
                    @error('bank_account_id') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
                </div>
            @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label for="cheque_number" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.cheque_number') }} *</label>
                    <input type="text" id="cheque_number" name="cheque_number" value="{{ old('cheque_number') }}" required
                           placeholder="{{ __('finance.cheques.ph_cheque_no') }}"
                           class="input-3d tabular w-full text-center font-mono text-sm">
                    @error('cheque_number') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
                </div>

                <div class="field-3d">
                    <label for="amount" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.cheque_amount') }} *</label>
                    <input type="number" step="0.01" min="1" id="amount" name="amount" value="{{ old('amount') }}" required
                           placeholder="{{ __('finance.cheques.ph_150000') }}"
                           class="input-3d tabular w-full text-center font-mono text-base font-bold text-slate-900 dark:text-white">
                    @error('amount') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label for="cheque_date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.date_on_cheque') }} *</label>
                    <input type="date" id="cheque_date" name="cheque_date" value="{{ old('cheque_date', date('Y-m-d')) }}" required
                           class="input-3d w-full text-center text-sm">
                    @error('cheque_date') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
                </div>

                <div class="field-3d">
                    <label for="due_date" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.due_maturity') }}</label>
                    <input type="date" id="due_date" name="due_date" value="{{ old('due_date', date('Y-m-d')) }}"
                           class="input-3d w-full text-center text-sm">
                    <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('finance.cheques.pdc_hint') }}</span>
                    @error('due_date') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="field-3d">
                <label for="payee_name" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.payee_name') }}</label>
                <input type="text" id="payee_name" name="payee_name" value="{{ old('payee_name') }}"
                       placeholder="{{ __('finance.cheques.ph_payee') }}"
                       class="input-3d w-full text-center text-sm">
            </div>

            <div class="field-3d">
                <label for="image" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cheques.cheque_photo') }}</label>
                <input type="file" id="image" name="image" accept="image/*,.pdf"
                       class="input-3d w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('finance.cheques.photo_hint') }}</span>
                @error('image') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="notes" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.cash.notes_remarks') }}</label>
                <textarea id="notes" name="notes" rows="2" placeholder="{{ __('finance.cheques.ph_notes') }}"
                          class="input-3d w-full text-center text-sm">{{ old('notes') }}</textarea>
            </div>

            <div class="mt-6 flex flex-wrap items-center justify-center gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <a href="{{ route('cheques.index') }}" class="btn-3d btn-3d-ghost">
                    {{ __('ui.actions.cancel') }}
                </a>
                <button type="submit" class="btn-3d btn-3d-primary">
                    {{ $type === 'RECEIVED' ? __('finance.cheques.save_customer') : __('finance.cheques.save_issued') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
