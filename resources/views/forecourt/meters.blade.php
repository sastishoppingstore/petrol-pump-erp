@extends('layouts.app')

@section('title', __('forecourt.meters.page_title'))
@section('breadcrumb')
    <li class="text-slate-500">{{ __('forecourt.meters.forecourt') }}</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('forecourt.meters.crumb') }}</li>
@endsection

{{--
    Forecourt Meter Readings — 2026 redesign.
    Nozzle cards ab 3D glass cards hain, keypad terminal glass box me,
    history table .table-3d me aur dono modals glass style me.
    Alpine meterModule(), tamam forms, routes aur hidden fields
    pehle jaisay hi hain — sirf markup/classes badle hain.
--}}
@section('content')
<div x-data="meterModule()" @keydown.escape.window="showTestModal = false; showCorrectionModal = false" class="space-y-6">

    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('forecourt.meters.heading') }}</h1>
        <p>{{ __('forecourt.meters.sub') }}</p>
        <div class="page-actions">
            <button type="button" @click="openTestModal()" class="btn-3d btn-3d-primary">
                <span>🧪</span> {{ __('forecourt.meters.test_tab') }}
            </button>
            <button type="button" @click="openCorrectionModal()" class="btn-3d btn-3d-amber">
                <span>⚙️</span> {{ __('forecourt.meters.correction_tab') }}
            </button>
        </div>
    </div>

    {{-- Main Forecourt Grid --}}
    <div class="grid gap-6 lg:grid-cols-12">

        {{-- Left: Color-coded Nozzle Cards (8 cols) --}}
        <div class="space-y-4 lg:col-span-8">
            <div class="text-center">
                <h2 class="text-sm font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    {{ __('forecourt.meters.select_heading') }}
                </h2>
                <div class="mt-2 flex flex-wrap items-center justify-center gap-3 text-xs font-semibold text-slate-500">
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-emerald-500"></span> {{ __('forecourt.common.petrol') }}</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-amber-500"></span> {{ __('forecourt.common.diesel') }}</span>
                    <span class="inline-flex items-center gap-1"><span class="h-2.5 w-2.5 rounded-full bg-blue-500"></span> {{ __('forecourt.common.hi_octane') }}</span>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @forelse ($nozzles as $nozzle)
                    @php
                        $fuelName = strtolower($nozzle->fuelProduct?->name ?? '');
                        $isPetrol = str_contains($fuelName, 'petrol') || str_contains($fuelName, 'super') || str_contains($fuelName, 'pmg');
                        $isDiesel = str_contains($fuelName, 'diesel') || str_contains($fuelName, 'hsd');
                        $isHiOctane = str_contains($fuelName, 'octane') || str_contains($fuelName, 'hobc');

                        $badgeColor = $isPetrol ? 'bg-emerald-500 text-white' : ($isDiesel ? 'bg-amber-500 text-slate-900' : 'bg-blue-600 text-white');
                        $badgeClass = $isPetrol ? 'badge-fuel-petrol' : ($isDiesel ? 'badge-fuel-diesel' : ($isHiOctane ? 'badge-fuel-octane' : 'badge-fuel-other'));
                    @endphp

                    <div @click="selectNozzle({{ $nozzle->id }}, '{{ $nozzle->nozzle_number }}', '{{ $nozzle->dispenser?->dispenser_number }}', '{{ $nozzle->fuelProduct?->name }}', '{{ $nozzle->current_meter }}', '{{ $badgeColor }}')"
                         :class="selectedNozzle && selectedNozzle.id === {{ $nozzle->id }} ? 'ring-2 ring-vital-primary shadow-glow' : ''"
                         class="glass-card card-3d cursor-pointer p-4 text-center">
                        <div class="text-3xl" aria-hidden="true">⛽</div>
                        <div class="mt-2">
                            <span class="inline-flex items-center rounded-full px-3 py-1 text-[10px] font-extrabold uppercase tracking-wider text-white shadow {{ $badgeClass }}">
                                {{ $nozzle->fuelProduct?->name ?? 'Fuel' }}
                            </span>
                        </div>
                        <h3 class="mt-2 text-base font-black text-slate-800 dark:text-white">
                            {{ __('forecourt.meters.dispenser_nozzle', ['dispenser' => $nozzle->dispenser?->dispenser_number ?? '1', 'nozzle' => $nozzle->nozzle_number]) }}
                        </h3>
                        <p class="text-xs text-slate-500">{{ __('forecourt.meters.tank_label') }} {{ $nozzle->tank?->name ?? 'Main' }}</p>

                        <div class="mt-4 rounded-2xl bg-white/60 p-3 dark:bg-white/5">
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-500">{{ __('forecourt.meters.current_last') }}</div>
                            <div class="tabular mt-0.5 font-mono text-lg font-black text-slate-800 dark:text-white">
                                {{ number_format((float) $nozzle->current_meter, 3) }}
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="glass-card col-span-full p-8 text-center text-slate-500">
                        {{ __('forecourt.meters.no_nozzles') }}
                    </div>
                @endforelse
            </div>

            {{-- Recent Meter Readings & Tests --}}
            <div class="glass-card mt-6 overflow-hidden">
                <div class="border-b border-slate-200/70 p-4 text-center dark:border-slate-700/50">
                    <h3 class="text-sm font-black text-slate-700 dark:text-slate-200">{{ __('forecourt.meters.history_heading') }}</h3>
                </div>
                <div class="table-3d">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('forecourt.common.type') }}</th>
                                <th>{{ __('forecourt.meters.nozzle_fuel') }}</th>
                                <th>{{ __('forecourt.common.previous') }}</th>
                                <th>{{ __('forecourt.meters.current_new') }}</th>
                                <th>{{ __('forecourt.meters.throughput_litres') }}</th>
                                <th>{{ __('forecourt.meters.reason_note') }}</th>
                                <th>{{ __('forecourt.meters.operator') }}</th>
                                <th>{{ __('forecourt.meters.time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentReadings as $reading)
                                <tr>
                                    <td>
                                        <span @class([
                                            'inline-flex rounded-full px-2 py-0.5 text-[10px] font-bold uppercase',
                                            'bg-emerald-100 text-emerald-800' => $reading->type === 'SALE',
                                            'bg-purple-100 text-purple-800' => $reading->type === 'NOZZLE_TEST',
                                            'bg-amber-100 text-amber-800' => $reading->type === 'CORRECTION',
                                            'bg-blue-100 text-blue-800' => $reading->type === 'ROLLOVER',
                                            'bg-slate-100 text-slate-800' => in_array($reading->type, ['OPENING', 'CLOSING']),
                                        ])>
                                            {{ $reading->type }}
                                        </span>
                                    </td>
                                    <td class="font-semibold">
                                        N-{{ $reading->nozzle?->nozzle_number }} ({{ $reading->nozzle?->fuelProduct?->name }})
                                    </td>
                                    <td class="tabular font-mono">{{ number_format((float) $reading->previous_meter, 3) }}</td>
                                    <td class="tabular font-mono font-bold">{{ number_format((float) $reading->current_meter, 3) }}</td>
                                    <td class="tabular font-mono font-semibold text-slate-700 dark:text-slate-200">
                                        {{ number_format((float) $reading->quantity, 3) }} L
                                    </td>
                                    <td class="text-slate-500">{{ $reading->reason ?: '—' }}</td>
                                    <td class="text-slate-600 dark:text-slate-400">{{ $reading->user?->name ?? 'System' }}</td>
                                    <td class="text-slate-400">{{ $reading->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="py-6 text-slate-400">{{ __('forecourt.meters.empty_history') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Right: Big Touch Keypad & Actions Panel (4 cols) --}}
        <div class="space-y-4 lg:col-span-4">
            <div class="glass-card p-5">
                <h2 class="text-center text-sm font-black uppercase tracking-wide text-slate-600 dark:text-slate-400">
                    {{ __('forecourt.meters.terminal') }}
                </h2>

                <template x-if="!selectedNozzle">
                    <div class="mt-6 rounded-2xl border-2 border-dashed border-slate-300 p-8 text-center text-slate-400 dark:border-slate-600">
                        <span class="text-3xl">👈</span>
                        <p class="mt-2 text-sm font-semibold">{{ __('forecourt.meters.select_prompt') }}</p>
                    </div>
                </template>

                <template x-if="selectedNozzle">
                    <div class="mt-4 space-y-4">
                        {{-- Active Selection Details --}}
                        <div class="rounded-2xl bg-white/60 p-4 text-center dark:bg-white/5">
                            <span class="text-xs font-bold text-slate-500" x-text="'Dispenser ' + selectedNozzle.dispenser"></span>
                            <h3 class="text-base font-black text-slate-800 dark:text-white" x-text="'Nozzle ' + selectedNozzle.number + ' · ' + selectedNozzle.fuel"></h3>
                            <div class="mt-2">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-bold shadow" :class="selectedNozzle.badgeColor" x-text="selectedNozzle.fuel"></span>
                            </div>
                            <div class="mt-2 text-xs text-slate-500">
                                {{ __('forecourt.meters.opening_label') }} <span class="font-mono font-bold text-slate-700 dark:text-slate-300" x-text="selectedNozzle.meter"></span>
                            </div>
                        </div>

                        {{-- Mode Selector --}}
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" @click="activeKeypadMode = 'CLOSING'"
                                    :class="activeKeypadMode === 'CLOSING' ? 'btn-3d btn-3d-primary w-full' : 'btn-3d btn-3d-ghost w-full'">
                                {{ __('forecourt.meters.enter_closing') }}
                            </button>
                            <button type="button" @click="openTestModal()"
                                    class="btn-3d btn-3d-navy w-full">
                                {{ __('forecourt.meters.test_btn') }}
                            </button>
                        </div>

                        {{-- Digital Meter Display --}}
                        <div>
                            <label class="mb-1 block text-center text-xs font-bold text-slate-600 dark:text-slate-400">
                                <span x-text="isRollover ? 'Rollover Closing Meter:' : 'Closing Meter Reading:'"></span>
                            </label>
                            <div class="flex items-center rounded-2xl border-2 border-slate-600 bg-slate-900 px-4 py-3 font-mono text-2xl font-bold text-emerald-400 shadow-3d">
                                <span x-text="keypadInput || '0.000'" class="w-full text-right tracking-widest"></span>
                            </div>

                            <div class="mt-2 flex items-center justify-between gap-2">
                                <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                                    <input type="checkbox" x-model="isRollover" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                    <span>{{ __('forecourt.meters.rollover') }}</span>
                                </label>
                                <template x-if="isRollover">
                                    <select x-model="rolloverMax" class="rounded-lg border-slate-300 py-0.5 text-xs dark:border-slate-700 dark:bg-slate-800">
                                        <option value="100000.000">{{ __('forecourt.meters.max_5') }}</option>
                                        <option value="1000000.000">{{ __('forecourt.meters.max_6') }}</option>
                                        <option value="10000000.000">{{ __('forecourt.meters.max_7') }}</option>
                                    </select>
                                </template>
                            </div>

                            {{-- Live Calculated Litres --}}
                            <div class="mt-2 rounded-2xl bg-emerald-50 p-2.5 text-center text-xs text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                <span>{{ __('forecourt.meters.throughput') }} </span>
                                <strong class="text-sm font-black" x-text="calculatedThroughput() + ' Litres'"></strong>
                            </div>
                        </div>

                        {{-- Big Touch Keypad Grid --}}
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (['1','2','3','4','5','6','7','8','9'] as $key)
                                <button type="button" @click="pressKey('{{ $key }}')"
                                        class="flex h-14 items-center justify-center rounded-2xl border border-white/60 bg-white/70 text-xl font-black text-slate-800 shadow-3d transition hover:bg-white active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                    {{ $key }}
                                </button>
                            @endforeach
                            <button type="button" @click="pressKey('.')"
                                    class="flex h-14 items-center justify-center rounded-2xl border border-white/60 bg-white/70 text-xl font-black text-slate-800 shadow-3d transition hover:bg-white active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                .
                            </button>
                            <button type="button" @click="pressKey('0')"
                                    class="flex h-14 items-center justify-center rounded-2xl border border-white/60 bg-white/70 text-xl font-black text-slate-800 shadow-3d transition hover:bg-white active:scale-95 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                0
                            </button>
                            <button type="button" @click="backspace()"
                                    class="flex h-14 items-center justify-center rounded-2xl border border-red-200 bg-red-50 text-lg font-black text-red-700 shadow-3d transition hover:bg-red-100 active:scale-95 dark:border-red-900 dark:bg-red-950 dark:text-red-300">
                                ⌫
                            </button>
                        </div>

                        <div class="flex gap-2">
                            <button type="button" @click="clearKeypad()"
                                    class="btn-3d btn-3d-ghost w-1/3">
                                {{ __('forecourt.meters.clear') }}
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
                                            class="btn-3d btn-3d-navy w-full disabled:opacity-50">
                                        {{ __('forecourt.meters.save_rollover') }}
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
                                                class="btn-3d btn-3d-primary w-full disabled:opacity-50">
                                            {{ __('forecourt.meters.save_closing') }}
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
         class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 backdrop-blur-[2px] sm:items-center sm:p-6">
        <div @click.outside="showTestModal = false" class="glass-card modal-bounce relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-b-none p-6 sm:rounded-b-2xl">
            <div class="relative border-b border-slate-200/70 pb-3 text-center dark:border-slate-700/50">
                <h3 class="text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.meters.test_heading') }}</h3>
                <button type="button" @click="showTestModal = false" class="absolute right-0 top-0 text-lg text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <p class="mt-2 text-center text-xs text-slate-500">
                {{ __('forecourt.meters.test_note') }}
            </p>

            <form method="POST" action="{{ route('forecourt.meters.test') }}" class="mt-4 space-y-4">
                @csrf
                @if ($activeShift)
                    <input type="hidden" name="shift_id" value="{{ $activeShift->id }}">
                @endif

                <div>
                    <label class="mb-1.5 block text-center text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('forecourt.meters.select_nozzle') }}</label>
                    <div class="field-3d">
                        <select name="nozzle_id" required class="input-3d">
                            @foreach ($nozzles as $nozzle)
                                <option value="{{ $nozzle->id }}" :selected="selectedNozzle && selectedNozzle.id === {{ $nozzle->id }}">
                                    {{ __('forecourt.meters.dispenser_nozzle', ['dispenser' => $nozzle->dispenser?->dispenser_number, 'nozzle' => $nozzle->nozzle_number]) }} ({{ $nozzle->fuelProduct?->name }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-center text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('forecourt.meters.test_litres') }}</label>
                    <div class="flex gap-2">
                        @foreach (['5.000', '10.000', '20.000'] as $preset)
                            <button type="button" @click="testLitres = '{{ $preset }}'"
                                    :class="testLitres === '{{ $preset }}' ? 'bg-vital-primary text-white font-bold shadow' : 'bg-slate-100 dark:bg-slate-800'"
                                    class="flex-1 rounded-xl py-2 text-xs transition">
                                {{ (float)$preset }} {{ __('forecourt.common.litres') }}
                            </button>
                        @endforeach
                    </div>
                    <div class="field-3d mt-2">
                        <input type="number" step="0.001" min="0.001" name="litres" x-model="testLitres" required
                               class="input-3d tabular text-center font-mono text-base font-bold">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-center text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('forecourt.meters.reason_desc') }}</label>
                    <div class="field-3d">
                        <input type="text" name="reason" value="5L Standard Calibration Can Check (پیمانہ چیک)"
                               class="input-3d">
                    </div>
                </div>

                <div class="rounded-2xl bg-amber-50 p-3 text-center text-xs text-amber-800 dark:bg-amber-950/40 dark:text-amber-300">
                    {{ __('forecourt.meters.warn_advance_1') }}<span x-text="testLitres || '0'"></span>{{ __('forecourt.meters.warn_advance_2') }}
                </div>

                <div class="flex flex-wrap justify-center gap-2 pt-2">
                    <button type="button" @click="showTestModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.cancel') }}</button>
                    <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">{{ __('forecourt.meters.record_test') }}</button>
                </div>
            </form>
        </div>
    </div>

    {{-- ================= METER CORRECTION MODAL ================= --}}
    <div x-show="showCorrectionModal" x-cloak
         class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/60 backdrop-blur-[2px] sm:items-center sm:p-6">
        <div @click.outside="showCorrectionModal = false" class="glass-card modal-bounce relative max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-b-none p-6 sm:rounded-b-2xl">
            <div class="relative border-b border-slate-200/70 pb-3 text-center dark:border-slate-700/50">
                <h3 class="text-base font-black text-slate-800 dark:text-white">{{ __('forecourt.meters.correction_heading') }}</h3>
                <button type="button" @click="showCorrectionModal = false" class="absolute right-0 top-0 text-lg text-slate-400 hover:text-slate-600">✕</button>
            </div>

            <p class="mt-2 text-center text-xs text-slate-500">
                {{ __('forecourt.meters.correction_note') }}
            </p>

            <form method="POST" action="{{ route('forecourt.meters.correction') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label class="mb-1.5 block text-center text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('forecourt.meters.select_nozzle') }}</label>
                    <div class="field-3d">
                        <select name="nozzle_id" required class="input-3d">
                            @foreach ($nozzles as $nozzle)
                                <option value="{{ $nozzle->id }}" :selected="selectedNozzle && selectedNozzle.id === {{ $nozzle->id }}">
                                    {{ __('forecourt.meters.dispenser_nozzle', ['dispenser' => $nozzle->dispenser?->dispenser_number, 'nozzle' => $nozzle->nozzle_number]) }} ({{ __('forecourt.meters.current_label', ['meter' => number_format((float) $nozzle->current_meter, 3)]) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-center text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('forecourt.meters.new_corrected') }}</label>
                    <div class="field-3d">
                        <input type="number" step="0.001" min="0" name="new_meter" required
                               class="input-3d tabular text-center font-mono text-base font-bold"
                               placeholder="0.000">
                    </div>
                </div>

                <div>
                    <label class="mb-1.5 block text-center text-xs font-bold text-slate-700 dark:text-slate-300">{{ __('forecourt.meters.justification') }}</label>
                    <div class="field-3d">
                        <textarea name="reason" rows="2" required
                                  class="input-3d"
                                  placeholder="{{ __('forecourt.meters.reason_placeholder') }}"></textarea>
                    </div>
                </div>

                <div class="flex flex-wrap justify-center gap-2 pt-2">
                    <button type="button" @click="showCorrectionModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.cancel') }}</button>
                    <button type="submit" class="btn-3d btn-3d-amber btn-3d-sm">{{ __('forecourt.meters.apply_correction') }}</button>
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
