@extends('layouts.app')

@section('title', 'Record Cash Out (CPV) — Vital Petroleum')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('cash.index') }}">Roznamcha</a></li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Cash Out (خرچہ / ادائیگی)</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl space-y-6" x-data="{ availableCash: {{ (float)$availableCash }}, amount: '{{ old('amount', '') }}' }">

    <div class="rounded-xl border border-red-200 bg-gradient-to-r from-red-600 to-red-800 p-5 text-white shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <span class="rounded bg-white/20 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white">CPV</span>
                <h1 class="mt-1 text-xl font-bold tracking-tight">Record Cash Outflow (کیش ادائیگی / خرچہ واؤچر)</h1>
                <p class="text-xs text-red-100">Vital Petroleum — Mehar Filling Station, Sheikhupura</p>
            </div>
            <span class="text-3xl">📤</span>
        </div>
    </div>

    {{-- Available Cash Banner (Strict Non-Negative Rule) --}}
    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-200">
        <div class="flex items-center justify-between">
            <div>
                <span class="font-semibold uppercase tracking-wider text-amber-800 dark:text-amber-400">Current Available Till Cash:</span>
                <div class="font-mono text-xl font-black text-amber-900 dark:text-amber-200">
                    {{ \App\Support\PakistaniCurrency::format($availableCash) }}
                </div>
            </div>
            <div class="text-right">
                <span class="rounded bg-amber-200 px-2 py-1 text-[10px] font-bold uppercase text-amber-900 dark:bg-amber-900 dark:text-amber-200">
                    Strict Rule: Cash cannot go negative
                </span>
                <p class="mt-1 text-[11px] text-amber-700 dark:text-amber-400">Payout cannot exceed drawer cash.</p>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="POST" action="{{ route('cash.store-out') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf

            @if ($errors->any())
                <div class="rounded-lg bg-red-50 p-4 text-xs text-red-800 dark:bg-red-950/40 dark:text-red-300">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Branch & Shift --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Station / Branch <span class="text-red-500">*</span></label>
                    <select name="branch_id" required class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Active Shift</label>
                    <div class="mt-1 rounded-lg border border-slate-200 bg-slate-50 p-2 text-xs font-medium text-slate-700 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300">
                        @if ($activeShift)
                            Shift: <strong>{{ $activeShift->shift_number }}</strong>
                            <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                        @else
                            <span class="text-slate-500">General Station Drawer (No open shift)</span>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Amount (Rs.) --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Amount to Pay Out (روپے) <span class="text-red-500">*</span></label>
                <div class="mt-1 flex items-center rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800">
                    <span class="font-bold text-slate-500 mr-2">Rs.</span>
                    <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" required
                           placeholder="0.00"
                           class="w-full bg-transparent font-mono text-xl font-black text-slate-900 focus:outline-none dark:text-white">
                </div>
                <template x-if="parseFloat(amount || 0) > availableCash">
                    <p class="mt-1 text-xs font-semibold text-red-600">
                        ⚠️ Error: Payout amount exceeds available cash (Rs. {{ number_format((float)$availableCash, 2) }}). Cash cannot go negative!
                    </p>
                </template>
            </div>

            {{-- Category & Paid To Person Name --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Payment Category <span class="text-red-500">*</span></label>
                    <select name="category" required class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" @selected(old('category') === $catKey)>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Paid To / Recipient (وصول کنندہ کا نام) <span class="text-red-500">*</span></label>
                    <input type="text" name="person_name" value="{{ old('person_name') }}" required
                           placeholder="e.g. Electrician / PSO / Driver / Manager"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">
                </div>
            </div>

            {{-- Reference Number & Entry Date --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Reference / Bill / Voucher #</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                           placeholder="Optional bill or invoice #"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Transaction Date</label>
                    <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">
                </div>
            </div>

            {{-- Notes --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Expense Justification / Notes (تفصیل خرچ)</label>
                <textarea name="notes" rows="2" placeholder="Explain the purpose of payment..."
                          class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">{{ old('notes') }}</textarea>
            </div>

            {{-- Attachment --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Payment Receipt / Bill Proof (Scan/Photo)</label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                       class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('cash.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                    Cancel
                </a>
                <button type="submit"
                        :disabled="parseFloat(amount || 0) <= 0 || parseFloat(amount || 0) > availableCash"
                        class="rounded-lg bg-red-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-red-700 disabled:opacity-50 transition">
                    Save Cash Out (CPV)
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
