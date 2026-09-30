@extends('layouts.app')

@section('title', 'Forecourt Meter Readings — Vital Petroleum')
@section('breadcrumb')
    <li class="text-slate-500">Forecourt</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Meter Readings (Tile 1)</li>
@endsection

@section('content')
<div x-data="meterModule()" class="space-y-6">

    {{-- Header Banner with Vital Branding --}}
    <div class="rounded-xl border border-red-200 bg-gradient-to-r from-red-600 to-red-800 p-5 text-white shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-white/20 px-2 py-0.5 text-xs font-bold uppercase tracking-wider text-white">Tile 1</span>
                    <h1 class="text-xl font-bold tracking-tight">Forecourt Meter Readings & Calibration</h1>
                </div>
                <p class="mt-1 text-xs text-red-100">
                    Vital Petroleum — Mehar Filling Station, Sheikhupura · Daily Dispenser Meter Logs & Pump Calibration Tests
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <button type="button" @click="openTestModal()"
                        class="inline-flex items-center gap-1.5 rounded-lg bg-white px-3.5 py-2 text-xs font-bold text-red-700 shadow-sm hover:bg-red-50">
                    <span>🧪</span> Nozzle Test / Return (پیمانہ ٹیسٹ)
                </button>
                <button type="button" @click="openCorrectionModal()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-white/30 bg-black/20 px-3.5 py-2 text-xs font-bold text-white hover:bg-black/30">
                    <span>⚙️</span> Meter Correction (درستگی)
                </button>
            </div>
        </div>
    </div>

    {{-- Main Forecourt Grid --}}
    <div class="grid gap-6 lg:grid-cols-12">

        {{-- Left: Color-coded Nozzle Cards (8 cols) --}}
        <div class="space-y-4 lg:col-span-8">
            <div class="flex items-center justify-between">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Select Nozzle to Inspect / Enter Closing Meter
                </h2>
                <div class="flex items-center gap-3 text-xs font-medium">
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> Petrol</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> Diesel</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span> Hi-Octane</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($nozzles as $nozzle)
                    @php
                        $fuelName = strtolower($nozzle->fuelProduct?->name ?? '');
                        $isPetrol = str_contains($fuelName, 'petrol') || str_contains($fuelName, 'super') || str_contains($fuelName, 'pmg');
                        $isDiesel = str_contains($fuelName, 'diesel') || str_contains($fuelName, 'hsd');
                        $isHiOctane = str_contains($fuelName, 'octane') || str_contains($fuelName, 'hobc');

                        $badgeColor = $isPetrol ? 'bg-emerald-500 text-white' : ($isDiesel ? 'bg-amber-500 text-slate-900' : 'bg-blue-600 text-white');
                        $borderAccent = $isPetrol ? 'border-emerald-500' : ($isDiesel ? 'border-amber-500' : 'border-blue-500');
                        $bgCard = $isPetrol ? 'hover:border-emerald-400' : ($isDiesel ? 'hover:border-amber-400' : 'hover:border-blue-400');
                    @endphp

                    <div @click="selectNozzle({{ $nozzle->id }}, '{{ $nozzle->nozzle_number }}', '{{ $nozzle->dispenser?->dispenser_number }}', '{{ $nozzle->fuelProduct?->name }}', '{{ $nozzle->current_meter }}', '{{ $badgeColor }}')"
                         :class="selectedNozzle && selectedNozzle.id === {{ $nozzle->id }} ? 'ring-2 ring-red-600 border-red-600 shadow-md' : 'border-slate-200 dark:border-slate-700'"
                         class="cursor-pointer rounded-xl border-l-4 {{ $borderAccent }} {{ $bgCard }} bg-white p-4 shadow-sm transition hover:shadow dark:bg-slate-900">
                        <div class="flex items-start justify-between">
                            <div>
                                <span class="rounded px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $badgeColor }}">
                                    {{ $nozzle->fuelProduct?->name ?? 'Fuel' }}
                                </span>
                                <h3 class="mt-2 text-base font-bold text-slate-800 dark:text-white">
                                    Dispenser {{ $nozzle->dispenser?->dispenser_number ?? '1' }} · Nozzle {{ $nozzle->nozzle_number }}
                                </h3>
                                <p class="text-xs text-slate-500">Tank: {{ $nozzle->tank?->name ?? 'Main' }}</p>
                            </div>
                            <span class="text-2xl">⛽</span>
                        </div>

                        <div class="mt-4 rounded-lg bg-slate-50 p-2.5 dark:bg-slate-800/60">
                            <div class="text-[11px] font-medium uppercase text-slate-500">Current Meter (Last Closing)</div>
                            <div class="tabular font-mono text-lg font-bold text-slate-800 dark:text-white">
                                {{ number_format((float) $nozzle->current_meter, 3) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-full rounded-xl border border-slate-200 bg-white p-8 text-center text-slate-500 dark:border-slate-700 dark:bg-slate-900">
                        No active nozzles found. Please check dispenser and fuel setup.
                    </div>
                @endforelse
            </div>

            {{-- Recent Meter Readings & Tests Tabs --}}
            <div class="mt-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="mb-3 flex items-center justify-between border-b border-slate-200 pb-2 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-700 dark:text-slate-300">Calibration Tests & Audit History</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 uppercase text-slate-500 dark:bg-slate-800">
                            <tr>
                                <th class="p-2.5">Type</th>
                                <th class="p-2.5">Nozzle / Fuel</th>
                                <th class="p-2.5 text-right">Previous</th>
                                <th class="p-2.5 text-right">Current / New</th>
                                <th class="p-2.5 text-right">Throughput / Litres</th>
                                <th class="p-2.5">Reason / Note</th>
                                <th class="p-2.5">Operator</th>
                                <th class="p-2.5">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($recentReadings as $reading)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/40">
                                    <td class="p-2.5">
                                        <span @class([
                                            'rounded px-1.5 py-0.5 text-[10px] font-bold uppercase',
                                            'bg-emerald-100 text-emerald-800' => $reading->type === 'SALE',
                                            'bg-purple-100 text-purple-800' => $reading->type === 'NOZZLE_TEST',
                                            'bg-amber-100 text-amber-800' => $reading->type === 'CORRECTION',
                                            'bg-blue-100 text-blue-800' => $reading->type === 'ROLLOVER',
                                            'bg-slate-100 text-slate-800' => in_array($reading->type, ['OPENING', 'CLOSING']),
                                        ])>
                                            {{ $reading->type }}
                                        </span>
                                    </td>
                                    <td class="p-2.5 font-medium">
                                        N-{{ $reading->nozzle?->nozzle_number }} ({{ $reading->nozzle?->fuelProduct?->name }})
                                    </td>
                                    <td class="tabular p-2.5 text-right font-mono">{{ number_format((float) $reading->previous_meter, 3) }}</td>
                                    <td class="tabular p-2.5 text-right font-mono font-bold">{{ number_format((float) $reading->current_meter, 3) }}</td>
                                    <td class="tabular p-2.5 text-right font-mono font-semibold text-slate-700 dark:text-slate-200">
                                        {{ number_format((float) $reading->quantity, 3) }} L
                                    </td>
                                    <td class="p-2.5 text-slate-500">{{ $reading->reason ?: '—' }}</td>
                                    <td class="p-2.5 text-slate-600 dark:text-slate-400">{{ $reading->user?->name ?? 'System' }}</td>
                                    <td class="p-2.5 text-slate-400">{{ $reading->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="p-4 text-center text-slate-400">No recent meter entries recorded.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right: Big Touch Keypad & Actions Panel (4 cols) --}}
        <div class="space-y-4 lg:col-span-4">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="text-sm font-bold uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    Touch Entry Terminal
                </h2>

                <template x-if="!selectedNozzle">
                    <div class="mt-6 rounded-lg border-2 border-dashed border-slate-200 p-8 text-center text-slate-400 dark:border-slate-700">
                        <span class="text-3xl">👈</span>
                        <p class="mt-2 text-sm font-medium">Select a nozzle card from the left to enter readings.</p>
                    </div>
                </template>

                <template x-if="selectedNozzle">
                    <div class="mt-4 space-y-4">
                        {{-- Active Selection Details --}}
                        <div class="rounded-lg bg-slate-50 p-3.5 dark:bg-slate-800">
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-xs font-bold text-slate-500" x-text="'Dispenser ' + selectedNozzle.dispenser"></span>
                                    <h3 class="text-base font-bold text-slate-800 dark:text-white" x-text="'Nozzle ' + selectedNozzle.number + ' · ' + selectedNozzle.fuel"></h3>
                                </div>
                                <span class="rounded px-2 py-0.5 text-xs font-bold text-white" :class="selectedNozzle.badgeColor" x-text="selectedNozzle.fuel"></span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">
                                Opening Meter: <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="selectedNozzle.meter"></span>
                            </div>
                        </div>

                        {{-- Mode Selector --}}
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="activeKeypadMode = 'CLOSING'"
                                    :class="activeKeypadMode === 'CLOSING' ? 'bg-red-600 text-white font-bold' : 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'"
                                    class="rounded-lg py-2 text-xs transition">
                                Enter Closing Meter
                            </button>
                            <button type="button" @click="openTestModal()"
                                    class="rounded-lg bg-purple-50 py-2 text-xs font-bold text-purple-700 hover:bg-purple-100 dark:bg-purple-900/30 dark:text-purple-300">
                                🧪 Calibration Test
                            </button>
                        </div>

                        {{-- Digital Meter Display --}}
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-600 dark:text-slate-400">
                                <span x-text="isRollover ? 'Rollover Closing Meter:' : 'Closing Meter Reading:'"></span>
                            </label>
                            <div class="flex items-center rounded-lg border-2 border-slate-300 bg-slate-900 px-4 py-3 font-mono text-2xl font-bold text-emerald-400 shadow-inner dark:border-slate-600">
                                <span x-text="keypadInput || '0.000'" class="w-full text-right tracking-widest"></span>
                            </div>

                            <div class="mt-2 flex items-center justify-between">
                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                                    <input type="checkbox" x-model="isRollover" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                    <span>Meter Rollover (99999)</span>
                                </label>
                                <template x-if="isRollover">
                                    <select x-model="rolloverMax" class="rounded border-slate-300 py-0.5 text-xs dark:border-slate-700 dark:bg-slate-800">
                                        <option value="100000.000">Max 99,999</option>
                                        <option value="1000000.000">Max 999,999</option>
                                        <option value="10000000.000">Max 9,999,999</option>
                                    </select>
                                </template>
                            </div>

                            {{-- Live Calculated Litres --}}
                            <div class="mt-2 rounded-lg bg-emerald-50 p-2.5 text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <span>Throughput: </span>
                                <strong class="text-sm font-bold" x-text="calculatedThroughput() + ' Litres'"></strong>
                            </div>
                        </div>

                        {{-- Big Touch Keypad Grid --}}
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['1','2','3','4','5','6','7','8','9'] as $key)
                                <button type="button" @click="pressKey('{{ $key }}')"
                                        class="flex h-14 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xl font-bold text-slate-800 shadow-sm transition active:scale-95 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    {{ $key }}
                                </button>
                            @endforeach
                            <button type="button" @click="pressKey('.')"
                                    class="flex h-14 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xl font-bold text-slate-800 shadow-sm transition active:scale-95 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                .
                            </button>
                            <button type="button" @click="pressKey('0')"
                                    class="flex h-14 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-xl font-bold text-slate-800 shadow-sm transition active:scale-95 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                0
                            </button>
                            <button type="button" @click="backspace()"
                                    class="flex h-14 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-lg font-bold text-red-700 shadow-sm transition active:scale-95 hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                                ⌫
                            </button>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" @click="clearKeypad()"
                                    class="w-1/3 rounded-xl border border-slate-300 py-3 text-xs font-semibold text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300">
                                Clear
                            </button>

                            {{-- Rollover Form Submit --}}
                            <template x-if="isRollover">
                                <form method="POST" action="{{ route('forecourt.meters.rollover') }}" class="w-2/3">
                                    @csrf
                                    <input type="hidden" name="nozzle_id" :value="selectedNozzle.id">
                                    <input type="hidden" name="closing_meter" :value="keypadInput">
                                    <input type="hidden" name="rollover_max" :value="rolloverMax">
                                    <input type="hidden" name="reason" value="Meter Rollover recorded from Forecourt wizard">
                                    <button type="submit" :disabled="!keypadInput || parseFloat(keypadInput) < 0"
                                            class="w-full rounded-xl bg-blue-600 py-3 text-sm font-bold text-white shadow hover:bg-blue-700 disabled:opacity-50">
                                        Save Rollover Reading
                                    </button>
                                </form>
                            </template>

                            {{-- Standard Reading Form (Shift Closing Helper & Direct Log) --}}
                            <template x-if="!isRollover">
                                <div class="flex w-2/3 gap-2">
                                    <form method="POST" action="{{ route('forecourt.meters.closing') }}" class="w-full">
                                        @csrf
                                        <input type="hidden" name="nozzle_id" :value="selectedNozzle.id">
                                        <input type="hidden" name="closing_meter" :value="keypadInput">
                                        @if ($activeShift)
                                            <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                                        @endif
                                        <button type="submit" :disabled="!keypadInput || parseFloat(keypadInput) < parseFloat(selectedNozzle.meter)"
                                                class="w-full rounded-xl bg-red-600 py-3 text-sm font-bold text-white shadow hover:bg-red-700 disabled:opacity-50">
                                            Save Closing Reading
                                        </button>
                                    </form>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- ================= NOZZLE TEST MODAL ================= --}}
    <div x-show="showTestModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div @click.outside="showTestModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="rounded bg-purple-100 p-1.5 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300">🧪</span>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Record Nozzle Calibration Test (پیمانہ ٹیسٹ)</h3>
                </div>
                <button type="button" @click="showTestModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <p class="mt-2 text-xs text-slate-500">
                Calibration test fuel returned to tank. Excluded from sales and customer billing.
            </p>

            <form method="POST" action="{{ route('forecourt.meters.test') }}" class="mt-4 space-y-4">
                @csrf
                @if ($activeShift)
                    <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                @endif

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Select Nozzle</label>
                    <select name="nozzle_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                        @foreach ($nozzles as $nozzle)
                            <option value="{{ $nozzle->id }}" :selected="selectedNozzle && selectedNozzle.id === {{ $nozzle->id }}">
                                Dispenser {{ $nozzle->dispenser?->dispenser_number }} · Nozzle {{ $nozzle->nozzle_number }} ({{ $nozzle->fuelProduct?->name }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Test Measure Litres</label>
                    <div class="mt-1 flex gap-2">
                        @foreach (['5.000', '10.000', '20.000'] as $preset)
                            <button type="button" @click="testLitres = '{{ $preset }}'"
                                    :class="testLitres === '{{ $preset }}' ? 'bg-purple-700 text-white font-bold' : 'bg-slate-100 dark:bg-slate-800'"
                                    class="flex-1 rounded-lg py-2 text-xs transition">
                                {{ (float)$preset }} Litres
                            </button>
                        @endforeach
                    </div>
                    <input type="number" step="0.001" min="0.001" name="litres" x-model="testLitres" required
                           class="tabular mt-2 w-full rounded-lg border-slate-300 font-mono text-base font-bold dark:border-slate-700 dark:bg-slate-800">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Reason / Description</label>
                    <input type="text" name="reason" value="5L Standard Calibration Can Check (پیمانہ چیک)"
                           class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                </div>

                <div class="rounded-lg bg-amber-50 p-3 text-xs text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                    ⚠️ The nozzle meter will advance by <span x-text="testLitres || '0'"></span> L to reflect fuel pumped into measure container, and will be logged as returned to tank.
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showTestModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="rounded-lg bg-purple-700 px-5 py-2 text-xs font-bold text-white hover:bg-purple-800">Record Test & Return</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= METER CORRECTION MODAL ================= --}}
    <div x-show="showCorrectionModal" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm">
        <div @click.outside="showCorrectionModal = false" class="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3 dark:border-slate-800">
                <div class="flex items-center gap-2">
                    <span class="rounded bg-amber-100 p-1.5 text-amber-700 dark:bg-amber-900/50 dark:text-amber-300">⚙️</span>
                    <h3 class="text-base font-bold text-slate-800 dark:text-white">Supervisor Meter Correction (درستگی میٹر)</h3>
                </div>
                <button type="button" @click="showCorrectionModal = false" class="text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <p class="mt-2 text-xs text-slate-500">
                Authorized override for mechanical reset or pulser replacement. Writes an immutable audit log.
            </p>

            <form method="POST" action="{{ route('forecourt.meters.correction') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Select Nozzle</label>
                    <select name="nozzle_id" required class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                        @foreach ($nozzles as $nozzle)
                            <option value="{{ $nozzle->id }}" :selected="selectedNozzle && selectedNozzle.id === {{ $nozzle->id }}">
                                Dispenser {{ $nozzle->dispenser?->dispenser_number }} · Nozzle {{ $nozzle->nozzle_number }} (Current: {{ number_format((float) $nozzle->current_meter, 3) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">New Corrected Meter Reading</label>
                    <input type="number" step="0.001" min="0" name="new_meter" required
                           class="tabular mt-1 w-full rounded-lg border-slate-300 font-mono text-base font-bold dark:border-slate-700 dark:bg-slate-800"
                           placeholder="0.000">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">Mandatory Justification / Reason</label>
                    <textarea name="reason" rows="2" required
                              class="mt-1 w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"
                              placeholder="e.g. Dispenser pulser replaced / OGRA inspection reset"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showCorrectionModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-xs font-semibold">Cancel</button>
                    <button type="submit" class="rounded-lg bg-amber-600 px-5 py-2 text-xs font-bold text-white hover:bg-amber-700">Apply Meter Correction</button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
function meterModule() {
    return {
        selectedNozzle: null,
        activeKeypadMode: 'CLOSING',
        keypadInput: '',
        isRollover: false,
        rolloverMax: '100000.000',
        showTestModal: false,
        testLitres: '5.000',
        showCorrectionModal: false,

        selectNozzle(id, number, dispenser, fuel, meter, badgeColor) {
            this.selectedNozzle = { id, number, dispenser, fuel, meter, badgeColor };
            this.keypadInput = '';
            this.isRollover = false;
        },

        pressKey(char) {
            if (char === '.' && this.keypadInput.includes('.')) return;
            if (this.keypadInput.length >= 14) return;
            this.keypadInput += char;
        },

        backspace() {
            this.keypadInput = this.keypadInput.slice(0, -1);
        },

        clearKeypad() {
            this.keypadInput = '';
        },

        calculatedThroughput() {
            if (!this.selectedNozzle || !this.keypadInput) return '0.000';
            const open = parseFloat(this.selectedNozzle.meter || 0);
            const close = parseFloat(this.keypadInput || 0);

            if (this.isRollover) {
                const max = parseFloat(this.rolloverMax || 100000);
                const diff = (max - open) + close;
                return diff >= 0 ? diff.toFixed(3) : '0.000';
            }

            const diff = close - open;
            return diff >= 0 ? diff.toFixed(3) : '0.000';
        },

        openTestModal() {
            this.showTestModal = true;
        },

        openCorrectionModal() {
            this.showCorrectionModal = true;
        },

        useInShift() {
            if (window.confirm('Value ' + this.keypadInput + ' ready. Navigate to Shift Close screen?')) {
                window.location.href = "{{ route('shifts.index') }}";
            }
        }
    };
}
</script>
@endpush
