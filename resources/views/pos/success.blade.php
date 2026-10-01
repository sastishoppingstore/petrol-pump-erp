@extends('layouts.app')

@section('title', 'Sale Completed — ' . $sale->invoice_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('pos.index') }}">{{ __('sales.pos_success.breadcrumb_pos') }}</a></li>
    <li class="font-semibold text-emerald-600">{{ __('sales.pos_success.breadcrumb_complete') }}</li>
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
    <div class="glass-card p-8 text-center">

        {{-- Animated Green Checkmark SVG --}}
        <div class="animate-checkmark-scale mx-auto flex h-24 w-24 items-center justify-center">
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
            {{ __('sales.pos_success.heading') }}
        </h1>
        <p class="mt-1 font-urdu text-base font-semibold text-emerald-600 dark:text-emerald-400">
            {{ __('sales.pos_success.urdu_line') }}
        </p>

        {{-- Invoice Highlights --}}
        <div class="mt-6 rounded-2xl bg-white/60 p-5 text-center shadow-inner dark:bg-slate-800/50">
            <div class="flex flex-wrap items-center justify-center gap-x-10 gap-y-3 border-b border-slate-200 pb-3 dark:border-slate-700">
                <div>
                    <span class="text-xs font-semibold text-slate-500">{{ __('sales.pos_success.invoice_number') }}</span>
                    <div class="font-mono text-lg font-bold text-vital-primary">{{ $sale->invoice_number }}</div>
                </div>
                <div>
                    <span class="text-xs font-semibold text-slate-500">{{ __('sales.pos_success.date_time') }}</span>
                    <div class="text-xs font-medium text-slate-700 dark:text-slate-300">{{ $sale->sale_date?->format('d M Y, h:i A') }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 py-3 text-xs sm:grid-cols-4">
                <div>
                    <span class="text-slate-500">{{ __('sales.pos_success.customer') }}</span>
                    <div class="font-semibold text-slate-800 dark:text-white">
                        {{ $sale->customer?->name ?: ($sale->customer_name ?: 'Walk-in Customer') }}
                    </div>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('sales.pos_success.vehicle') }}</span>
                    <div class="font-semibold text-slate-800 dark:text-white">
                        {{ $sale->vehicle?->registration_number ?? '—' }}
                    </div>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('sales.pos_success.total_litres') }}</span>
                    <div class="font-mono font-bold text-slate-800 dark:text-white">
                        {{ number_format((float) $sale->total_litres, 3) }} L
                    </div>
                </div>
                <div>
                    <span class="text-slate-500">{{ __('sales.pos_success.payment') }}</span>
                    <div class="font-semibold text-emerald-600 dark:text-emerald-400">
                        {{ $sale->payments->pluck('method')->implode(', ') }}
                    </div>
                </div>
            </div>

            {{-- Big Total Amount & Urdu Words --}}
            <div class="mt-2 rounded-2xl bg-gradient-to-br from-vital-primary to-vital-darkred p-4 text-white shadow-glow">
                <div class="flex items-baseline justify-between">
                    <span class="text-xs font-bold uppercase tracking-wider text-red-100">{{ __('sales.pos_success.total_amount_charged') }}</span>
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

            {{-- 1. Print Thermal — autoprint=1 so the cashier slip prints immediately --}}
            <a href="{{ route('pos.thermal', ['sale' => $sale, 'autoprint' => 1]) }}" target="_blank" class="btn-3d btn-3d-navy w-full">
                <span>🧾</span> {{ __('sales.pos_success.print_thermal') }}
            </a>

            {{-- 2. Print A4 Tax Invoice --}}
            <a href="{{ route('pos.receipt', $sale) }}" target="_blank" class="btn-3d btn-3d-ghost w-full">
                <span>📄</span> {{ __('sales.pos_success.print_a4') }}
            </a>

            {{-- 3. WhatsApp wa.me link --}}
            <a href="{{ $whatsappUrl }}" target="_blank" class="btn-3d btn-3d-success w-full">
                <span>💬</span> {{ __('sales.pos_success.whatsapp_receipt') }}
            </a>

            {{-- 4. New Sale --}}
            <a href="{{ route('pos.index') }}" class="btn-3d btn-3d-primary w-full sm:col-span-2 lg:col-span-2">
                <span>➕</span> {{ __('sales.pos_success.start_new_sale') }}
            </a>

            {{-- 5. Home --}}
            <a href="{{ route('dashboard') }}" class="btn-3d btn-3d-ghost w-full">
                <span>🏠</span> {{ __('sales.pos_success.dashboard') }}
            </a>
        </div>

    </div>
</div>
@endsection
