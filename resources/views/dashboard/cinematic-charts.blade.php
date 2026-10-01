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
        <h1>📊 Live Station Analytics</h1>
        <p>
            <span class="live-dot">LIVE</span>
            <span class="ml-2">Pichle 30 din ki sale, fuel mix aur payment mix — bilkul film ki tarah</span>
        </p>
    </div>

    {{-- Row 1: Sales trend — film-line (poori width) --}}
    <div class="chart-card-3d">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <div class="chart-title">🎬 Sales Trend — Last 30 Days</div>
                <div class="chart-sub">Rozana fuel sale (litres) — line film ki tarah draw hoti hai</div>
            </div>
            <span class="live-dot mt-1">LIVE</span>
        </div>
        <div class="chart-box">
            <canvas data-chart-type="film-line" data-chart='@json($chartSalesTrend)'></canvas>
        </div>
    </div>

    {{-- Row 2: Fuel-wise bars + Payment mix donut --}}
    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <div class="chart-card-3d">
            <div class="chart-title">⛽ Fuel-wise Sales — Last 30 Days</div>
            <div class="chart-sub">Har fuel product ki kul sale (litres)</div>
            <div class="chart-box-sm">
                <canvas data-chart-type="gradient-bars" data-chart='@json($chartFuelMix)'></canvas>
            </div>
            @if (empty($chartFuelMix['labels']))
                <p class="chart-sub mt-3 text-center">Is period me abhi koi sale record nahi hui.</p>
            @endif
        </div>

        <div class="chart-card-3d">
            <div class="chart-title">💳 Payment Mix — Last 30 Days</div>
            <div class="chart-sub">Cash, card, udhaar aur baqi tareeqon ka hissa (Rs)</div>
            <div class="chart-box-sm">
                <canvas data-chart-type="donut-rotate" data-chart='@json($chartPaymentMix)'></canvas>
                <div class="donut-center">
                    <div class="dc-num kpi-num"><span data-countup="{{ $paymentMixTotal }}" data-prefix="₨ ">₨ {{ number_format($paymentMixTotal) }}</span></div>
                    <div class="dc-lbl">Total — 30 Days</div>
                </div>
            </div>
            @if (empty($chartPaymentMix['labels']))
                <p class="chart-sub mt-3 text-center">Is period me abhi koi payment record nahi hui.</p>
            @endif
        </div>
    </div>

    {{-- KPI strip: aaj ke asli numbers, count-up ke saath --}}
    <div class="tilt-wrap mt-6">
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="stat-tile-3d stat-red tilt-3d">
                <div class="stat-label">Aaj ki Sale — Today's Sales</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $kpiTodaySales }}" data-prefix="₨ ">₨ {{ number_format($kpiTodaySales) }}</span></div>
                <div class="stat-sub">Completed sales — aaj din bhar ki kul raqam</div>
            </div>
            <div class="stat-tile-3d stat-green tilt-3d">
                <div class="stat-label">Aaj ke Litres — Fuel Sold</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $kpiTodayLitres }}" data-decimals="1" data-suffix=" L">{{ number_format($kpiTodayLitres, 1) }} L</span></div>
                <div class="stat-sub">Aaj becha gaya kul fuel (litres)</div>
            </div>
            <div class="stat-tile-3d stat-amber tilt-3d">
                <div class="stat-label">Transactions — Aaj</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $kpiTodayTransactions }}">{{ number_format($kpiTodayTransactions) }}</span></div>
                <div class="stat-sub">Aaj mukammal hui invoices</div>
            </div>
            <div class="stat-tile-3d stat-navy tilt-3d">
                <div class="stat-label">Active Shifts</div>
                <div class="stat-value kpi-num"><span data-countup="{{ $kpiActiveShifts }}">{{ $kpiActiveShifts }}</span></div>
                <div class="stat-sub"><span class="live-dot">LIVE</span> <span class="ml-1">Abhi khuli hui shifts</span></div>
            </div>
        </div>
    </div>
</section>
