{{--
    Cash-Flow Forecast Widget (K4) — DashboardController se $forecast
    (ForecastService) ata hai: agle 30 din ke 4 weekly buckets me
    Money In / Money Out. Har figure asal records se hai:
    receivables, received/issued cheques (due dates), supplier
    payables aur sab se recent payroll month ki net salary.
    Motion: erp-motion-kit contract — data-chart JSON + gradient-bars
    preset, net tile par kpi-num count-up. JS sirf animate karta hai.
--}}
@if (isset($forecast))
<section class="mt-10">
    {{-- Section head --}}
    <div class="page-head">
        <h1>🔮 {{ __('finance.forecast.heading') }}</h1>
        <p>{{ __('finance.forecast.sub') }}</p>
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Weekly in/out chart --}}
        <div class="chart-card-3d lg:col-span-2">
            <div class="chart-title">📊 {{ __('finance.forecast.chart_title') }}</div>
            <div class="chart-sub">{{ __('finance.forecast.chart_sub') }}</div>
            <div class="chart-box">
                <canvas data-chart-type="gradient-bars" data-chart='@json($forecast['chart'])'></canvas>
            </div>
        </div>

        {{-- Net position + breakdown --}}
        <div class="space-y-4">
            <div class="stat-tile-3d {{ (float) $forecast['net'] < 0 ? 'stat-red' : 'stat-green' }} tilt-3d">
                <div class="stat-label">{{ __('finance.forecast.net_position') }}</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $forecast['net'] }}" data-prefix="₨ " data-decimals="2">₨ {{ number_format((float) $forecast['net'], 2) }}</span></div>
                <div class="stat-sub">{{ __('finance.forecast.net_sub') }}</div>
            </div>

            <div class="glass-card card-3d p-5">
                <h3 class="mb-2 text-center text-sm font-black text-emerald-600 dark:text-emerald-400">⬇ {{ __('finance.forecast.in_heading') }} — ₨ {{ number_format((float) $forecast['total_in'], 2) }}</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('finance.forecast.receivables') }}</span><span class="tabular font-semibold">₨ {{ number_format((float) $forecast['breakdown']['receivables'], 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('finance.forecast.cheques_in') }}</span><span class="tabular font-semibold">₨ {{ number_format((float) $forecast['breakdown']['cheques_in'], 2) }}</span></div>
                </div>
                <h3 class="mb-2 mt-4 text-center text-sm font-black text-rose-600 dark:text-rose-400">⬆ {{ __('finance.forecast.out_heading') }} — ₨ {{ number_format((float) $forecast['total_out'], 2) }}</h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('finance.forecast.payables') }}</span><span class="tabular font-semibold">₨ {{ number_format((float) $forecast['breakdown']['payables'], 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('finance.forecast.cheques_out') }}</span><span class="tabular font-semibold">₨ {{ number_format((float) $forecast['breakdown']['cheques_out'], 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('finance.forecast.salary_est') }}</span><span class="tabular font-semibold">₨ {{ number_format((float) $forecast['breakdown']['salary'], 2) }}</span></div>
                </div>
                @if ($forecast['breakdown']['salary_month'])
                    <p class="mt-3 text-center text-[11px] text-slate-400">
                        {{ __('finance.forecast.salary_note') }}: {{ $forecast['breakdown']['salary_month'] }} • Payday: {{ $forecast['breakdown']['payday'] }}
                    </p>
                @endif
            </div>
        </div>
    </div>
</section>
@endif
