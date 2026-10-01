{{--
    Cinematic Station Analytics — dashboard charts (MASTER_PROMPT T1).
    Data: DashboardController::cinematicAnalytics() se asli sales data
    (pichle 30 din, COMPLETED sales, branch scope ke saath).
    Motion: resources/js/dashboard-motion.js presets —
    film-line (progressive draw) / gradient-bars / donut-rotate,
    data-countup KPI numbers, GSAP entrance + 3D tilt.
    Yahan koi hardcoded number nahi — sab kuch @json payloads aur
    controller variables se aata hai. Khali DB par charts khali
    render hote hain, page nahi toot-ti.
--}}
<section class="mt-10">
    {{-- Section head --}}
    <div class="page-head">
        <h1>📊 {{ __('admin.cinematic.title') }}</h1>
        <p>
            <span class="live-dot">{{ __('admin.cinematic.live') }}</span>
            <span class="ml-2">{{ __('admin.cinematic.subtitle') }}</span>
        </p>
    </div>

    {{-- Row 1: Sales trend — film-line (poori width) --}}
    <div class="chart-card-3d">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="chart-title">🎬 {{ __('admin.cinematic.trend_title') }}</div>
                <div class="chart-sub">{{ __('admin.cinematic.trend_sub') }}</div>
            </div>
            <span class="live-dot mt-1">{{ __('admin.cinematic.live') }}</span>
        </div>
        <div class="chart-box">
            <canvas data-chart-type="film-line" data-chart='@json($chartSalesTrend)'></canvas>
        </div>
    </div>

    {{-- Row 2: Fuel-wise bars + Payment mix donut --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="chart-card-3d">
            <div class="chart-title">⛽ {{ __('admin.cinematic.fuel_title') }}</div>
            <div class="chart-sub">{{ __('admin.cinematic.fuel_sub') }}</div>
            <div class="chart-box-sm">
                <canvas data-chart-type="gradient-bars" data-chart='@json($chartFuelMix)'></canvas>
            </div>
            @if (empty($chartFuelMix['labels']))
                <p class="chart-sub mt-3 text-center">{{ __('admin.cinematic.no_sales') }}</p>
            @endif
        </div>

        <div class="chart-card-3d">
            <div class="chart-title">💳 {{ __('admin.cinematic.payment_title') }}</div>
            <div class="chart-sub">{{ __('admin.cinematic.payment_sub') }}</div>
            <div class="chart-box-sm">
                <canvas data-chart-type="donut-rotate" data-chart='@json($chartPaymentMix)'></canvas>
                <div class="donut-center">
                    <div class="dc-num kpi-num"><span data-countup="{{ $paymentMixTotal }}" data-prefix="₨ ">₨ {{ number_format($paymentMixTotal) }}</span></div>
                    <div class="dc-lbl">{{ __('admin.cinematic.donut_total') }}</div>
                </div>
            </div>
            @if (empty($chartPaymentMix['labels']))
                <p class="chart-sub mt-3 text-center">{{ __('admin.cinematic.no_payments') }}</p>
            @endif
        </div>
    </div>

    {{-- KPI strip: aaj ke asli numbers, count-up ke saath --}}
    <div class="tilt-wrap mt-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="stat-tile-3d stat-red tilt-3d">
                <div class="stat-label">{{ __('admin.cinematic.kpi_sales_label') }}</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $kpiTodaySales }}" data-prefix="₨ ">₨ {{ number_format($kpiTodaySales) }}</span></div>
                <div class="stat-sub">{{ __('admin.cinematic.kpi_sales_sub') }}</div>
            </div>
            <div class="stat-tile-3d stat-green tilt-3d">
                <div class="stat-label">{{ __('admin.cinematic.kpi_litres_label') }}</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $kpiTodayLitres }}" data-decimals="1" data-suffix=" L">{{ number_format($kpiTodayLitres, 1) }} L</span></div>
                <div class="stat-sub">{{ __('admin.cinematic.kpi_litres_sub') }}</div>
            </div>
            <div class="stat-tile-3d stat-amber tilt-3d">
                <div class="stat-label">{{ __('admin.cinematic.kpi_tx_label') }}</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $kpiTodayTransactions }}">{{ number_format($kpiTodayTransactions) }}</span></div>
                <div class="stat-sub">{{ __('admin.cinematic.kpi_tx_sub') }}</div>
            </div>
            <div class="stat-tile-3d stat-navy tilt-3d">
                <div class="stat-label">{{ __('admin.cinematic.kpi_shifts_label') }}</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $kpiActiveShifts }}">{{ $kpiActiveShifts }}</span></div>
                <div class="stat-sub"><span class="live-dot">{{ __('admin.cinematic.live') }}</span> <span class="ml-1">{{ __('admin.cinematic.kpi_shifts_sub') }}</span></div>
            </div>
        </div>
    </div>
</section>
