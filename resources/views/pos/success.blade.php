@extends('layouts.app')

@section('title', 'Sale Completed — ' . $sale->invoice_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('pos.index') }}">POS</a></li>
    <li class="font-semibold text-emerald-600">Sale Complete</li>
@endsection

@push('styles')
<style>
@keyframes checkmark-circle {
    0% { stroke-dashoffset: 166; }
    100% { stroke-dashoffset: 0; }
}
@keyframes checkmark-check {
    0% { stroke-dashoffset: 48; }
    100% { stroke-dashoffset: 0; }
}
@keyframes scale-pulse {
    0%, 100% { transform: none; }
    50% { transform: scale3d(1.05, 1.05, 1); }
}
.animate-checkmark-circle {
    stroke-dasharray: 166;
    stroke-dashoffset: 166;
    animation: checkmark-circle 0.6s cubic-bezier(0.65, 0, 0.45, 1) forwards;
}
.animate-checkmark-check {
    stroke-dasharray: 48;
    stroke-dashoffset: 48;
    animation: checkmark-check 0.4s cubic-bezier(0.65, 0, 0.45, 1) 0.5s forwards;
}
.animate-checkmark-scale {
    animation: scale-pulse 0.4s ease-in-out 0.9s both;
}
</style>
@endpush

@section('content')
<div class="mx-auto max-w-2xl py-6">

    {{-- Success Card with Animated Tick --}}
    <div class="rounded-2xl border border-emerald-100 bg-white p-8 text-center shadow-lg dark:border-emerald-950 dark:bg-slate-900">

        {{-- Animated Green Checkmark SVG --}}
        <div class="mx-auto flex h-24 w-24 items-center justify-center animate-checkmark-scale">
            <svg class="h-24 w-24 text-emerald-500" viewBox="0 0 52 52">
                <circle class="animate-checkmark-circle stroke-current text-emerald-100 dark:text-emerald-950"
                        cx="26" cy="26" r="25" fill="none" stroke-width="3" />
                <circle class="animate-checkmark-circle stroke-current text-emerald-500"
                        cx="26" cy="26" r="25" fill="none" stroke-width="3" />
                <path class="animate-checkmark-check stroke-current text-emerald-500"
                      fill="none" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"
                      d="M14.1 27.2l7.1 7.2 16.7-16.8" />
            </svg>
        </div>

        <h1 class="mt-4 text-2xl font-black text-slate-800 dark:text-white">
            Sale Completed Successfully!
        </h1>
        <p class="mt-1 font-urdu text-base font-semibold text-emerald-600 dark:text-emerald-400">
            ٹرانزیکشن کامیابی سے مکمل ہوئی
        </p>

        {{-- Invoice Highlights --}}
        <div class="mt-6 rounded-xl border border-slate-200 bg-slate-50/80 p-5 text-left dark:border-slate-800 dark:bg-slate-800/50">
            <div class="flex flex-wrap items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-700">
                <div>
                    <span class="text-xs font-semibold text-slate-500">Invoice Number</span>
                    <div class="font-mono text-lg font-bold text-red-700 dark:text-red-400">{{ $sale->invoice_number }}</div>
                </div>
                <div class="text-right">
                    <span class="text-xs font-semibold text-slate-500">Date & Time</span>
                    <div class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $sale->sale_date?->format('d M Y, h:i A') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 py-3 text-xs sm:grid-cols-4">
                <div>
                    <span class="text-slate-500">Customer</span>
                    <div class="font-semibold text-slate-800 dark:text-white">
                        {{ $sale->customer?->name ?: ($sale->customer_name ?: 'Walk-in Customer') }}
                    </div>
                </div>
                <div>
                    <span class="text-slate-500">Vehicle</span>
                    <div class="font-semibold text-slate-800 dark:text-white">
                        {{ $sale->vehicle?->registration_number ?? '—' }}
                    </div>
                </div>
                <div>
                    <span class="text-slate-500">Total Litres</span>
                    <div class="font-mono font-bold text-slate-800 dark:text-white">
                        {{ number_format((float) $sale->total_litres, 3) }} L
                    </div>
                </div>
                <div>
                    <span class="text-slate-500">Payment</span>
                    <div class="font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ $sale->payments->pluck('method')->implode(', ') }}
                    </div>
                </div>
            </div>

            {{-- Big Total Amount & Urdu Words --}}
            <div class="mt-2 rounded-lg bg-red-700 p-4 text-white">
                <div class="flex items-baseline justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-red-200">Total Amount Charged</span>
                    <span class="tabular font-mono text-3xl font-extrabold tracking-tight">
                        {{ \App\Support\PakistaniCurrency::format($sale->total) }}
                    </span>
                </div>
                <div class="mt-1 text-right font-urdu text-sm font-medium text-red-100">
                    {{ \App\Support\PakistaniCurrency::toWordsUrdu($sale->total) }}
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">

            {{-- 1. Print Thermal 80mm --}}
            <a href="{{ route('pos.thermal', $sale) }}" target="_blank"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-slate-900 px-4 py-3.5 text-sm font-bold text-white shadow transition hover:bg-black">
                <span>🧾</span> Print Thermal (80mm)
            </a>

            {{-- 2. Print A4 Tax Invoice --}}
            <a href="{{ route('pos.receipt', $sale) }}" target="_blank"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <span>📄</span> Print A4 Invoice
            </a>

            {{-- 3. WhatsApp wa.me link --}}
            <a href="{{ $whatsappUrl }}" target="_blank"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-emerald-600 px-4 py-3.5 text-sm font-bold text-white shadow transition hover:bg-emerald-700">
                <span>💬</span> WhatsApp Receipt
            </a>

            {{-- 4. New Sale --}}
            <a href="{{ route('pos.index') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-3.5 text-sm font-bold text-white shadow transition hover:bg-red-700 sm:col-span-2 lg:col-span-2">
                <span>➕</span> Start New Sale (نیا بل)
            </a>

            {{-- 5. Home --}}
            <a href="{{ route('dashboard') }}"
               class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-3.5 text-sm font-bold text-slate-700 shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <span>🏠</span> Dashboard
            </a>
        </div>

    </div>
</div>
@endsection
