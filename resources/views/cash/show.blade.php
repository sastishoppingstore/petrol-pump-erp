@extends('layouts.app')

@section('title', 'Voucher ' . $entry->voucher_number . ' — Vital Petroleum')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('cash.index') }}">Roznamcha</a></li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ $entry->voucher_number }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl space-y-6">

    <div class="flex items-center justify-between">
        <a href="{{ route('cash.index') }}" class="rounded-lg border border-slate-300 px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
            ← Back to Roznamcha
        </a>
        <a href="{{ route('cash.print', $entry) }}" target="_blank"
           class="inline-flex items-center gap-1.5 rounded-lg bg-slate-900 px-4 py-2 text-xs font-bold text-white shadow hover:bg-black">
            <span>🖨️</span> Print Voucher (80mm / Slip)
        </a>
    </div>

    {{-- Voucher Slip Card --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        {{-- Header --}}
        <div class="border-b border-slate-200 pb-4 text-center dark:border-slate-700">
            <h1 class="text-xl font-black uppercase text-slate-800 dark:text-white">Mehar Filling Station</h1>
            <div class="text-xs font-bold text-red-600">VITAL PETROLEUM FRANCHISE — SHEIKHUPURA</div>
            <div class="mt-2 inline-block rounded-full px-3 py-1 text-xs font-extrabold uppercase {{ $entry->isCashIn() ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300' }}">
                {{ $entry->isCashIn() ? 'Cash Receipt Voucher (CRV)' : 'Cash Payment Voucher (CPV)' }}
            </div>
        </div>

        {{-- Voucher Facts --}}
        <div class="mt-6 grid grid-cols-2 gap-4 text-xs">
            <div>
                <span class="text-slate-500">Voucher Number:</span>
                <div class="font-mono text-base font-bold text-slate-800 dark:text-white">{{ $entry->voucher_number }}</div>
            </div>
            <div class="text-right">
                <span class="text-slate-500">Date & Time:</span>
                <div class="font-semibold text-slate-800 dark:text-white">{{ $entry->entry_date?->format('d M Y, h:i A') }}</div>
            </div>
            <div>
                <span class="text-slate-500">{{ $entry->isCashIn() ? 'Received From:' : 'Paid To:' }}</span>
                <div class="text-sm font-black text-slate-900 dark:text-white">{{ $entry->person_name }}</div>
            </div>
            <div class="text-right">
                <span class="text-slate-500">Category:</span>
                <div class="font-semibold text-slate-800 dark:text-white">{{ str_replace('_', ' ', $entry->category) }}</div>
            </div>
            @if ($entry->reference_no)
                <div>
                    <span class="text-slate-500">Reference No:</span>
                    <div class="font-mono font-semibold text-slate-700 dark:text-slate-300">{{ $entry->reference_no }}</div>
                </div>
            @endif
            @if ($entry->shift)
                <div class="{{ $entry->reference_no ? 'text-right' : '' }}">
                    <span class="text-slate-500">Shift:</span>
                    <div class="font-semibold text-slate-700 dark:text-slate-300">{{ $entry->shift->shift_number }}</div>
                </div>
            @endif
        </div>

        {{-- Amount Display --}}
        <div class="mt-6 rounded-xl {{ $entry->isCashIn() ? 'bg-emerald-700' : 'bg-red-700' }} p-4 text-white">
            <div class="flex items-baseline justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-white/80">Amount</span>
                <span class="font-mono text-3xl font-black">
                    {{ \App\Support\PakistaniCurrency::format($entry->amount) }}
                </span>
            </div>
            <div class="mt-2 text-right font-urdu text-sm font-medium text-white/90">
                {{ \App\Support\PakistaniCurrency::toWordsUrdu($entry->amount) }}
            </div>
        </div>

        @if ($entry->notes)
            <div class="mt-4 rounded-lg bg-slate-50 p-3 text-xs text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                <span class="font-bold text-slate-500">Notes / Remarks: </span>
                {{ $entry->notes }}
            </div>
        @endif

        {{-- Signature Signoffs --}}
        <div class="mt-10 grid grid-cols-3 gap-4 border-t border-slate-200 pt-6 text-center text-xs dark:border-slate-700">
            <div>
                <div class="h-10"></div>
                <div class="border-t border-slate-300 pt-1 font-semibold text-slate-600 dark:border-slate-600 dark:text-slate-400">
                    Prepared By: {{ $entry->user?->name ?? 'Cashier' }}
                </div>
            </div>
            <div>
                <div class="h-10"></div>
                <div class="border-t border-slate-300 pt-1 font-semibold text-slate-600 dark:border-slate-600 dark:text-slate-400">
                    Recipient / Payee
                </div>
            </div>
            <div>
                <div class="h-10"></div>
                <div class="border-t border-slate-300 pt-1 font-semibold text-slate-600 dark:border-slate-600 dark:text-slate-400">
                    Manager / Station Stamp
                </div>
            </div>
        </div>
    </div>

</div>
@endsection
