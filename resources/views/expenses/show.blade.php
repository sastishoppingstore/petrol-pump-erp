@extends('layouts.app')

@section('title', 'Expense Voucher ' . $expense->expense_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('expenses.index') }}">Expenses</a></li>
    <li class="text-slate-500">{{ $expense->expense_number }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-3xl space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>🧾 Voucher {{ $expense->expense_number }}</h1>
        <p>
            <span class="rounded bg-emerald-100 px-2.5 py-0.5 align-middle text-xs font-semibold text-emerald-800">
                {{ $expense->status }}
            </span>
        </p>
        <p>{{ $expense->title }}</p>
        <div class="page-actions no-print">
            <button onclick="window.print()" class="btn-3d btn-3d-primary">
                🖨️ Print Voucher
            </button>
            <a href="{{ route('expenses.index') }}" class="btn-3d btn-3d-ghost">
                Back to Expenses
            </a>
        </div>
    </div>

    {{-- ================= Printable Voucher Card ================= --}}
    <div class="glass-card p-8">
        <div class="border-b-2 border-red-600 pb-5">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-2">
                        <span class="rounded bg-red-600 px-2 py-0.5 text-xs font-black tracking-widest text-white">VITAL</span>
                        <span class="text-lg font-black text-slate-900 dark:text-white">MEHAR FILLING STATION</span>
                    </div>
                    <p class="text-xs text-slate-500">Vital Petroleum Franchise &bull; GT Road, Sheikhupura</p>
                </div>
                <div class="text-right">
                    <span class="rounded bg-slate-100 px-2.5 py-1 font-mono text-xs font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-200">
                        EXPENSE VOUCHER / ادائیگی واؤچر
                    </span>
                    <div class="mt-1 font-mono text-xs text-slate-500">{{ $expense->date?->format('d M Y') }}</div>
                </div>
            </div>
        </div>

        <div class="mt-6 grid gap-4 text-sm sm:grid-cols-2">
            <div class="text-center sm:text-left">
                <span class="text-xs font-semibold uppercase text-slate-400">Category (شعبہ / مد)</span>
                <div class="font-bold text-slate-900 dark:text-white">
                    {{ $expense->category?->name }}
                    @if ($expense->category?->urdu_name)
                        <span class="font-normal text-slate-500">({{ $expense->category->urdu_name }})</span>
                    @endif
                </div>
            </div>

            <div class="text-center sm:text-right">
                <span class="text-xs font-semibold uppercase text-slate-400">Payee / Vendor (وصول کنندہ)</span>
                <div class="font-bold text-slate-900 dark:text-white">{{ $expense->payee ?: 'General / Bearer' }}</div>
            </div>

            <div class="text-center sm:col-span-2">
                <span class="text-xs font-semibold uppercase text-slate-400">Description / تفصیل</span>
                <div class="mt-1 font-medium text-slate-800 dark:text-slate-200">{{ $expense->title }}</div>
            </div>

            <div class="text-center sm:text-left">
                <span class="text-xs font-semibold uppercase text-slate-400">Payment Source (ادائیگی بذریعہ)</span>
                <div class="mt-1 text-xs font-semibold">
                    @if ($expense->payment_method === 'CASH')
                        <span class="text-emerald-700">Cash Float {{ $expense->shift ? '(Shift #' . $expense->shift->shift_number . ')' : '' }}</span>
                    @elseif ($expense->payment_method === 'BANK_TRANSFER')
                        <span class="text-sky-700">Bank Transfer ({{ $expense->bankAccount?->account_title ?? 'Bank' }})</span>
                    @else
                        <span>{{ $expense->payment_method }}</span>
                    @endif
                </div>
            </div>

            <div class="text-center sm:text-right">
                <span class="text-xs font-semibold uppercase text-slate-400">Receipt / Reference #</span>
                <div class="tabular mt-1 font-mono text-xs">{{ $expense->receipt_number ?: '—' }}</div>
            </div>
        </div>

        {{-- Amount Box --}}
        <div class="glass-card mt-6 border border-red-200 bg-red-50/60 p-4">
            <div class="flex flex-col items-center justify-between gap-2 text-center sm:flex-row sm:text-left">
                <div>
                    <span class="text-xs font-bold uppercase text-red-900">Total Amount Paid (ادا شدہ رقم)</span>
                    <div class="tabular mt-1 font-mono text-2xl font-black text-red-700">
                        Rs. {{ number_format((float) $expense->amount, 2) }}
                    </div>
                </div>
                <div class="text-center sm:text-right">
                    <div class="text-sm font-bold text-red-900" style="direction: rtl;">
                        {{ \App\Support\PakistaniCurrency::toUrduWords((string) $expense->amount) }}
                    </div>
                </div>
            </div>
        </div>

        @if ($expense->notes)
            <div class="mt-4 text-center text-xs text-slate-500">
                <strong>Notes:</strong> {{ $expense->notes }}
            </div>
        @endif

        {{-- Attachment Preview --}}
        @if ($expense->attachment_path)
            <div class="mt-6 border-t border-slate-200/70 pt-4 text-center dark:border-slate-700/60">
                <span class="text-xs font-semibold uppercase text-slate-400">Attached Bill / Receipt Proof</span>
                <div class="glass-card mt-2 overflow-hidden p-2">
                    <img src="{{ asset('storage/' . $expense->attachment_path) }}" alt="Receipt for {{ $expense->expense_number }}" class="mx-auto max-h-96 rounded-lg object-contain">
                </div>
            </div>
        @endif

        {{-- Signatures --}}
        <div class="mt-12 grid grid-cols-3 gap-6 text-center text-xs">
            <div>
                <div class="border-t border-slate-300 pt-2 font-medium text-slate-600">
                    Prepared By: {{ $expense->creator?->name ?? 'Staff' }}
                </div>
            </div>
            <div>
                <div class="border-t border-slate-300 pt-2 font-medium text-slate-600">
                    Received By: {{ $expense->payee ?: 'Signature' }}
                </div>
            </div>
            <div>
                <div class="border-t border-slate-300 pt-2 font-medium text-slate-600">
                    Manager / Authorized Signatory
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
