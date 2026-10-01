@extends('layouts.app')

@section('title', __('finance.bank_account.add_title'))
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">{{ __('finance.common.banks_word') }}</a></li>
    <li class="text-slate-500">{{ __('finance.bank_account.add_account_crumb') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ $account->exists ? __('finance.bank_account.edit_title') : __('finance.bank_account.add_title') }}</h1>
        <p>{{ __('finance.bank_account.form_sub') }}</p>
    </div>

    <form method="POST"
          action="{{ $account->exists ? route('bank-accounts.update', $account) : route('bank-accounts.store') }}"
          class="glass-card card-3d mx-auto max-w-3xl p-6">
        @csrf
        @if ($account->exists) @method('PUT') @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="field-3d sm:col-span-2">
                <label for="bank_id" class="mb-1 block text-center text-sm font-medium">{{ __('finance.bank_account.bank') }} <span class="text-vital-primary">*</span></label>
                <select id="bank_id" name="bank_id" class="input-3d w-full text-center" required>
                    <option value="">{{ __('finance.bank_account.select_bank') }}</option>
                    @foreach ($banks as $bank)
                        <option value="{{ $bank->id }}" @selected((string) old('bank_id', $account->bank_id) === (string) $bank->id)>
                            {{ $bank->name }}
                        </option>
                    @endforeach
                </select>
                @error('bank_id') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="account_title" class="mb-1 block text-center text-sm font-medium">{{ __('finance.bank_account.account_title') }} <span class="text-vital-primary">*</span></label>
                <input type="text" id="account_title" name="account_title"
                       value="{{ old('account_title', $account->account_title) }}"
                       class="input-3d w-full text-center" required maxlength="150">
                @error('account_title') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="account_number" class="mb-1 block text-center text-sm font-medium">{{ __('finance.bank_account.account_number') }} <span class="text-vital-primary">*</span></label>
                <input type="text" id="account_number" name="account_number"
                       value="{{ old('account_number', $account->account_number) }}"
                       class="input-3d tabular w-full text-center" required maxlength="30">
                @error('account_number') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="iban" class="mb-1 block text-center text-sm font-medium">{{ __('finance.bank_account.iban_label') }}</label>
                <input type="text" id="iban" name="iban" value="{{ old('iban', $account->iban) }}"
                       placeholder="PK36MEZN0001234567890123"
                       class="input-3d tabular w-full text-center" maxlength="34">
                @error('iban') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="account_type" class="mb-1 block text-center text-sm font-medium">{{ __('finance.bank_account.account_type') }}</label>
                <select id="account_type" name="account_type" class="input-3d w-full text-center">
                    @foreach (['CURRENT', 'SAVINGS'] as $t)
                        <option value="{{ $t }}" @selected(old('account_type', $account->account_type ?? 'CURRENT') === $t)>
                            {{ __('finance.bank_account.type_' . strtolower($t)) }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="field-3d">
                <label for="opening_balance" class="mb-1 block text-center text-sm font-medium">{{ __('finance.bank_account.opening_balance') }}</label>
                <input type="number" step="0.01" min="0" id="opening_balance" name="opening_balance"
                       value="{{ old('opening_balance', $account->opening_balance) }}"
                       class="input-3d tabular w-full text-center">
                @error('opening_balance') <p class="mt-1 text-center text-xs text-vital-primary">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="status" class="mb-1 block text-center text-sm font-medium">{{ __('finance.common.status') }}</label>
                <select id="status" name="status" class="input-3d w-full text-center">
                    @foreach (['ACTIVE', 'INACTIVE'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $account->status ?? 'ACTIVE') === $s)>{{ __('finance.bank_account.status_' . strtolower($s)) }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="mt-6 flex flex-wrap justify-center gap-3">
            <button type="submit" class="btn-3d btn-3d-navy">
                {{ $account->exists ? __('finance.bank_account.save_changes') : __('finance.banks.add_account') }}
            </button>
            <a href="{{ route('banks.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
        </div>
    </form>
@endsection
