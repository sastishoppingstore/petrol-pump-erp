@extends('layouts.app')

@section('title', 'Record Cash Out (CPV) — Vital Petroleum')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('cash.index') }}">Roznamcha</a></li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Cash Out (خرچہ / ادائیگی)</li>
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-6" x-data="{ availableCash: {{ (float)$availableCash }}, amount: '{{ old('amount', '') }}' }">

    <div class="rounded-2xl bg-gradient-to-br from-vital-primary to-vital-darkred p-5 text-center text-white shadow-3d">
        <span class="rounded bg-white/20 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white">CPV</span>
        <h1 class="mt-2 text-xl font-bold tracking-tight">Record Cash Outflow (کیش ادائیگی / خرچہ واؤچر)</h1>
        <p class="text-xs text-red-100">Vital Petroleum — Mehar Filling Station, Sheikhupura</p>
        <div class="mt-2 text-3xl">📤</div>
    </div>

    {{-- Available Cash Banner (Strict Non-Negative Rule) --}}
    <div class="stat-tile-3d tilt-3d stat-amber text-xs">
        <div class="stat-label">Current Available Till Cash:</div>
        <div class="stat-value tabular">{{ \App\Support\PakistaniCurrency::format($availableCash) }}</div>
        <div class="mt-2">
            <span class="rounded bg-white/25 px-2 py-1 text-[10px] font-bold uppercase">
                Strict Rule: Cash cannot go negative
            </span>
            <p class="mt-1 text-[11px] opacity-80">Payout cannot exceed drawer cash.</p>
        </div>
    </div>

    <div class="glass-card card-3d p-6">
        <form method="POST" action="{{ route('cash.store-out') }}" enctype="multipart/form-data" class="space-y-4">
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
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Station / Branch <span class="text-vital-primary">*</span></label>
                    <select name="branch_id" required class="input-3d mt-1 w-full text-center text-xs">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Active Shift</label>
                    <div class="input-3d mt-1 p-2 text-center text-xs font-medium text-slate-700 dark:text-slate-300">
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
            <div class="field-3d">
                <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Amount to Pay Out (روپے) <span class="text-vital-primary">*</span></label>
                <div class="input-3d mt-1 flex items-center px-3 py-2">
                    <span class="mr-2 font-bold text-slate-500">Rs.</span>
                    <input type="number" step="0.01" min="0.01" name="amount" x-model="amount" required
                           placeholder="0.00"
                           class="tabular w-full bg-transparent text-center font-mono text-xl font-black text-slate-900 focus:outline-none dark:text-white">
                </div>
                <template x-if="parseFloat(amount || 0) > availableCash">
                    <p class="mt-1 text-center text-xs font-semibold text-vital-primary">
                        ⚠️ Error: Payout amount exceeds available cash (Rs. {{ number_format((float)$availableCash, 2) }}). Cash cannot go negative!
                    </p>
                </template>
            </div>

            {{-- Category & Paid To Person Name --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Payment Category <span class="text-vital-primary">*</span></label>
                    <select name="category" required class="input-3d mt-1 w-full text-center text-xs">
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" @selected(old('category') === $catKey)>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Paid To / Recipient (وصول کنندہ کا نام) <span class="text-vital-primary">*</span></label>
                    <input type="text" name="person_name" value="{{ old('person_name') }}" required
                           placeholder="e.g. Electrician / PSO / Driver / Manager"
                           class="input-3d mt-1 w-full text-center text-xs">
                </div>
            </div>

            {{-- Reference Number & Entry Date --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Reference / Bill / Voucher #</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                           placeholder="Optional bill or invoice #"
                           class="input-3d mt-1 w-full text-center text-xs">
                </div>
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Transaction Date</label>
                    <input type="date" name="entry_date" value="{{ old('entry_date', now()->toDateString()) }}"
                           class="input-3d mt-1 w-full text-center text-xs">
                </div>
            </div>

            {{-- Notes --}}
            <div class="field-3d">
                <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Expense Justification / Notes (تفصیل خرچ)</label>
                <textarea name="notes" rows="2" placeholder="Explain the purpose of payment..."
                          class="input-3d mt-1 w-full text-center text-xs">{{ old('notes') }}</textarea>
            </div>

            {{-- Attachment --}}
            <div class="field-3d">
                <label class="block text-center text-xs font-semibold text-slate-700 dark:text-slate-300">Payment Receipt / Bill Proof (Scan/Photo)</label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                       class="input-3d mt-1 block w-full text-xs text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
            </div>

            <div class="flex flex-wrap items-center justify-center gap-3 border-t border-slate-200 pt-3 dark:border-slate-800">
                <a href="{{ route('cash.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                    Cancel
                </a>
                <button type="submit"
                        :disabled="parseFloat(amount || 0) <= 0 || parseFloat(amount || 0) > availableCash"
                        class="btn-3d btn-3d-primary btn-3d-sm disabled:opacity-50">
                    Save Cash Out (CPV)
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
