@extends('layouts.app')

@section('title', __('ui.nav.tanks'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.nav.tanks') }}</li>
@endsection

{{--
    Tanks — 2026 redesign.
    Pehle ye page Bootstrap classes (btn/table/erp-card/erp-stock-bar)
    istemal karta tha jo is project me load hi nahi hotin — stock bar
    bilkul ghayab tha. Ab har tank ek 3D glass card hai jisme asal
    tank cylinder (.tank-3d-cylinder + .tank-liquid-wave) ke andar
    live stock level nazar aata hai. Tamam values controller/model
    se real aati hain — koi hardcoded number nahi.
--}}
@section('content')
    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('forecourt.tanks.heading') }}</h1>
        <p>{{ __('forecourt.tanks.count_sub', ['count' => $tanks->count()]) }}</p>
        @can('fuel.create')
            <div class="page-actions">
                <a href="{{ route('tanks.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> {{ __('forecourt.tanks.add') }}
                </a>
            </div>
        @endcan
    </div>

    {{-- ================= 3D Tank Cards ================= --}}
    @if ($tanks->isNotEmpty())
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
            @foreach ($tanks as $tank)
                @php
                    // Cylinder ka rang fuel ki qisam se, aur low/critical
                    // stock par cylinder warning rang le leta hai.
                    $pct = min(100, max(0, (float) $tank->stockPercent()));
                    $fuelKind = strtoupper($tank->fuelName());
                    $cylClass = match (true) {
                        str_contains($fuelKind, 'DIESEL'), str_contains($fuelKind, 'HSD') => 'tank-diesel',
                        str_contains($fuelKind, 'OCTANE'), str_contains($fuelKind, 'HOBC') => 'tank-octane',
                        str_contains($fuelKind, 'KEROSENE') => 'tank-kerosene',
                        default => 'tank-petrol',
                    };
                    $badgeClass = match (true) {
                        str_contains($fuelKind, 'DIESEL'), str_contains($fuelKind, 'HSD') => 'badge-fuel-diesel',
                        str_contains($fuelKind, 'OCTANE'), str_contains($fuelKind, 'HOBC') => 'badge-fuel-octane',
                        str_contains($fuelKind, 'KEROSENE') => 'badge-fuel-other',
                        default => 'badge-fuel-petrol',
                    };
                    $levelClass = match ($tank->stockLevel()) {
                        'low' => 'tank-low-stock',
                        'critical' => 'tank-critical-stock',
                        default => '',
                    };
                @endphp

                <article class="glass-card card-3d p-5 text-center">
                    {{-- 3D tank cylinder with live liquid level --}}
                    <div class="flex justify-center pt-1">
                        <div class="tank-3d-cylinder {{ $cylClass }} {{ $levelClass }} relative flex h-32 w-24 flex-col justify-end">
                            <div class="tank-liquid-wave" style="height: {{ $pct }}%">
                                <div class="absolute inset-x-0 top-0 h-2 rounded-full bg-white/40 blur-[1px]"></div>
                            </div>
                            <div class="tank-gloss-sheen"></div>
                            <div class="relative z-10 pb-1.5">
                                <span class="text-xs font-black text-white drop-shadow-md">{{ $tank->stockPercent() }}%</span>
                            </div>
                        </div>
                    </div>

                    <h2 class="mt-4 text-base font-black text-slate-800 dark:text-white">{{ $tank->displayName() }}</h2>
                    <p class="mt-0.5 text-xs font-semibold text-slate-400">{{ $tank->branch?->name }}</p>

                    <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
                        <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-extrabold uppercase tracking-wide text-white shadow {{ $badgeClass }}">
                            {{ $tank->fuelName() }}
                        </span>
                        <span class="pill-status {{ $tank->isActive() ? 'pill-active' : 'pill-inactive' }}">
                            <span class="dot"></span>{{ $tank->status }}
                        </span>
                    </div>

                    {{-- Stock figures --}}
                    <div class="mt-4 grid grid-cols-2 gap-2">
                        <div class="rounded-xl bg-white/60 px-2 py-2.5 dark:bg-white/5">
                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('forecourt.tanks.current_stock') }}</div>
                            <div class="tabular mt-0.5 text-sm font-black text-slate-800 dark:text-white">{{ number_format((float) $tank->current_stock, 3) }} L</div>
                        </div>
                        <div class="rounded-xl bg-white/60 px-2 py-2.5 dark:bg-white/5">
                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('forecourt.tanks.capacity') }}</div>
                            <div class="tabular mt-0.5 text-sm font-black text-slate-800 dark:text-white">{{ number_format((float) $tank->capacity, 3) }} L</div>
                        </div>
                        <div class="rounded-xl bg-white/60 px-2 py-2.5 dark:bg-white/5">
                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('forecourt.tanks.min_level') }}</div>
                            <div class="tabular mt-0.5 text-sm font-bold text-slate-600 dark:text-slate-300">{{ number_format((float) $tank->min_level, 3) }} L</div>
                        </div>
                        <div class="rounded-xl bg-white/60 px-2 py-2.5 dark:bg-white/5">
                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('forecourt.tanks.max_level') }}</div>
                            <div class="tabular mt-0.5 text-sm font-bold text-slate-600 dark:text-slate-300">{{ number_format((float) $tank->max_level, 3) }} L</div>
                        </div>
                        <div class="col-span-2 rounded-xl bg-white/60 px-2 py-2.5 dark:bg-white/5">
                            <div class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">{{ __('forecourt.tanks.low_stock_alert') }}</div>
                            <div class="tabular mt-0.5 text-sm font-bold text-slate-600 dark:text-slate-300">{{ number_format((float) $tank->low_stock_threshold, 3) }} L</div>
                        </div>
                    </div>

                    @can('fuel.edit')
                        <a href="{{ route('tanks.edit', $tank) }}" class="btn-3d btn-3d-navy btn-3d-sm mt-4 w-full">{{ __('forecourt.tanks.edit_tank') }}</a>
                    @endcan
                </article>
            @endforeach
        </div>
    @else
        <div class="glass-card p-10 text-center">
            <div class="text-4xl" aria-hidden="true">🛢️</div>
            <p class="mt-3 font-semibold text-slate-600 dark:text-slate-300">{{ __('forecourt.tanks.empty') }}</p>
            @can('fuel.create')
                <a href="{{ route('tanks.create') }}" class="btn-3d btn-3d-primary mt-4">{{ __('forecourt.tanks.add_first') }}</a>
            @endcan
        </div>
    @endif

    {{-- ================= Floating Action Button ================= --}}
    @can('fuel.create')
        <a href="{{ route('tanks.create') }}" class="fab-3d" title="{{ __('forecourt.tanks.add_title') }}">
            <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('forecourt.tanks.add') }}
        </a>
    @endcan
@endsection
