@extends('layouts.app')

@section('title', 'Record Cash In (CRV) — Vital Petroleum')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('cash.index') }}">Roznamcha</a></li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Cash In (وصولی)</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl space-y-6">

    <div class="rounded-xl border border-emerald-200 bg-gradient-to-r from-emerald-600 to-emerald-800 p-5 text-white shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <span class="rounded bg-white/20 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white">CRV</span>
                <h1 class="mt-1 text-xl font-bold tracking-tight">Record Cash Inflow (کیش وصولی واؤچر)</h1>
                <p class="text-xs text-emerald-100">Vital Petroleum — Mehar Filling Station, Sheikhupura</p>
            </div>
            <span class="text-3xl">📥</span>
        </div>
    </div>

    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="POST" action="{{ route('cash.store-in') }}" enctype="multipart/form-data" class="space-y-4">
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
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Amount Received (روپے) <span class="text-red-500">*</span></label>
                <div class="mt-1 flex items-center rounded-lg border border-slate-300 bg-slate-50 px-3 py-2 dark:border-slate-700 dark:bg-slate-800">
                    <span class="font-bold text-slate-500 mr-2">Rs.</span>
                    <input type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" required
                           placeholder="0.00"
                           class="w-full bg-transparent font-mono text-xl font-black text-slate-900 focus:outline-none dark:text-white">
                </div>
            </div>

            {{-- Category & Person Name --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Receipt Category <span class="text-red-500">*</span></label>
                    <select name="category" required class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">
                        @foreach ($categories as $catKey => $catLabel)
                            <option value="{{ $catKey }}" @selected(old('category') === $catKey)>{{ $catLabel }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Received From (ادا کنندہ کا نام) <span class="text-red-500">*</span></label>
                    <input type="text" name="person_name" value="{{ old('person_name') }}" required
                           placeholder="e.g. Haji Aslam / Customer / Bank"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">
                </div>
            </div>

            {{-- Reference Number & Entry Date --}}
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Reference / Slip Number</label>
                    <input type="text" name="reference_no" value="{{ old('reference_no') }}"
                           placeholder="Optional receipt / slip #"
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
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Notes / Remarks (تفصیل)</label>
                <textarea name="notes" rows="2" placeholder="Optional details..."
                          class="mt-1 w-full rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">{{ old('notes') }}</textarea>
            </div>

            {{-- Attachment --}}
            <div>
                <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Receipt Attachment (Photo / Scan)</label>
                <input type="file" name="attachment" accept=".pdf,.jpg,.jpeg,.png"
                       class="mt-1 block w-full text-xs text-slate-500 file:mr-4 file:rounded-lg file:border-0 file:bg-slate-100 file:px-4 file:py-2 file:text-xs file:font-semibold file:text-slate-700 hover:file:bg-slate-200">
            </div>

            <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-200 dark:border-slate-800">
                <a href="{{ route('cash.index') }}" class="rounded-lg border border-slate-300 px-4 py-2.5 text-xs font-semibold text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-emerald-600 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                    Save Cash In (CRV)
                </button>
            </div>
        </form>
    </div>

</div>
@endsection
