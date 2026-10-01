@extends('layouts.app')

@section('title', 'Live Tank Gauges')
@section('breadcrumb')
    <li class="text-slate-500">Fuel</li>
    <li class="text-slate-500">Tank Gauges</li>
@endsection

@section('content')
<style>
    /* ---- 3D tank visual (self-contained; data sirf Blade se aata hai) ---- */
    .tank-area { position: relative; width: 176px; margin: 0 auto; }
    .tank-shell {
        position: relative; width: 176px; height: 252px; border-radius: 26px;
        background: linear-gradient(90deg, #475569, #cbd5e1 18%, #f8fafc 50%, #cbd5e1 82%, #475569);
        box-shadow: inset 0 0 0 3px rgba(15, 23, 42, .35), inset 0 -14px 24px rgba(15, 23, 42, .18),
            0 18px 30px -12px rgba(2, 6, 23, .45);
        overflow: hidden;
    }
    .tank-shell::after {
        content: ""; position: absolute; top: 6px; left: 12%; right: 12%; height: 16px;
        border-radius: 999px; background: rgba(255, 255, 255, .55); filter: blur(2px);
    }
    .tank-liquid-wrap { position: absolute; inset: 5px; border-radius: 0 0 21px 21px; overflow: hidden; }
    .tank-liquid {
        position: absolute; left: 0; right: 0; bottom: 0;
        transition: height 1.2s cubic-bezier(.22, 1, .36, 1);
    }
    .tank-wave {
        position: absolute; top: -8px; left: 0; width: 200%; height: 10px;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='60' height='10' viewBox='0 0 60 10'%3E%3Cpath d='M0 5 Q 15 0 30 5 T 60 5 V10 H0 Z' fill='%23ffffff' fill-opacity='0.4'/%3E%3C/svg%3E");
        background-repeat: repeat-x; background-size: 60px 10px;
        animation: tankWaveSlide 2.8s linear infinite;
    }
    @keyframes tankWaveSlide { to { transform: translateX(-60px); } }
    .tank-stand {
        width: 128px; height: 10px; margin: 8px auto 0; border-radius: 6px;
        background: linear-gradient(180deg, #334155, #0f172a);
        box-shadow: 0 10px 16px -6px rgba(2, 6, 23, .55);
    }
    .tank-mark {
        position: absolute; right: -74px; width: 66px; text-align: left;
        font-size: 10px; line-height: 1; color: #94a3b8; transform: translateY(50%);
        white-space: nowrap;
    }
    .tank-mark::before {
        content: ""; display: inline-block; width: 10px; margin-right: 4px;
        border-top: 2px solid #94a3b8; vertical-align: middle;
    }
    @media (prefers-reduced-motion: reduce) {
        .tank-wave { animation: none; }
        .tank-liquid { transition: none; }
    }
</style>

<div class="page-head">
    <h1>Live Tank Gauges / لائیو ٹینک گیج</h1>
    <p>Har tank me maujooda fuel — jitna oil hai utna bhara hua. Capacity aur tanks admin Tanks screen se set karta hai.</p>
    <div class="page-actions">
        <a href="{{ route('tanks.index') }}" class="btn-3d btn-3d-navy">Manage Tanks</a>
        @can('fuel.create')
            <a href="{{ route('tanks.create') }}" class="btn-3d btn-3d-success">Add Tank</a>
        @endcan
    </div>
</div>

@php
    $totalCapacity = collect($gauges)->sum('capacity');
    $totalStock = collect($gauges)->sum('current_stock');
    $overallPercent = $totalCapacity > 0 ? round($totalStock / $totalCapacity * 100, 1) : 0;
@endphp

<div class="mb-6 grid gap-4 sm:grid-cols-3">
    <div class="stat-tile-3d tilt-3d stat-navy">
        <div class="stat-label">Active Tanks</div>
        <div class="stat-value tabular">{{ count($gauges) }}</div>
        <div class="stat-sub">Database me jitne active tanks hain, wohi yahan dikhte hain</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-red">
        <div class="stat-label">Total Fuel In Tanks</div>
        <div class="stat-value tabular"><span data-countup="{{ $totalStock }}" data-decimals="0">0</span> L</div>
        <div class="stat-sub">Capacity {{ number_format($totalCapacity, 0) }} L</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-slate">
        <div class="stat-label">Overall Fill Level</div>
        <div class="stat-value tabular">{{ number_format($overallPercent, 1) }}%</div>
        <div class="stat-sub">Tamam tanks ka majmooi hisaab</div>
    </div>
</div>

<div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-3">
    @forelse ($gauges as $g)
        <div class="glass-card p-5 text-center" data-tank-card="{{ $g['tank_id'] }}">
            <div class="mb-1 flex items-center justify-center gap-2">
                <h2 class="text-base font-bold text-slate-900 dark:text-white">
                    {{ $g['tank_name'] ?: 'Tank #' . $g['tank_id'] }}
                </h2>
                <span class="pill-status" style="background: {{ $g['fill']['color'] }}; color: #fff;">
                    <span class="dot"></span>{{ $g['status']['level_label'] }}
                </span>
            </div>
            <div class="mb-4 text-xs font-semibold uppercase tracking-wider text-slate-500">
                {{ $g['fuel'] }}@if ($g['fuel_code']) ({{ $g['fuel_code'] }}) @endif
            </div>

            <div class="tank-area" role="img" aria-label="{{ $g['aria']['label'] }}">
                <div class="tank-shell" @if ($g['fill']['glow']) style="box-shadow: inset 0 0 0 3px rgba(15,23,42,.35), 0 0 34px -4px {{ $g['fill']['color'] }};" @endif>
                    <div class="tank-liquid-wrap">
                        <div class="tank-liquid" data-liquid
                             style="height: {{ $g['stock_percent'] }}%; background: linear-gradient(180deg, {{ $g['fill']['wave_color'] }}, {{ $g['fill']['color'] }}); opacity: {{ $g['fill']['opacity'] }};">
                            <div class="tank-wave"></div>
                        </div>
                    </div>
                </div>
                @foreach ($g['markings'] as $m)
                    <span class="tank-mark" style="bottom: {{ $m['position'] * 100 }}%;">{{ $m['label'] }} · {{ number_format($m['litres'], 0) }}L</span>
                @endforeach
                <div class="tank-stand"></div>
            </div>

            <div class="mt-4">
                <div class="tabular font-mono text-2xl font-black text-slate-900 dark:text-white">
                    <span data-litres>{{ number_format($g['current_stock'], 0) }}</span>
                    <span class="text-sm font-bold text-slate-500">L</span>
                </div>
                <div class="tabular text-xs text-slate-500">
                    of {{ number_format($g['capacity'], 0) }} L capacity ·
                    <span data-percent class="font-bold text-slate-700 dark:text-slate-200">{{ number_format($g['stock_percent'], 1) }}%</span> full
                </div>
            </div>

            @if ($g['status']['warning'])
                <div class="mt-3 rounded-xl bg-red-50 px-3 py-2 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-300">
                    @switch($g['status']['warning'])
                        @case('critical_low_stock') ⚠ Critical low stock — fuel order karein @break
                        @case('low_stock') ⚠ Low stock @break
                        @case('below_minimum') ⚠ Minimum level se neeche @break
                        @default ⚠ {{ $g['status']['warning'] }}
                    @endswitch
                </div>
            @endif

            <div class="mt-4 flex flex-wrap justify-center gap-2">
                <a href="{{ route('tanks.edit', $g['tank_id']) }}" class="btn-3d btn-3d-ghost btn-3d-sm">Edit / Capacity</a>
                <a href="{{ route('tanks.calibration', $g['tank_id']) }}" class="btn-3d btn-3d-navy btn-3d-sm">Dip Chart</a>
                <a href="{{ route('tank-readings.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">Readings</a>
            </div>
        </div>
    @empty
        <div class="glass-card p-10 text-center sm:col-span-2 xl:col-span-3">
            <div class="text-4xl">🛢️</div>
            <h2 class="mt-3 text-base font-bold text-slate-900 dark:text-white">Abhi koi active tank nahi hai</h2>
            <p class="mx-auto mt-1 max-w-md text-sm text-slate-500">
                Pehle Tanks screen se tank banayein aur us ki capacity (litres) set karein —
                phir wohi tank yahan live gauge ki surat me nazar aayega.
            </p>
            <a href="{{ route('tanks.create') }}" class="btn-3d btn-3d-success mt-4">Add First Tank</a>
        </div>
    @endforelse
</div>

<script>
(function () {
    var url = @json(route('gauges.api.all'));
    var interval = {{ (int) $updateInterval }};

    function fmt(n) {
        return Number(n).toLocaleString('en-PK', { maximumFractionDigits: 0 });
    }

    function refresh() {
        fetch(url, { headers: { 'Accept': 'application/json' } })
            .then(function (res) { return res.ok ? res.json() : null; })
            .then(function (data) {
                if (!data || !data.gauges) return;
                data.gauges.forEach(function (g) {
                    var card = document.querySelector('[data-tank-card="' + g.tank_id + '"]');
                    if (!card) return;
                    var liquid = card.querySelector('[data-liquid]');
                    if (liquid) liquid.style.height = g.stock_percent + '%';
                    var litres = card.querySelector('[data-litres]');
                    if (litres) litres.textContent = fmt(g.current_stock);
                    var pct = card.querySelector('[data-percent]');
                    if (pct) pct.textContent = Number(g.stock_percent).toFixed(1) + '%';
                });
            })
            .catch(function () { /* network rukawat: purani values rehne do */ });
    }

    if (interval > 0 && document.querySelector('[data-tank-card]')) {
        setInterval(refresh, interval);
    }
})();
</script>
@endsection
