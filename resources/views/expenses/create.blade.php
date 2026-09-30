@extends('layouts.app')

@section('title', 'Record Expense Voucher / نیا خرچ')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="text-slate-500">Create Voucher</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl" x-data="{ method: '{{ old('payment_method', 'CASH') }}' }">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Record Expense Voucher / نیا خرچ واؤچر</h1>
        <p class="mt-1 text-sm text-slate-500">
            Vital Petroleum — Mehar Filling Station operating expense. Deducts cash from drawer or station bank balance.
        </p>
    </div>

    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="category_id" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Expense Category (مد) *</label>
                    <select id="category_id" name="category_id" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                        <option value="">Select category…</option>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat->id }}" @selected(old('category_id') == $cat->id)>
                                {{ $cat->name }} @if($cat->urdu_name) ({{ $cat->urdu_name }}) @endif
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="date" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Date (تاریخ) *</label>
                    <input type="date" id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required
                           class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    @error('date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="title" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Expense Title / Description *</label>
                <input type="text" id="title" name="title" value="{{ old('title') }}" required
                       placeholder="e.g. Generator Diesel (15 Litres), LESCO Bill, Staff Tea & Meals"
                       class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="amount" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Amount (Rs.) *</label>
                    <input type="number" step="0.01" min="1" id="amount" name="amount" value="{{ old('amount') }}" required
                           placeholder="e.g. 4500"
                           class="w-full rounded-lg border-slate-300 font-mono text-base font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('amount') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="payment_method" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Payment Source (ادائیگی بذریعہ) *</label>
                    <select id="payment_method" name="payment_method" x-model="method" required
                            class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                        <option value="CASH">Cash (Drawn from Shift Till Float)</option>
                        <option value="BANK_TRANSFER">Bank Transfer (Station Account)</option>
                        <option value="CHEQUE">Cheque</option>
                    </select>
                </div>
            </div>

            {{-- Conditional: Tied to open shift --}}
            <div x-show="method === 'CASH'" class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                <label for="shift_id" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Active Shift Drawer Float</label>
                <select id="shift_id" name="shift_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">None / Direct Cash</option>
                    @foreach ($shifts as $s)
                        <option value="{{ $s->id }}" @selected(old('shift_id') == $s->id)>
                            {{ $s->shift_number }} (Opened: {{ $s->opened_at?->format('d M H:i') }})
                        </option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] text-slate-400">Deducts directly from the cashier's expected closing cash drawer.</span>
            </div>

            {{-- Conditional: Tied to bank account --}}
            <div x-show="method === 'BANK_TRANSFER'" class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800/50">
                <label for="bank_account_id" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Station Bank Account *</label>
                <select id="bank_account_id" name="bank_account_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">Select bank account…</option>
                    @foreach ($bankAccounts as $acc)
                        <option value="{{ $acc->id }}" @selected(old('bank_account_id') == $acc->id)>
                            {{ $acc->bank?->short_name }} — {{ $acc->account_title }} (Bal: Rs. {{ number_format((float) $acc->currentBalance(), 2) }})
                        </option>
                    @endforeach
                </select>
                <span class="mt-1 block text-[11px] text-slate-400">Withdraws directly from the station's bank book balance.</span>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="payee" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Payee / Vendor Name</label>
                    <input type="text" id="payee" name="payee" value="{{ old('payee') }}"
                           placeholder="e.g. LESCO, Muhammad Hanif (Vendor)"
                           class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                </div>

                <div>
                    <label for="receipt_number" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Bill / Receipt / Reference #</label>
                    <input type="text" id="receipt_number" name="receipt_number" value="{{ old('receipt_number') }}"
                           placeholder="e.g. LESCO-0982-12"
                           class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                </div>
            </div>

            <div>
                <label for="attachment" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Voucher / Bill Photo (تصویری رسید)</label>
                <input type="file" id="attachment" name="attachment" accept="image/*,.pdf"
                       class="w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-2 file:text-xs file:font-semibold hover:file:bg-slate-200 dark:file:bg-slate-800 dark:file:text-slate-300">
                <span class="mt-1 block text-[11px] text-slate-400">Attach photo of physical receipt or signed cash voucher.</span>
                @error('attachment') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="notes" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Audit Notes / تفصیل</label>
                <textarea id="notes" name="notes" rows="2" placeholder="e.g. Approved by station owner via phone call"
                          class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">{{ old('notes') }}</textarea>
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <a href="{{ route('expenses.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                    Save Expense Voucher
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
