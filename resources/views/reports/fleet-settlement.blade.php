@extends('layouts.app')

@section('title', $meta['title'] . ' — ' . $meta['urdu'])

@section('content')
<div class="space-y-6">

    {{-- Header (centered) --}}
    <div class="page-head">
        <div class="mb-3">
            <span class="badge bg-danger">{{ $meta['group'] }}</span>
        </div>
        <h1>💳 {{ $meta['title'] }}</h1>
        <p style="font-family: 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma; font-size: 1.2rem; color: rgb(var(--brand-primary-rgb));">{{ $meta['urdu'] }}</p>
        <p><strong>{{ __('finance.reports.station_label') }}:</strong> {{ $branch->name }} &bull; <strong>{{ __('finance.reports.filter_range') }}:</strong> {{ $range['label'] }}</p>
        <div class="page-actions">
            <a href="{{ route('reports.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                {{ __('finance.reports.back_hub') }}
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="glass-card p-4 text-center font-semibold text-emerald-600 dark:text-emerald-400">{{ session('success') }}</div>
    @endif

    {{-- Filter Bar --}}
    <div class="glass-card">
        <form method="GET" action="{{ route('reports.show', 'fleet-settlement') }}" class="filter-bar-3d">
            <div class="field-3d w-full sm:w-64">
                <label>{{ __('finance.reports.date_preset') }}</label>
                <select name="preset" class="input-3d" onchange="this.form.submit()">
                    <option value="today" {{ $range['preset'] === 'today' ? 'selected' : '' }}>{{ __('finance.reports.preset_today') }}</option>
                    <option value="yesterday" {{ $range['preset'] === 'yesterday' ? 'selected' : '' }}>{{ __('finance.reports.preset_yesterday') }}</option>
                    <option value="week" {{ $range['preset'] === 'week' ? 'selected' : '' }}>{{ __('finance.reports.preset_week') }}</option>
                    <option value="15days" {{ $range['preset'] === '15days' ? 'selected' : '' }}>{{ __('finance.reports.preset_15') }}</option>
                    <option value="month" {{ $range['preset'] === 'month' ? 'selected' : '' }}>{{ __('finance.reports.preset_month') }}</option>
                    <option value="year" {{ $range['preset'] === 'year' ? 'selected' : '' }}>{{ __('finance.reports.preset_year') }}</option>
                    <option value="custom" {{ $range['preset'] === 'custom' ? 'selected' : '' }}>{{ __('finance.reports.preset_custom') }}</option>
                </select>
            </div>

            @if ($range['preset'] === 'custom')
                <div class="field-3d w-full sm:w-52">
                    <label>{{ __('finance.fleet.from') }}</label>
                    <input type="date" name="from" class="input-3d" value="{{ $from->toDateString() }}">
                </div>
                <div class="field-3d w-full sm:w-52">
                    <label>{{ __('finance.fleet.to') }}</label>
                    <input type="date" name="to" class="input-3d" value="{{ $to->toDateString() }}">
                </div>
                <div class="field-3d">
                    <label>&nbsp;</label>
                    <button type="submit" class="btn-3d btn-3d-navy">{{ __('ui.actions.filter') }}</button>
                </div>
            @endif
        </form>
    </div>

    {{-- Summary tiles --}}
    <div class="tilt-wrap">
        <div class="grid gap-4 sm:grid-cols-3">
            <div class="stat-tile-3d stat-navy tilt-3d">
                <div class="stat-label">{{ __('finance.fleet.total') }}</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $totals['total'] }}" data-prefix="₨ " data-decimals="2">₨ {{ number_format((float) $totals['total'], 2) }}</span></div>
                <div class="stat-sub">{{ $totals['count'] }} {{ __('finance.fleet.payments') }}</div>
            </div>
            <div class="stat-tile-3d stat-green tilt-3d">
                <div class="stat-label">{{ __('finance.fleet.settled') }}</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $totals['settled'] }}" data-prefix="₨ " data-decimals="2">₨ {{ number_format((float) $totals['settled'], 2) }}</span></div>
                <div class="stat-sub">{{ $totals['settled_count'] }} {{ __('finance.fleet.payments') }}</div>
            </div>
            <div class="stat-tile-3d stat-amber tilt-3d">
                <div class="stat-label">{{ __('finance.fleet.pending') }}</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $totals['pending'] }}" data-prefix="₨ " data-decimals="2">₨ {{ number_format((float) $totals['pending'], 2) }}</span></div>
                <div class="stat-sub">{{ $totals['pending_count'] }} {{ __('finance.fleet.payments') }}</div>
            </div>
        </div>
    </div>

    {{-- OMC / reference groups --}}
    <div class="glass-card overflow-hidden">
        <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">🏢 {{ __('finance.fleet.group_heading') }}</h2>
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('finance.fleet.omc') }}</th>
                        <th>{{ __('finance.fleet.payments') }}</th>
                        <th>{{ __('finance.fleet.total') }}</th>
                        <th>{{ __('finance.fleet.settled') }}</th>
                        <th>{{ __('finance.fleet.pending') }}</th>
                        <th>{{ __('finance.fleet.action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($groups as $group)
                        <tr>
                            <td class="font-bold text-slate-800 dark:text-white">{{ $group['label'] }}</td>
                            <td class="tabular">{{ $group['count'] }}</td>
                            <td class="tabular font-semibold">₨ {{ number_format((float) $group['total'], 2) }}</td>
                            <td class="tabular text-emerald-600 dark:text-emerald-400">₨ {{ number_format((float) $group['settled'], 2) }}</td>
                            <td class="tabular font-bold text-amber-600 dark:text-amber-400">₨ {{ number_format((float) $group['pending'], 2) }}</td>
                            <td>
                                @if ((float) $group['pending'] > 0)
                                    <form method="POST" action="{{ route('reports.fleet-settle') }}" onsubmit="return confirm('{{ __('finance.fleet.mark_confirm') }}')">
                                        @csrf
                                        <input type="hidden" name="reference" value="{{ $group['key'] }}">
                                        <input type="hidden" name="from" value="{{ $from->toDateString() }}">
                                        <input type="hidden" name="to" value="{{ $to->toDateString() }}">
                                        <button type="submit" class="btn-3d btn-3d-success btn-3d-sm">✓ {{ __('finance.fleet.mark_settled') }}</button>
                                    </form>
                                @else
                                    <span class="pill-status pill-active">{{ __('finance.fleet.all_settled') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-slate-400">{{ __('finance.fleet.empty_groups') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Detail list --}}
    <div class="glass-card overflow-hidden">
        <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">🧾 {{ __('finance.fleet.detail_heading') }}</h2>
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('finance.fleet.date') }}</th>
                        <th>{{ __('finance.fleet.invoice') }}</th>
                        <th>{{ __('finance.fleet.customer') }}</th>
                        <th>{{ __('finance.fleet.reference') }}</th>
                        <th>{{ __('finance.fleet.amount') }}</th>
                        <th>{{ __('finance.fleet.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $payment->sale?->sale_date?->format('d M Y h:i A') }}</td>
                            <td class="font-semibold">
                                @if ($payment->sale)
                                    <a href="{{ route('sales.show', $payment->sale) }}" class="text-vital-primary hover:underline">{{ $payment->sale->invoice_number }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $payment->sale?->customer?->name ?? $payment->sale?->customer_name ?? '—' }}</td>
                            <td class="text-xs">{{ $payment->reference ?: '—' }}</td>
                            <td class="tabular font-bold">₨ {{ number_format((float) $payment->amount, 2) }}</td>
                            <td>
                                @if ($payment->settled_at)
                                    <span class="pill-status pill-active">{{ __('finance.fleet.status_settled') }}</span>
                                    <div class="mt-0.5 text-[11px] text-slate-400">{{ $payment->settled_at->format('d M Y') }}</div>
                                @else
                                    <span class="pill-status pill-pending">{{ __('finance.fleet.status_pending') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-slate-400">{{ __('finance.fleet.empty') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
