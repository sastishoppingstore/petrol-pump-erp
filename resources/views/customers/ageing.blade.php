@extends('layouts.app')

@section('title', __('sales.customer_ageing.title'))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('sales.customer_ageing.breadcrumb_customers') }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('sales.customer_ageing.breadcrumb_ageing') }}</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>⏱️ {{ __('sales.customer_ageing.heading') }}</h1>
        <p>{{ __('sales.customer_ageing.subtitle') }}</p>
        <div class="page-actions no-print">
            <button onclick="window.print()" class="btn-3d btn-3d-ghost">
                🖨️ {{ __('sales.customer_ageing.print_report') }}
            </button>
            <a href="{{ route('customers.index') }}" class="btn-3d btn-3d-ghost">
                ← {{ __('sales.customer_ageing.back_to_customers') }}
            </a>
        </div>
    </div>

    {{-- ================= Bucket KPI Tiles ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('sales.customer_ageing.bucket_0_30') }}</div>
            <div class="stat-value tabular text-xl">{{ \App\Support\PakistaniCurrency::format($totals['0_30'], true, 0) }}</div>
            <div class="stat-sub">{{ __('sales.customer_ageing.sub_current') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('sales.customer_ageing.bucket_31_60') }}</div>
            <div class="stat-value tabular text-xl">{{ \App\Support\PakistaniCurrency::format($totals['31_60'], true, 0) }}</div>
            <div class="stat-sub">{{ __('sales.customer_ageing.sub_followup') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">{{ __('sales.customer_ageing.bucket_61_90') }}</div>
            <div class="stat-value tabular text-xl">{{ \App\Support\PakistaniCurrency::format($totals['61_90'], true, 0) }}</div>
            <div class="stat-sub">{{ __('sales.customer_ageing.sub_warning') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('sales.customer_ageing.bucket_90') }}</div>
            <div class="stat-value tabular text-xl">{{ \App\Support\PakistaniCurrency::format($totals['over_90'], true, 0) }}</div>
            <div class="stat-sub">{{ __('sales.customer_ageing.sub_overdue') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-slate">
            <div class="stat-label">{{ __('sales.customer_ageing.total_outstanding') }}</div>
            <div class="stat-value tabular text-xl">{{ \App\Support\PakistaniCurrency::format($totals['total'], true, 0) }}</div>
            <div class="stat-sub">{{ count($rows) }} {{ __('sales.customer_ageing.debtors_suffix') }}</div>
        </div>
    </div>

    {{-- ================= Ageing Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table class="text-xs">
                <thead>
                    <tr>
                        <th>{{ __('sales.customer_ageing.th_customer') }}</th>
                        <th>{{ __('sales.customer_ageing.th_phone') }}</th>
                        <th class="text-emerald-700 dark:text-emerald-400">{{ __('sales.customer_ageing.th_0_30') }}</th>
                        <th class="text-blue-700 dark:text-blue-400">{{ __('sales.customer_ageing.th_31_60') }}</th>
                        <th class="text-amber-700 dark:text-amber-400">{{ __('sales.customer_ageing.th_61_90') }}</th>
                        <th class="text-red-700 dark:text-red-400">{{ __('sales.customer_ageing.th_90') }}</th>
                        <th>{{ __('sales.customer_ageing.th_total') }}</th>
                        <th class="no-print">{{ __('sales.customer_ageing.th_reminder') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                        @php $c = $row['customer']; $a = $row['ageing']; @endphp
                        <tr>
                            <td>
                                <a href="{{ route('customers.show', $c) }}" class="font-bold text-slate-900 hover:text-vital-primary dark:text-white dark:hover:text-red-400">
                                    {{ $c->name }}
                                </a>
                                <div class="font-mono text-[11px] text-slate-400">{{ $c->code }}</div>
                            </td>
                            <td class="tabular font-mono">
                                {{ $c->phone ?? 'N/A' }}
                            </td>
                            <td class="tabular font-mono text-emerald-600 dark:text-emerald-400">
                                {{ \App\Support\Money::compare($a['0_30'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($a['0_30'], false, 0) : '-' }}
                            </td>
                            <td class="tabular font-mono text-blue-600 dark:text-blue-400">
                                {{ \App\Support\Money::compare($a['31_60'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($a['31_60'], false, 0) : '-' }}
                            </td>
                            <td class="tabular font-mono text-amber-600 dark:text-amber-400">
                                {{ \App\Support\Money::compare($a['61_90'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($a['61_90'], false, 0) : '-' }}
                            </td>
                            <td class="tabular font-mono font-bold text-red-600 dark:text-red-400">
                                {{ \App\Support\Money::compare($a['over_90'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($a['over_90'], false, 0) : '-' }}
                            </td>
                            <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\PakistaniCurrency::format($a['total'], true, 0) }}
                            </td>
                            <td class="no-print">
                                <a href="{{ $row['whatsapp_link'] }}" target="_blank"
                                   class="btn-3d btn-3d-success btn-3d-sm"
                                   title="{{ __('sales.customer_ageing.whatsapp_title') }} {{ $c->phone }}">
                                    <span aria-hidden="true">💬</span> wa.me
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-8 text-slate-400">
                                {{ __('sales.customer_ageing.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-slate-100/70 font-bold text-slate-900 dark:bg-slate-800 dark:text-white">
                        <td class="uppercase" colspan="2">{{ __('sales.customer_ageing.consolidated_totals') }}</td>
                        <td class="tabular font-mono text-emerald-600">{{ \App\Support\PakistaniCurrency::format($totals['0_30'], false, 0) }}</td>
                        <td class="tabular font-mono text-blue-600">{{ \App\Support\PakistaniCurrency::format($totals['31_60'], false, 0) }}</td>
                        <td class="tabular font-mono text-amber-600">{{ \App\Support\PakistaniCurrency::format($totals['61_90'], false, 0) }}</td>
                        <td class="tabular font-mono text-red-600">{{ \App\Support\PakistaniCurrency::format($totals['over_90'], false, 0) }}</td>
                        <td class="tabular font-mono">{{ \App\Support\PakistaniCurrency::format($totals['total'], true, 0) }}</td>
                        <td class="no-print"></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection
