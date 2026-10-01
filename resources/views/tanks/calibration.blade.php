@extends('layouts.app')

@section('title', __('forecourt.tanks.calibration.title', ['number' => $tank->tank_number]))

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('tanks.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.tanks') }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ __('forecourt.tanks.calibration.breadcrumb', ['number' => $tank->tank_number]) }}</span></li>
@endsection

{{--
    Tank Dip Chart Calibration — 2026 redesign.
    Design system ke glass boxes + stat tiles + centered text.
    Alpine interpolation logic, tamam forms, routes aur field names
    pehle jaisay hi hain — sirf markup/classes badle hain.
--}}
@section('content')
<div class="mx-auto w-full max-w-6xl space-y-6">
    @php
        $allowableLoss = bcmul((string) $tank->current_stock, '0.005', 3);
        $fuelKind = strtoupper($tank->fuelProduct->name ?? '');
        $badgeClass = match (true) {
            str_contains($fuelKind, 'DIESEL'), str_contains($fuelKind, 'HSD') => 'badge-fuel-diesel',
            str_contains($fuelKind, 'OCTANE'), str_contains($fuelKind, 'HOBC') => 'badge-fuel-octane',
            str_contains($fuelKind, 'KEROSENE') => 'badge-fuel-other',
            default => 'badge-fuel-petrol',
        };
    @endphp

    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('forecourt.tanks.calibration.heading', ['number' => $tank->tank_number]) }}</h1>
        <p>
            <span class="inline-flex items-center rounded-full px-3 py-1 text-[11px] font-extrabold uppercase tracking-wide text-white shadow {{ $badgeClass }}">
                {{ $tank->fuelProduct->name ?? 'Fuel Tank' }}
            </span>
        </p>
        <p>{{ __('forecourt.tanks.calibration.sub') }}</p>
        <div class="page-actions">
            <a href="{{ route('tanks.index') }}" class="btn-3d btn-3d-ghost">{{ __('forecourt.tanks.calibration.back') }}</a>
            <a href="{{ route('tank-readings.index') }}" class="btn-3d btn-3d-navy">{{ __('forecourt.tanks.calibration.dip_log') }}</a>
        </div>
    </div>

    {{-- ================= Capacity & Stock Metrics ================= --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('forecourt.tanks.calibration.total_capacity') }}</div>
            <div class="stat-value">{{ \App\Support\Quantity::format($tank->capacity) }} <span class="text-sm font-bold">L</span></div>
            <div class="stat-sub">{{ __('forecourt.tanks.calibration.vessel') }}</div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('forecourt.tanks.calibration.book_stock') }}</div>
            <div class="stat-value">{{ \App\Support\Quantity::format($tank->current_stock) }} <span class="text-sm font-bold">L</span></div>
            <div class="stat-sub">
                {{ __('forecourt.tanks.calibration.ullage') }} {{ \App\Support\Quantity::format(\App\Support\Quantity::subtract($tank->capacity, $tank->current_stock)) }} L
            </div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('forecourt.tanks.calibration.points') }}</div>
            <div class="stat-value">{{ $charts->count() }} <span class="text-sm font-bold">{{ __('forecourt.tanks.calibration.points_unit') }}</span></div>
            <div class="stat-sub">
                {{ $charts->count() > 0 ? 'Max dip: ' . $charts->max('dip_cm') . ' cm' : 'No chart loaded' }}
            </div>
        </div>

        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">{{ __('forecourt.tanks.calibration.permissible') }}</div>
            <div class="stat-value">± {{ \App\Support\Quantity::format($allowableLoss) }} <span class="text-sm font-bold">L</span></div>
            <div class="stat-sub">{{ __('forecourt.tanks.calibration.tolerance_note') }}</div>
        </div>
    </div>

    {{-- ================= Physical Dip Reading & Variance Tool ================= --}}
    <div class="glass-card p-6"
         x-data="{
             chartData: {{ json_encode($charts->map(fn($c) => ['cm' => (float)$c->dip_cm, 'litres' => (float)$c->litres])) }},
             capacity: {{ (float) $tank->capacity }},
             currentStock: {{ (float) $tank->current_stock }},
             dipCm: '',
             manualLitres: '',

             interpolate(cm) {
                 if (!cm || cm <= 0) return 0;
                 if (this.chartData.length === 0) return 0;

                 // Exact match
                 const exact = this.chartData.find(p => p.cm === cm);
                 if (exact) return exact.litres;

                 const sorted = [...this.chartData].sort((a,b) => a.cm - b.cm);
                 const first = sorted[0];
                 const last = sorted[sorted.length - 1];

                 if (cm < first.cm) {
                     return (first.cm > 0) ? (cm * (first.litres / first.cm)) : first.litres;
                 }
                 if (cm >= last.cm) {
                     return Math.min(last.litres, this.capacity);
                 }

                 let lower = first;
                 let upper = last;
                 for (let i = 0; i < sorted.length - 1; i++) {
                     if (sorted[i].cm <= cm && sorted[i+1].cm >= cm) {
                         lower = sorted[i];
                         upper = sorted[i+1];
                         break;
                     }
                 }

                 const deltaCm = upper.cm - lower.cm;
                 if (deltaCm === 0) return lower.litres;
                 const deltaL = upper.litres - lower.litres;
                 return lower.litres + ((cm - lower.cm) * (deltaL / deltaCm));
             },

             get physicalLitres() {
                 if (this.manualLitres) return parseFloat(this.manualLitres) || 0;
                 const cm = parseFloat(this.dipCm) || 0;
                 return parseFloat(this.interpolate(cm).toFixed(3));
             },

             get variance() {
                 return parseFloat((this.physicalLitres - this.currentStock).toFixed(3));
             },

             get allowableLoss() {
                 return parseFloat((this.currentStock * 0.005).toFixed(3));
             },

             get isWithinTolerance() {
                 if (this.variance >= 0) return true;
                 return Math.abs(this.variance) <= this.allowableLoss;
             }
         }">

        <div class="border-b border-slate-200/70 pb-4 text-center dark:border-slate-700/50">
            <h2 class="text-lg font-black text-slate-900 dark:text-white">{{ __('forecourt.tanks.calibration.record_heading') }}</h2>
            <p class="mt-1 text-xs text-slate-500">
                {{ __('forecourt.tanks.calibration.record_sub') }}
            </p>
        </div>

        <form method="POST" action="{{ route('tanks.calibration.evaluate', $tank) }}" class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-4">
            @csrf

            <div>
                <label class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.tanks.calibration.dip_cm') }}</label>
                <div class="field-3d">
                    <input type="number" step="0.01" name="dip_cm" x-model="dipCm" placeholder="e.g. 145.5"
                           class="input-3d text-center font-mono font-bold">
                </div>
                <p class="mt-1 text-center text-[10px] text-slate-400">{{ __('forecourt.tanks.calibration.dip_help') }}</p>
            </div>

            <div>
                <label class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.tanks.calibration.direct_qty') }}</label>
                <div class="field-3d">
                    <input type="number" step="0.001" name="physical_quantity" x-model="manualLitres" placeholder="{{ __('forecourt.tanks.calibration.override_placeholder') }}"
                           class="input-3d text-center font-mono">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.tanks.calibration.reading_date') }}</label>
                <div class="field-3d">
                    <input type="date" name="reading_date" value="{{ today()->toDateString() }}" required
                           class="input-3d text-center">
                </div>
            </div>

            <div>
                <label class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.tanks.calibration.dip_notes') }}</label>
                <div class="field-3d">
                    <input type="text" name="notes" placeholder="{{ __('forecourt.tanks.calibration.dip_notes_placeholder') }}"
                           class="input-3d text-center">
                </div>
            </div>

            {{-- Live Analysis Preview Card --}}
            <div class="rounded-2xl border p-4 text-center transition sm:col-span-4"
                 :class="isWithinTolerance ? 'border-emerald-300 bg-emerald-50/60 dark:border-emerald-800 dark:bg-emerald-950/20' : 'border-red-400 bg-red-50/60 dark:border-red-800 dark:bg-red-950/20'">
                <div class="grid grid-cols-2 gap-4 text-xs sm:grid-cols-4">
                    <div>
                        <div class="font-semibold text-slate-500">{{ __('forecourt.tanks.calibration.calc_stock') }}</div>
                        <div class="tabular mt-0.5 font-mono text-lg font-black text-slate-900 dark:text-white">
                            <span x-text="physicalLitres.toLocaleString()"></span> L
                        </div>
                    </div>

                    <div>
                        <div class="font-semibold text-slate-500">{{ __('forecourt.tanks.calibration.expected_stock') }}</div>
                        <div class="tabular mt-0.5 font-mono text-lg font-bold text-slate-900 dark:text-white">
                            {{ \App\Support\Quantity::format($tank->current_stock) }} L
                        </div>
                    </div>

                    <div>
                        <div class="font-semibold text-slate-500">{{ __('forecourt.tanks.calibration.variance') }}</div>
                        <div class="tabular mt-0.5 font-mono text-lg font-black"
                             :class="variance >= 0 ? 'text-emerald-600' : (isWithinTolerance ? 'text-amber-600' : 'text-red-600')">
                            <span x-text="variance >= 0 ? '+' : ''"></span><span x-text="variance.toLocaleString()"></span> L
                        </div>
                    </div>

                    <div>
                        <div class="font-semibold text-slate-500">{{ __('forecourt.tanks.calibration.evap_status') }}</div>
                        <div class="mt-1 text-sm font-bold"
                             :class="isWithinTolerance ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-400'">
                            <span x-show="isWithinTolerance">{{ __('forecourt.tanks.calibration.within_1') }}<span x-text="allowableLoss"></span> {{ __('forecourt.tanks.calibration.within_2') }}</span>
                            <span x-show="!isWithinTolerance">{{ __('forecourt.tanks.calibration.exceeds') }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-center sm:col-span-4">
                <button type="submit" class="btn-3d btn-3d-primary">
                    {{ __('forecourt.tanks.calibration.save_dip') }}
                </button>
            </div>
        </form>
    </div>

    {{-- ================= Calibration Management ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Single Calibration Point --}}
        <div class="glass-card p-6">
            <h2 class="border-b border-slate-200/70 pb-3 text-center text-sm font-black text-slate-900 dark:border-slate-700/50 dark:text-white">
                {{ __('forecourt.tanks.calibration.add_point') }}
            </h2>
            <form method="POST" action="{{ route('tanks.calibration.store', $tank) }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.tanks.calibration.dip_height') }}</label>
                        <div class="field-3d">
                            <input type="number" step="0.01" name="dip_cm" required placeholder="e.g. 50.0"
                                   class="input-3d text-center font-mono">
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.tanks.calibration.volume') }}</label>
                        <div class="field-3d">
                            <input type="number" step="0.001" name="litres" required placeholder="e.g. 7500"
                                   class="input-3d text-center font-mono">
                        </div>
                    </div>
                </div>

                <div class="flex justify-center pt-1">
                    <button type="submit" class="btn-3d btn-3d-navy btn-3d-sm">{{ __('forecourt.tanks.calibration.save_point') }}</button>
                </div>
            </form>
        </div>

        {{-- Bulk Import Calibration Points --}}
        <div class="glass-card p-6">
            <h2 class="border-b border-slate-200/70 pb-3 text-center text-sm font-black text-slate-900 dark:border-slate-700/50 dark:text-white">
                {{ __('forecourt.tanks.calibration.bulk') }}
            </h2>
            <form method="POST" action="{{ route('tanks.calibration.bulk', $tank) }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">{{ __('forecourt.tanks.calibration.paste') }}</label>
                    <div class="field-3d">
                        <textarea name="data" rows="4" required placeholder="10, 850&#10;20, 1850&#10;30, 3100&#10;40, 4500"
                                  class="input-3d font-mono text-xs"></textarea>
                    </div>
                    <p class="mt-1 text-center text-[10px] text-slate-400">{{ __('forecourt.tanks.calibration.bulk_help') }}</p>
                </div>

                <div class="flex justify-center pt-1">
                    <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">{{ __('forecourt.tanks.calibration.import') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= Calibration Chart Table ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 p-4 text-center dark:border-slate-700/50">
            <h2 class="text-sm font-black text-slate-900 dark:text-white">{{ __('forecourt.tanks.calibration.points_heading', ['count' => $charts->count()]) }}</h2>
            <p class="mt-0.5 text-xs text-slate-500">{{ __('forecourt.tanks.calibration.sorted') }}</p>
        </div>

        <div class="table-3d max-h-96 overflow-y-auto">
            <table>
                <thead>
                    <tr>
                        <th class="sticky top-0 z-10">{{ __('forecourt.tanks.calibration.col_cm') }}</th>
                        <th class="sticky top-0 z-10">{{ __('forecourt.tanks.calibration.col_volume') }}</th>
                        <th class="sticky top-0 z-10">{{ __('forecourt.tanks.calibration.col_pct') }}</th>
                        <th class="sticky top-0 z-10">{{ __('forecourt.tanks.calibration.col_gauge') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($charts as $point)
                        @php
                            $cap = (float) $tank->capacity;
                            $litres = (float) $point->litres;
                            $pct = $cap > 0 ? min(100, round(($litres / $cap) * 100, 1)) : 0;
                        @endphp
                        <tr>
                            <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                {{ $point->dip_cm }} cm
                            </td>
                            <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\Quantity::format($point->litres) }} L
                            </td>
                            <td class="tabular font-mono font-semibold text-slate-600 dark:text-slate-300">
                                {{ $pct }}%
                            </td>
                            <td>
                                <div class="mx-auto h-2.5 w-full max-w-[220px] rounded-full bg-slate-200 dark:bg-slate-700">
                                    <div class="h-2.5 rounded-full bg-gradient-to-r from-vital-primary to-vital-darkred" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-slate-400">
                                {{ __('forecourt.tanks.calibration.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
