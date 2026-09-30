@extends('layouts.app')

@section('title', 'Add Bank Account')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Add account</li>
@endsection

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-4 text-xl font-bold">{{ $account->exists ? 'Edit Bank Account' : 'Add Bank Account' }}</h1>

        <form method="POST"
              action="{{ $account->exists ? route('bank-accounts.update', $account) : route('bank-accounts.store') }}"
              class="rounded-lg border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            @csrf
            @if ($account->exists) @method('PUT') @endif

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="bank_id" class="mb-1 block text-sm font-medium">Bank <span class="text-red-600">*</span></label>
                    <select id="bank_id" name="bank_id" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800" required>
                        <option value="">Select bank…</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}" @selected((string) old('bank_id', $account->bank_id) === (string) $bank->id)>
                                {{ $bank->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('bank_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="account_title" class="mb-1 block text-sm font-medium">Account title <span class="text-red-600">*</span></label>
                    <input type="text" id="account_title" name="account_title"
                           value="{{ old('account_title', $account->account_title) }}"
                           class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800" required maxlength="150">
                    @error('account_title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="account_number" class="mb-1 block text-sm font-medium">Account number <span class="text-red-600">*</span></label>
                    <input type="text" id="account_number" name="account_number"
                           value="{{ old('account_number', $account->account_number) }}"
                           class="tabular w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800" required maxlength="30">
                    @error('account_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="iban" class="mb-1 block text-sm font-medium">IBAN</label>
                    <input type="text" id="iban" name="iban" value="{{ old('iban', $account->iban) }}"
                           class="tabular w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800" maxlength="34">
                </div>

                <div>
                    <label for="account_type" class="mb-1 block text-sm font-medium">Account type</label>
                    <select id="account_type" name="account_type" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                        @foreach (['CURRENT', 'SAVINGS'] as $t)
                            <option value="{{ $t }}" @selected(old('account_type', $account->account_type ?? 'CURRENT') === $t)>
                                {{ ucfirst(strtolower($t)) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="opening_balance" class="mb-1 block text-sm font-medium">Opening balance</label>
                    <input type="number" step="0.01" min="0" id="opening_balance" name="opening_balance"
                           value="{{ old('opening_balance', $account->opening_balance) }}"
                           class="tabular w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                    @error('opening_balance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="status" class="mb-1 block text-sm font-medium">Status</label>
                    <select id="status" name="status" class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                        @foreach (['ACTIVE', 'INACTIVE'] as $s)
                            <option value="{{ $s }}" @selected(old('status', $account->status ?? 'ACTIVE') === $s)>{{ ucfirst(strtolower($s)) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <button type="submit" class="rounded-md bg-navy-800 px-4 py-2 font-semibold text-white hover:bg-navy-900 dark:bg-navy-700">
                    {{ $account->exists ? 'Save Changes' : 'Add Account' }}
                </button>
                <a href="{{ route('banks.index') }}" class="rounded-md border border-slate-300 px-4 py-2 dark:border-slate-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
