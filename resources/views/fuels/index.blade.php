@extends('layouts.app')

@section('title', __('ui.nav.fuel_products'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.nav.fuel_products') }}</li>
@endsection

{{--
    Fuel Products — 2026 redesign.
    Pehle ye page Bootstrap classes (btn/table/erp-card) istemal karta tha
    lekin project sirf Tailwind load karta hai, is liye list bilkul plain
    text render hoti thi. Ab:
    - Har fuel ek 3D glass card hai (fuel-type badge, status pill, bari price)
    - Desktop par saath rich data table bhi hai (xl screens)
    - "Add Fuel" floating action button (FAB) har screen size par
    Tamam data pehle ki tarah controller se real aata hai — koi dummy
    button ya hardcoded number nahi.
--}}
@section('content')
    {{-- ================= Header ================= --}}
    <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
        <div>
            <h1 class="text-2xl font-black tracking-tight text-slate-900 dark:text-white">{{ __('ui.nav.fuel_products') }}</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ __('forecourt.fuels.count_sub', ['count' => $fuels->total()]) }} •
                {{ __('forecourt.fuels.prices_managed_from') }} <a href="{{ route('fuel-prices.index') }}" class="font-bold text-vital-primary hover:underline">{{ __('ui.nav.fuel_prices') }}</a>
            </p>
        </div>
        @can('fuel.create')
            <a href="{{ route('fuels.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                <span aria-hidden="true">＋</span> {{ __('forecourt.fuels.add') }}
            </a>
        @endcan
    </div>

    @forelse ($fuels as $fuel)
        @if ($loop->first)
            {{-- ================= 3D Fuel Cards ================= --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @endif

        @php
            // Badge ka rang fuel ki qisam se (naam/code ke keyword par).
            $fuelKind = strtoupper($fuel->code . ' ' . $fuel->name);
            $badgeClass = match (true) {
                str_contains($fuelKind, 'OCTANE'), str_contains($fuelKind, 'HI-') => 'badge-fuel-octane',
                str_contains($fuelKind, 'HSD'), str_contains($fuelKind, 'DIESEL') => 'badge-fuel-diesel',
                str_contains($fuelKind, 'PETROL'), str_contains($fuelKind, 'SUPER'), str_contains($fuelKind, 'MOGAS'), str_contains($fuelKind, 'PMG') => 'badge-fuel-petrol',
                default => 'badge-fuel-other',
            };
            $margin = (float) $fuel->selling_price - (float) $fuel->average_cost;
        @endphp

        <article class="glass-card card-3d group relative overflow-hidden p-5">
            {{-- Top gloss strip --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 {{ $badgeClass }}" aria-hidden="true"></div>

            <div class="flex items-start justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl text-xl text-white shadow-lg {{ $badgeClass }}" aria-hidden="true">⛽</div>
                    <div class="min-w-0">
                        <h2 class="truncate text-base font-black text-slate-900 dark:text-white">{{ $fuel->name }}</h2>
                        <span class="mt-0.5 inline-block rounded-md bg-slate-900/5 px-1.5 py-0.5 font-mono text-[11px] font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">{{ $fuel->code }}</span>
                    </div>
                </div>
                <span class="pill-status {{ $fuel->isActive() ? 'pill-active' : 'pill-inactive' }}">
                    <span class="dot" aria-hidden="true"></span>{{ $fuel->status }}
                </span>
            </div>

            {{-- Bari price --}}
            <div class="mt-5 flex items-baseline gap-1.5">
                <span class="text-sm font-bold text-slate-400">Rs</span>
                <span class="tabular text-4xl font-black tracking-tight text-slate-900 dark:text-white">{{ number_format((float) $fuel->selling_price, 2) }}</span>
                <span class="text-sm font-semibold text-slate-400">/ {{ strtolower($fuel->unit) }}</span>
            </div>

            {{-- Stats --}}
            <dl class="mt-4 grid grid-cols-3 gap-2 border-t border-slate-200/70 pt-4 text-center dark:border-slate-700/60">
                <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('forecourt.fuels.avg_cost') }}</dt>
                    <dd class="tabular mt-0.5 text-sm font-bold text-slate-700 dark:text-slate-200">{{ number_format((float) $fuel->average_cost, 2) }}</dd>
                </div>
                <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('forecourt.fuels.margin') }}</dt>
                    <dd class="tabular mt-0.5 text-sm font-bold {{ $margin >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format($margin, 2) }}</dd>
                </div>
                <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('forecourt.fuels.min_stock') }}</dt>
                    <dd class="tabular mt-0.5 text-sm font-bold text-slate-700 dark:text-slate-200">{{ number_format((float) $fuel->minimum_stock, 0) }}</dd>
                </div>
            </dl>

            @can('fuel.edit')
                <a href="{{ route('fuels.edit', $fuel) }}"
                   class="btn-3d btn-3d-ghost btn-3d-sm mt-4 w-full transition duration-200 hover:-translate-y-0.5">
                    {{ __('forecourt.fuels.edit_fuel') }}
                </a>
            @endcan
        </article>

        @if ($loop->last)
            </div>
        @endif
    @empty
        <div class="glass-card p-10 text-center">
            <div class="text-4xl" aria-hidden="true">⛽</div>
            <p class="mt-3 font-semibold text-slate-600 dark:text-slate-300">{{ __('forecourt.fuels.empty') }}</p>
            @can('fuel.create')
                <a href="{{ route('fuels.create') }}" class="btn-3d btn-3d-primary mt-4">{{ __('forecourt.common.add_first') }}</a>
            @endcan
        </div>
    @endforelse

    {{-- ================= Rich data table (barri screens) ================= --}}
    @if ($fuels->isNotEmpty())
        <div class="glass-card mt-6 hidden overflow-hidden xl:block">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200/80 text-left text-[11px] font-extrabold uppercase tracking-wider text-slate-400 dark:border-slate-700/60">
                            <th class="px-5 py-3.5">{{ __('forecourt.fuels.code') }}</th>
                            <th class="px-5 py-3.5">{{ __('forecourt.common.fuel') }}</th>
                            <th class="px-5 py-3.5">{{ __('forecourt.fuels.unit') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('forecourt.fuels.selling_price') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('forecourt.fuels.avg_cost') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('forecourt.fuels.margin') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('forecourt.fuels.min_stock') }}</th>
                            <th class="px-5 py-3.5">{{ __('forecourt.common.status') }}</th>
                            <th class="px-5 py-3.5 text-right">{{ __('forecourt.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($fuels as $fuel)
                            <tr class="transition hover:bg-white/60 dark:hover:bg-white/5">
                                <td class="px-5 py-3.5"><code class="rounded-md bg-slate-900/5 px-1.5 py-0.5 font-mono text-xs font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $fuel->code }}</code></td>
                                <td class="px-5 py-3.5 font-bold text-slate-800 dark:text-slate-100">{{ $fuel->name }}</td>
                                <td class="px-5 py-3.5 text-slate-500">{{ $fuel->unit }}</td>
                                <td class="tabular px-5 py-3.5 text-right font-bold">{{ number_format((float) $fuel->selling_price, 2) }}</td>
                                <td class="tabular px-5 py-3.5 text-right text-slate-500">{{ number_format((float) $fuel->average_cost, 2) }}</td>
                                <td class="tabular px-5 py-3.5 text-right font-semibold {{ ((float) $fuel->selling_price - (float) $fuel->average_cost) >= 0 ? 'text-emerald-600' : 'text-red-600' }}">{{ number_format((float) $fuel->selling_price - (float) $fuel->average_cost, 2) }}</td>
                                <td class="tabular px-5 py-3.5 text-right text-slate-500">{{ number_format((float) $fuel->minimum_stock, 3) }}</td>
                                <td class="px-5 py-3.5">
                                    <span class="pill-status {{ $fuel->isActive() ? 'pill-active' : 'pill-inactive' }}">
                                        <span class="dot" aria-hidden="true"></span>{{ $fuel->status }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-right">
                                    @can('fuel.edit')
                                        <a href="{{ route('fuels.edit', $fuel) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.edit') }}</a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-6">{{ $fuels->links() }}</div>

    {{-- ================= Floating Action Button ================= --}}
    @can('fuel.create')
        <a href="{{ route('fuels.create') }}" class="fab-3d" title="{{ __('forecourt.fuels.add_title') }}">
            <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('forecourt.fuels.add') }}
        </a>
    @endcan
@endsection
