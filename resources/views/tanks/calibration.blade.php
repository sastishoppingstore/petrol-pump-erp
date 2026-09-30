@extends('layouts.app')

@section('title', 'Tank #' . $tank->tank_number . ' Dip Chart Calibration')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('tanks.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Tanks</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Tank #{{ $tank->tank_number }} Calibration</span></li>
@endsection

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    {{-- Header Banner --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                    <span class="text-red-600">📏</span> Tank #{{ $tank->tank_number }} Dip Chart Calibration
                </h1>
                <span class="rounded bg-red-100 px-2.5 py-0.5 font-bold text-xs text-red-800 dark:bg-red-950/60 dark:text-red-300">
                    {{ $tank->fuelProduct->name ?? 'Fuel Tank' }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                Underground cylindrical tank calibration curve (cm to Litres), physical dip variance analysis, and 0.5% permissible evaporation threshold.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('tanks.index') }}" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition shadow-sm">
                ← Back to Tanks
            </a>
            <a href="{{ route('tank-readings.index') }}" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 transition shadow-sm">
                📋 Dip Reading Log
            </a>
        </div>
    </div>

    {{-- Tank Capacity & Stock Metrics --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total Tank Capacity</div>
            <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
                {{ \App\Support\Quantity::format($tank->capacity) }} <span class="text-xs font-normal text-slate-400">L</span>
            </div>
            <div class="mt-1 text-xs text-slate-500">Underground vessel volume</div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Current Book Stock</div>
            <div class="mt-2 text-2xl font-black text-emerald-600 dark:text-emerald-400 font-mono">
                {{ \App\Support\Quantity::format($tank->current_stock) }} <span class="text-xs font-normal text-slate-400">L</span>
            </div>
            <div class="mt-1 text-xs text-slate-500">
                Ullage / Free space: {{ \App\Support\Quantity::format(\App\Support\Quantity::subtract($tank->capacity, $tank->current_stock)) }} L
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Calibration Points</div>
            <div class="mt-2 text-2xl font-black text-slate-900 dark:text-white font-mono">
                {{ $charts->count() }} <span class="text-xs font-normal text-slate-400">points</span>
            </div>
            <div class="mt-1 text-xs text-slate-500">
                {{ $charts->count() > 0 ? 'Max dip: ' . $charts->max('dip_cm') . ' cm' : 'No chart loaded' }}
            </div>
        </div>

        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="text-xs font-semibold uppercase tracking-wider text-slate-500">Permissible Loss (0.5%)</div>
            @php
                $allowableLoss = bcmul((string) $tank->current_stock, '0.005', 3);
            @endphp
            <div class="mt-2 text-2xl font-black text-amber-600 dark:text-amber-400 font-mono">
                ± {{ \App\Support\Quantity::format($allowableLoss) }} <span class="text-xs font-normal text-slate-400">L</span>
            </div>
            <div class="mt-1 text-xs text-slate-500">Standard evaporation tolerance</div>
        </div>
    </div>

    {{-- Interactive Physical Dip Reading & Evaporation Variance Tool --}}
    <div class="rounded-xl border-2 border-red-600 bg-white p-6 shadow-sm dark:border-red-600 dark:bg-slate-900"
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

        <div class="border-b border-slate-200 pb-3 dark:border-slate-800">
            <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>🔍</span> Record Physical Dip & Evaluate Evaporation Variance
            </h2>
            <p class="text-xs text-slate-500">
                Enter dip stick height in cm to calculate physical litres via calibration interpolation and evaluate against 0.5% permissible loss allowance.
            </p>
        </div>

        <form method="POST" action="{{ route('tanks.calibration.evaluate', $tank) }}" class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-4">
            @csrf

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Physical Dip Stick (cm) *</label>
                <input type="number" step="0.01" name="dip_cm" x-model="dipCm" placeholder="e.g. 145.5"
                       class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                <span class="text-[10px] text-slate-400">Auto-calculates litres below</span>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Or Direct Quantity (Litres)</label>
                <input type="number" step="0.001" name="physical_quantity" x-model="manualLitres" placeholder="Overrides dip cm"
                       class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Reading Date *</label>
                <input type="date" name="reading_date" value="{{ today()->toDateString() }}" required
                       class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Notes / Dip Rod Remarks</label>
                <input type="text" name="notes" placeholder="e.g. Morning opening dip reading"
                       class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            {{-- Live Analysis Preview Card --}}
            <div class="sm:col-span-4 rounded-xl border p-4 transition"
                 :class="isWithinTolerance ? 'border-emerald-300 bg-emerald-50/50 dark:border-emerald-800 dark:bg-emerald-950/20' : 'border-red-400 bg-red-50/50 dark:border-red-800 dark:bg-red-950/20'">
                <div class="grid grid-cols-2 gap-4 text-xs sm:grid-cols-4">
                    <div>
                        <div class="text-slate-500">Calculated Physical Stock</div>
                        <div class="text-lg font-black text-slate-900 dark:text-white font-mono mt-0.5">
                            <span x-text="physicalLitres.toLocaleString()"></span> L
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-500">Expected Book Stock</div>
                        <div class="text-lg font-bold text-slate-900 dark:text-white font-mono mt-0.5">
                            {{ \App\Support\Quantity::format($tank->current_stock) }} L
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-500">Physical Variance</div>
                        <div class="text-lg font-black font-mono mt-0.5"
                             :class="variance >= 0 ? 'text-emerald-600' : (isWithinTolerance ? 'text-amber-600' : 'text-red-600')">
                            <span x-text="variance >= 0 ? '+' : ''"></span><span x-text="variance.toLocaleString()"></span> L
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-500">Evaporation Tolerance Status</div>
                        <div class="text-sm font-bold mt-1"
                             :class="isWithinTolerance ? 'text-emerald-700 dark:text-emerald-300' : 'text-red-700 dark:text-red-400'">
                            <span x-show="isWithinTolerance">✓ Within 0.5% limit (±<span x-text="allowableLoss"></span> L)</span>
                            <span x-show="!isWithinTolerance">⚠️ EXCEEDS 0.5% EVAPORATION LOSS!</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sm:col-span-4 flex justify-end">
                <button type="submit" class="rounded-lg bg-red-600 px-6 py-2.5 text-xs font-bold text-white hover:bg-red-700 shadow-md transition">
                    Save Dip Reading & Record Variance Log →
                </button>
            </div>
        </form>
    </div>

    {{-- Calibration Management Section (Single Point & Bulk Import) --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Single Calibration Point --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                <span>➕</span> Add / Update Single Calibration Point
            </h2>
            <form method="POST" action="{{ route('tanks.calibration.store', $tank) }}" class="mt-4 space-y-4">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Dip Height (cm) *</label>
                        <input type="number" step="0.01" name="dip_cm" required placeholder="e.g. 50.0"
                               class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Volume (Litres) *</label>
                        <input type="number" step="0.001" name="litres" required placeholder="e.g. 7500"
                               class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-bold text-white hover:bg-slate-900 transition dark:bg-slate-700 dark:hover:bg-slate-600">
                        Save Point
                    </button>
                </div>
            </form>
        </div>

        {{-- Bulk Import Calibration Points --}}
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2 border-b border-slate-100 pb-3 dark:border-slate-800">
                <span>📥</span> Bulk Import Dip Chart (CSV / Space Separated)
            </h2>
            <form method="POST" action="{{ route('tanks.calibration.bulk', $tank) }}" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Paste Data (Format: cm, litres OR cm litres per line)</label>
                    <textarea name="data" rows="4" required placeholder="10, 850&#10;20, 1850&#10;30, 3100&#10;40, 4500"
                              class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white"></textarea>
                    <p class="text-[10px] text-slate-400 mt-1">One calibration point per line. Supports tab, comma, or space delimiters.</p>
                </div>

                <div class="flex justify-end pt-1">
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-bold text-white hover:bg-red-700 transition shadow-sm">
                        Import Dip Chart Points
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Calibration Chart Table --}}
    <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="border-b border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60 flex items-center justify-between">
            <h2 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                <span>📊</span> Calibrated Dip Curve Points ({{ $charts->count() }} Points)
            </h2>
            <span class="text-xs text-slate-500">Sorted ascending by dip height</span>
        </div>

        <div class="overflow-x-auto max-h-96">
            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                <thead class="border-b border-slate-200 bg-slate-100/70 font-bold uppercase tracking-wider text-slate-600 dark:border-slate-800 dark:bg-slate-800 dark:text-slate-400 sticky top-0">
                    <tr>
                        <th class="px-6 py-3">Dip Height (cm)</th>
                        <th class="px-6 py-3 text-right">Calibrated Volume (Litres)</th>
                        <th class="px-6 py-3 text-right">% of Tank Capacity</th>
                        <th class="px-6 py-3">Visual Fill Gauge</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 dark:divide-slate-800">
                    @forelse($charts as $point)
                        @php
                            $cap = (float) $tank->capacity;
                            $litres = (float) $point->litres;
                            $pct = $cap > 0 ? min(100, round(($litres / $cap) * 100, 1)) : 0;
                        @endphp
                        <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40 transition">
                            <td class="px-6 py-2.5 font-mono font-bold text-slate-900 dark:text-white">
                                {{ $point->dip_cm }} cm
                            </td>
                            <td class="px-6 py-2.5 text-right font-mono font-bold text-slate-900 dark:text-white">
                                {{ \App\Support\Quantity::format($point->litres) }} L
                            </td>
                            <td class="px-6 py-2.5 text-right font-mono font-semibold text-slate-600 dark:text-slate-400">
                                {{ $pct }}%
                            </td>
                            <td class="px-6 py-2.5">
                                <div class="w-full bg-slate-200 rounded-full h-2 dark:bg-slate-700">
                                    <div class="bg-red-600 h-2 rounded-full" style="width: {{ $pct }}%"></div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-8 text-center text-slate-400">
                                No dip chart points calibrated yet. Use the bulk import or single point form above to populate the chart.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
