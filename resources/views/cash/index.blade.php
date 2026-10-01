@extends('layouts.app')

@section('title', 'Cash Book & Roznamcha — Vital Petroleum')
@section('breadcrumb')
    <li class="text-slate-500">Finance</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Cash Book / Roznamcha (روزنامچہ)</li>
@endsection

@section('content')
<div class="space-y-6">

    {{-- Top Banner: Vital Petroleum Branding --}}
    <div class="rounded-2xl bg-gradient-to-br from-vital-primary to-vital-darkred p-5 text-center text-white shadow-3d">
        <span class="rounded bg-white/20 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white">Roznamcha</span>
        <h1 class="mt-2 text-xl font-bold tracking-tight">Forecourt Daily Cash Book (روزنامچہ نقدی)</h1>
        <p class="mt-1 text-xs text-red-100">
            Vital Petroleum — Mehar Filling Station, Sheikhupura · Daily Till Reconciliations &amp; Sequential Vouchers
        </p>
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
            <a href="{{ route('cash.create-in') }}" class="btn-3d btn-3d-success">
                <span>📥</span> Cash In / CRV (وصولی)
            </a>
            <a href="{{ route('cash.create-out') }}" class="btn-3d btn-3d-primary">
                <span>📤</span> Cash Out / CPV (خرچہ / ادائیگی)
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="glass-card p-4">
        <form method="GET" action="{{ route('cash.index') }}" class="flex flex-wrap items-end justify-center gap-3">
            <div class="field-3d">
                <label class="block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Date (تاریخ)</label>
                <input type="date" name="date" value="{{ $date }}"
                       class="input-3d mt-1 py-1.5 text-center text-xs font-medium">
            </div>

            @if ($branches->count() > 1)
                <div class="field-3d">
                    <label class="block text-center text-xs font-semibold text-slate-600 dark:text-slate-400">Station / Branch</label>
                    <select name="branch_id" class="input-3d mt-1 py-1.5 text-center text-xs font-medium">
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected($branchId == $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <button type="submit" class="btn-3d btn-3d-navy btn-3d-sm">
                Filter Register
            </button>
        </form>
    </div>

    {{-- Summary Cards (Roznamcha T-Account facts) --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Opening Balance (شروع نقد رقم)</div>
            <div class="stat-value tabular">{{ \App\Support\PakistaniCurrency::format($roznamcha['opening_cash']) }}</div>
            <div class="stat-sub">Prior day closing balance</div>
        </div>

        <div class="stat-tile-3d stat-green">
            <div class="stat-label">Total Cash Received (کل وصولی)</div>
            <div class="stat-value tabular">+{{ \App\Support\PakistaniCurrency::format($roznamcha['total_in']) }}</div>
            <div class="stat-sub">CRV vouchers &amp; inflows</div>
        </div>

        <div class="stat-tile-3d stat-amber">
            <div class="stat-label">Total Cash Paid (کل اخراجات)</div>
            <div class="stat-value tabular">-{{ \App\Support\PakistaniCurrency::format($roznamcha['total_out']) }}</div>
            <div class="stat-sub">CPV vouchers, drops &amp; expenses</div>
        </div>

        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Current Till Cash (موجودہ نقد)</div>
            <div class="stat-value tabular">{{ \App\Support\PakistaniCurrency::format($roznamcha['closing_cash']) }}</div>
            <div class="stat-sub font-urdu">{{ \App\Support\PakistaniCurrency::toWordsUrdu($roznamcha['closing_cash']) }}</div>
        </div>
    </div>

    {{-- Transactions Register Table --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 p-4 text-center dark:border-slate-700/60">
            <h2 class="text-base font-bold text-slate-800 dark:text-white">Daily Roznamcha Entries</h2>
            <p class="text-xs text-slate-500">Chronological Cash Inflow and Outflow audit log</p>
            <span class="mt-1 inline-block rounded bg-slate-100 px-2 py-1 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                {{ $roznamcha['entries']->count() }} Transactions
            </span>
        </div>

        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>Voucher #</th>
                        <th>Type</th>
                        <th>Category</th>
                        <th>Person / Party</th>
                        <th>Reference / Notes</th>
                        <th>Cash In (آمدن)</th>
                        <th>Cash Out (خرچ)</th>
                        <th>Operator</th>
                        <th>Time</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roznamcha['entries'] as $e)
                        <tr>
                            <td class="font-mono font-bold text-slate-800 dark:text-white">
                                {{ $e->voucher_number }}
                            </td>
                            <td>
                                <span @class([
                                    'rounded px-2 py-0.5 text-[10px] font-bold uppercase',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300' => $e->isCashIn(),
                                    'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-300' => $e->isCashOut(),
                                ])>
                                    {{ $e->isCashIn() ? 'CRV (IN)' : 'CPV (OUT)' }}
                                </span>
                            </td>
                            <td class="font-medium text-slate-700 dark:text-slate-300">
                                {{ str_replace('_', ' ', $e->category) }}
                            </td>
                            <td class="font-semibold text-slate-800 dark:text-white">
                                {{ $e->person_name }}
                            </td>
                            <td class="text-slate-500">
                                @if ($e->reference_no)
                                    <span class="rounded bg-slate-100 px-1 py-0.5 font-mono text-[10px] dark:bg-slate-800">Ref: {{ $e->reference_no }}</span>
                                @endif
                                <div>{{ $e->notes ?: '—' }}</div>
                            </td>
                            <td class="tabular font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                @if ($e->isCashIn())
                                    +{{ number_format((float) $e->amount, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="tabular font-mono font-bold text-vital-primary dark:text-red-400">
                                @if ($e->isCashOut())
                                    -{{ number_format((float) $e->amount, 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-slate-600 dark:text-slate-400">
                                {{ $e->user?->name ?? 'System' }}
                            </td>
                            <td class="text-slate-400">
                                {{ $e->created_at?->format('h:i A') }}
                            </td>
                            <td>
                                <a href="{{ route('cash.show', $e) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                    Voucher
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400">
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
