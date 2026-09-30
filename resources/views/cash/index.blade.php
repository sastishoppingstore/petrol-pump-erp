@extends('layouts.app')

@section('title', 'Cash Book & Roznamcha — Vital Petroleum')
@section('breadcrumb')
    <li class="text-slate-500">Finance</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Cash Book / Roznamcha (روزنامچہ)</li>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Top Banner: Vital Petroleum Branding --}}
    <div class="rounded-xl border border-red-200 bg-gradient-to-r from-red-600 to-red-800 p-5 text-white shadow-sm">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-white/20 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white">Roznamcha</span>
                    <h1 class="text-xl font-bold tracking-tight">Forecourt Daily Cash Book (روزنامچہ نقدی)</h1>
                </div>
                <p class="mt-1 text-xs text-red-100">
                    Vital Petroleum — Mehar Filling Station, Sheikhupura · Daily Till Reconciliations & Sequential Vouchers
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <a href="{{ route('cash.create-in') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-emerald-700 transition">
                    <span>📥</span> Cash In / CRV (وصولی)
                </a>
                <a href="{{ route('cash.create-out') }}"
                   class="inline-flex items-center gap-1.5 rounded-lg bg-white px-4 py-2.5 text-xs font-bold text-red-700 shadow-sm hover:bg-red-50 transition">
                    <span>📤</span> Cash Out / CPV (خرچہ / ادائیگی)
                </a>
            </div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('cash.index') }}" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Date (تاریخ)</label>
                <input type="date" name="date" value="{{ $date }}"
                       class="mt-1 rounded-lg border-slate-300 py-1.5 text-xs font-medium dark:border-slate-700 dark:bg-slate-800">
            </div>

            @if ($branches->count() > 1)
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Station / Branch</label>
                    <select name="branch_id" class="mt-1 rounded-lg border-slate-300 py-1.5 text-xs font-medium dark:border-slate-700 dark:bg-slate-800">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-black transition dark:bg-slate-700">
                Filter Register
            </button>
        </form>
    </div>

    {{-- Summary Cards (Roznamcha T-Account facts) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        {{-- 1. Opening Balance --}}
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-slate-500">Opening Balance (شروع نقد رقم)</span>
            <div class="mt-1 font-mono text-2xl font-bold text-slate-800 dark:text-white">
                {{ \App\Support\PakistaniCurrency::format($roznamcha['opening_cash']) }}
            </div>
            <p class="mt-1 text-[11px] text-slate-400">Prior day closing balance</p>
        </div>

        {{-- 2. Total In (CRV) --}}
        <div class="rounded-xl border border-emerald-200 bg-emerald-50/50 p-4 shadow-sm dark:border-emerald-950 dark:bg-emerald-950/20">
            <span class="text-xs font-semibold uppercase text-emerald-700 dark:text-emerald-400">Total Cash Received (کل وصولی)</span>
            <div class="mt-1 font-mono text-2xl font-bold text-emerald-700 dark:text-emerald-300">
                +{{ \App\Support\PakistaniCurrency::format($roznamcha['total_in']) }}
            </div>
            <p class="mt-1 text-[11px] text-emerald-600 dark:text-emerald-400">CRV vouchers & inflows</p>
        </div>

        {{-- 3. Total Out (CPV) --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50/50 p-4 shadow-sm dark:border-amber-950 dark:bg-amber-950/20">
            <span class="text-xs font-semibold uppercase text-amber-700 dark:text-amber-400">Total Cash Paid (کل اخراجات)</span>
            <div class="mt-1 font-mono text-2xl font-bold text-amber-700 dark:text-amber-300">
                -{{ \App\Support\PakistaniCurrency::format($roznamcha['total_out']) }}
            </div>
            <p class="mt-1 text-[11px] text-amber-600 dark:text-amber-400">CPV vouchers, drops & expenses</p>
        </div>

        {{-- 4. Current / Closing Balance --}}
        <div class="rounded-xl border border-red-200 bg-red-50/50 p-4 shadow-sm dark:border-red-950 dark:bg-red-950/20">
            <span class="text-xs font-semibold uppercase text-red-700 dark:text-red-400">Current Till Cash (موجودہ نقد)</span>
            <div class="mt-1 font-mono text-2xl font-bold text-red-700 dark:text-red-300">
                {{ \App\Support\PakistaniCurrency::format($roznamcha['closing_cash']) }}
            </div>
            <p class="mt-1 font-urdu text-xs text-red-600 dark:text-red-400">
                {{ \App\Support\PakistaniCurrency::toWordsUrdu($roznamcha['closing_cash']) }}
            </p>
        </div>
    </div>

    {{-- Transactions Register Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center justify-between border-b border-slate-200 p-4 dark:border-slate-800">
            <div>
                <h2 class="text-base font-bold text-slate-800 dark:text-white">Daily Roznamcha Entries</h2>
                <p class="text-xs text-slate-500">Chronological Cash Inflow and Outflow audit log</p>
            </div>
            <span class="rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                {{ $roznamcha['entries']->count() }} Transactions
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 uppercase text-slate-500 dark:bg-slate-800">
                    <tr>
                        <th class="p-3">Voucher #</th>
                        <th class="p-3">Type</th>
                        <th class="p-3">Category</th>
                        <th class="p-3">Person / Party</th>
                        <th class="p-3">Reference / Notes</th>
                        <th class="p-3 text-right">Cash In (آمدن)</th>
                        <th class="p-3 text-right">Cash Out (خرچ)</th>
                        <th class="p-3">Operator</th>
                        <th class="p-3">Time</th>
                        <th class="p-3 text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($roznamcha['entries'] as $e)
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                            <td class="p-3 font-mono font-bold text-slate-800 dark:text-white">
                                {{ $e->voucher_number }}
                            </td>
                            <td class="p-3">
                                <span @class([
                                    'rounded px-2 py-0.5 text-[10px] font-bold uppercase',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => $e->isCashIn(),
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' => $e->isCashOut(),
                                ])>
                                    {{ $e->isCashIn() ? 'CRV (IN)' : 'CPV (OUT)' }}
                                </span>
                            </td>
                            <td class="p-3 font-medium text-slate-700 dark:text-slate-300">
                                {{ str_replace('_', ' ', $e->category) }}
                            </td>
                            <td class="p-3 font-semibold text-slate-800 dark:text-white">
                                {{ $e->person_name }}
                            </td>
                            <td class="p-3 text-slate-500">
                                @if ($e->reference_no)
                                    <span class="rounded bg-slate-100 px-1 py-0.5 font-mono text-[10px] dark:bg-slate-800">Ref: {{ $e->reference_no }}</span>
                                @endif
                                <div>{{ $e->notes ?: '—' }}</div>
                            </td>
                            <td class="tabular p-3 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                @if ($e->isCashIn())
                                    +{{ number_format((float) $e->amount, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="tabular p-3 text-right font-mono font-bold text-red-600 dark:text-red-400">
                                @if ($e->isCashOut())
                                    -{{ number_format((float) $e->amount, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="p-3 text-slate-600 dark:text-slate-400">
                                {{ $e->user?->name ?? 'System' }}
                            </td>
                            <td class="p-3 text-slate-400">
                                {{ $e->created_at?->format('h:i A') }}
                            </td>
                            <td class="p-3 text-center">
                                <a href="{{ route('cash.show', $e) }}" class="rounded bg-slate-100 px-2 py-1 text-[11px] font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                                    Voucher
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="p-8 text-center text-slate-400">
                                No cash transactions logged for this date.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
