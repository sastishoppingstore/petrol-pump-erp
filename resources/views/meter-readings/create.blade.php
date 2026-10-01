@extends('layouts.app')

@section('title', __('forecourt.meter_readings.page_title'))

@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('meter-readings.index') }}">{{ __('forecourt.meter_readings.title') }}</a></li>
    <li class="font-semibold">{{ __('forecourt.meter_readings.new_entry') }}</li>
@endsection

{{--
    Meter Reading Entry wizard — 2026 redesign.
    Picture-first 4-step wizard ab ek centered glass box me hai;
    mobile par app jaisa, desktop par wide. Alpine state, keypad
    logic, hidden form fields aur store route pehle jaisay hi hain.
--}}
@section('content')
<div x-data="{
    step: 1,
    selectedNozzleId: null,
    openingMeter: null,
    closingMeter: '',
    testLitres: '0',
    errors: {},
    lang: '{{ session('locale', 'ur') }}',
    async submitReading() {
        if (!this.selectedNozzleId) {
            this.errors.nozzle = this.lang === 'ur' ? 'نوزل منتخب کریں' : 'Select a nozzle';
            return;
        }
        if (!this.closingMeter || parseFloat(this.closingMeter) < parseFloat(this.openingMeter)) {
            this.errors.closing = this.lang === 'ur' ? 'ریڈنگ کم ہے، دوبارہ دیکھیں' : 'Closing meter cannot be lower than opening';
            navigator.vibrate && navigator.vibrate(200);
            return;
        }
        // Submit via form
        document.getElementById('meterForm').submit();
    }
}">

    {{-- ================= Header (centered) ================= --}}
    <div class="page-head">
        <h1>{{ __('forecourt.meter_readings.heading') }}</h1>
        <p>{{ __('forecourt.meter_readings.sub') }}</p>
    </div>

    {{-- Picture-First Meter Reading Wizard --}}
    <div class="glass-card mx-auto w-full max-w-[430px] p-2 sm:p-4 lg:max-w-2xl">

        {{-- Progress Indicator (Dots) --}}
        <div class="flex items-center justify-center gap-2 px-4 py-4">
            <span class="h-2.5 w-2.5 rounded-full" :class="step >= 1 ? 'bg-vital-primary' : 'bg-slate-300'"></span>
            <span class="h-2.5 w-2.5 rounded-full" :class="step >= 2 ? 'bg-vital-primary' : 'bg-slate-300'"></span>
            <span class="h-2.5 w-2.5 rounded-full" :class="step >= 3 ? 'bg-vital-primary' : 'bg-slate-300'"></span>
            <span class="h-2.5 w-2.5 rounded-full" :class="step >= 4 ? 'bg-vital-primary' : 'bg-slate-300'"></span>
        </div>

        {{-- STEP 1: Nozzle Selection (Picture-First) --}}
        <div x-show="step === 1" class="space-y-4 p-4">
            <div class="mb-4 text-center">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">⛽</h2>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    {{ session('locale') === 'ur' ? 'مرحلہ 1' : 'Step 1' }}
                </p>
                <p class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-200">
                    {{ session('locale') === 'ur' ? 'نوزل منتخب کریں' : 'Select a Nozzle' }}
                </p>
            </div>

            {{-- Nozzle Grid (Picture-Only) --}}
            <div class="grid grid-cols-2 gap-3">
                @forelse($nozzles as $nozzle)
                    <button type="button"
                            @click="selectedNozzleId = {{ $nozzle->id }}; step = 2; openingMeter = '{{ $nozzle->current_meter }}'"
                            class="nozzle-card glass-card card-3d p-4 text-center transition"
                            :class="selectedNozzleId === {{ $nozzle->id }} ? 'ring-2 ring-vital-primary shadow-glow' : ''">

                        {{-- Large Nozzle Icon with Fuel Color --}}
                        <div class="mb-2 text-4xl">
                            ⛽
                        </div>

                        {{-- Nozzle Number (Big) --}}
                        <div class="text-3xl font-black text-vital-primary">
                            {{ $nozzle->nozzle_number ?? 'N/A' }}
                        </div>

                        {{-- Fuel Type & Status --}}
                        <div class="mt-1 text-xs text-slate-600 dark:text-slate-300">
                            {{ $nozzle->fuelProduct?->name ?? 'Unknown' }}
                        </div>
                    </button>
                @empty
                    <div class="col-span-2 py-8 text-center">
                        <p class="text-sm text-slate-500">{{ session('locale') === 'ur' ? 'کوئی نوزل دستیاب نہیں' : 'No nozzles available' }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- STEP 2: Opening Meter (Auto-Filled, Locked) --}}
        <div x-show="step === 2" class="space-y-4 p-4">
            <div class="mb-4 text-center">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">📍</h2>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    {{ session('locale') === 'ur' ? 'مرحلہ 2' : 'Step 2' }}
                </p>
                <p class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-200">
                    {{ session('locale') === 'ur' ? 'شروعاتی ریڈنگ (خودکار)' : 'Opening Reading (Auto)' }}
                </p>
            </div>

            {{-- Opening Meter Display (Green Lock) --}}
            <div class="rounded-3xl border-2 border-emerald-200 bg-emerald-50 p-6 text-center shadow-3d dark:border-emerald-800 dark:bg-emerald-950">
                <div class="tabular text-[40px] font-black text-emerald-600 dark:text-emerald-400">
                    <span x-text="(parseFloat(openingMeter) || 0).toFixed(3)"></span>
                </div>
                <div class="mt-2 text-sm font-bold text-emerald-700 dark:text-emerald-300">
                    {{ session('locale') === 'ur' ? '✔️ موجودہ ریڈنگ' : '✔ Current Reading' }}
                </div>
            </div>

            {{-- Next Button --}}
            <button type="button"
                    @click="step = 3"
                    class="btn-3d btn-3d-primary w-full">
                {{ session('locale') === 'ur' ? 'اگلا ➜' : 'Next →' }}
            </button>
        </div>

        {{-- STEP 3: Closing Meter (Numeric Keypad) --}}
        <div x-show="step === 3" class="space-y-4 p-4">
            <div class="mb-4 text-center">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">📊</h2>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    {{ session('locale') === 'ur' ? 'مرحلہ 3' : 'Step 3' }}
                </p>
                <p class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-200">
                    {{ session('locale') === 'ur' ? 'اختتام ریڈنگ (کیبورڈ)' : 'Closing Reading' }}
                </p>
            </div>

            {{-- Closing Meter Input Display --}}
            <div class="rounded-3xl border-2 border-slate-300 bg-white/70 p-6 text-center shadow-3d dark:border-slate-700 dark:bg-slate-800/70">
                <div class="tabular text-[40px] font-black text-slate-800 dark:text-slate-200">
                    <span x-text="closingMeter || '0.000'"></span>
                </div>
                <div class="mt-2 text-xs text-slate-600 dark:text-slate-400">
                    {{ session('locale') === 'ur' ? 'لیٹر سے (شروع: ' : 'Litres (Opening: ' }}
                    <span x-text="(parseFloat(openingMeter) || 0).toFixed(3)"></span>
                    {{ session('locale') === 'ur' ? ')' : ')' }}
                </div>
            </div>

            {{-- Numeric Keypad --}}
            <div class="grid grid-cols-4 gap-2">
                @for ($i = 1; $i <= 9; $i++)
                    <button type="button"
                            @click="closingMeter += '{{ $i }}'"
                            class="rounded-xl bg-slate-100 py-3 text-xl font-black text-slate-800 shadow transition hover:bg-slate-200 active:scale-95 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">
                        {{ $i }}
                    </button>
                @endfor

                {{-- Decimal Point --}}
                <button type="button"
                        @click="if (!closingMeter.includes('.')) closingMeter += '.'"
                        class="rounded-xl bg-blue-100 py-3 text-xl font-black text-blue-800 shadow transition hover:bg-blue-200 active:scale-95 dark:bg-blue-900/60 dark:text-blue-200">
                    .
                </button>

                {{-- 0 --}}
                <button type="button"
                        @click="closingMeter += '0'"
                        class="col-span-2 rounded-xl bg-slate-100 py-3 text-xl font-black text-slate-800 shadow transition hover:bg-slate-200 active:scale-95 dark:bg-slate-800 dark:text-slate-100 dark:hover:bg-slate-700">
                    0
                </button>

                {{-- Backspace --}}
                <button type="button"
                        @click="closingMeter = closingMeter.slice(0, -1)"
                        class="rounded-xl bg-red-100 py-3 text-lg font-black text-red-800 shadow transition hover:bg-red-200 active:scale-95 dark:bg-red-900/60 dark:text-red-200">
                    ⌫
                </button>
            </div>

            {{-- Validation Message --}}
            <div x-show="closingMeter && parseFloat(closingMeter) < parseFloat(openingMeter)" class="rounded-2xl border-2 border-red-200 bg-red-50 p-3 text-center dark:border-red-800 dark:bg-red-950">
                <p class="text-sm font-bold text-red-700 dark:text-red-300">
                    {{ session('locale') === 'ur' ? '❌ ریڈنگ کم ہے!' : '❌ Reading is too low!' }}
                </p>
            </div>

            {{-- Next Button --}}
            <button type="button"
                    @click="step = 4"
                    :disabled="!closingMeter || parseFloat(closingMeter) < parseFloat(openingMeter)"
                    class="btn-3d btn-3d-primary w-full disabled:opacity-50">
                {{ session('locale') === 'ur' ? 'اگلا ➜' : 'Next →' }}
            </button>
        </div>

        {{-- STEP 4: Test Litres (Optional) --}}
        <div x-show="step === 4" class="space-y-4 p-4">
            <div class="mb-4 text-center">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">🧪</h2>
                <p class="text-xs font-bold uppercase tracking-wider text-slate-600 dark:text-slate-300">
                    {{ session('locale') === 'ur' ? 'مرحلہ 4' : 'Step 4' }}
                </p>
                <p class="mt-2 text-sm font-bold text-slate-800 dark:text-slate-200">
                    {{ session('locale') === 'ur' ? 'ٹیسٹ لیٹر (اختیاری)' : 'Test Litres (Optional)' }}
                </p>
            </div>

            {{-- Summary Before Confirmation --}}
            <div class="space-y-2 rounded-3xl border border-white/60 bg-white/60 p-4 shadow-3d dark:border-slate-700 dark:bg-slate-800/60">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ session('locale') === 'ur' ? 'شروع:' : 'Opening:' }}</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(parseFloat(openingMeter) || 0).toFixed(3)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ session('locale') === 'ur' ? 'اختتام:' : 'Closing:' }}</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(parseFloat(closingMeter) || 0).toFixed(3)"></span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-sm font-bold dark:border-slate-700">
                    <span class="text-slate-800 dark:text-slate-200">{{ session('locale') === 'ur' ? 'کل لیٹر:' : 'Total Litres:' }}</span>
                    <span class="text-lg text-vital-primary" x-text="(parseFloat(closingMeter) - parseFloat(openingMeter) || 0).toFixed(3)"></span>
                </div>
            </div>

            {{-- Submit Button --}}
            <form id="meterForm" method="POST" action="{{ route('meter-readings.store') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="nozzle_id" :value="selectedNozzleId">
                <input type="hidden" name="meter_start" :value="openingMeter">
                <input type="hidden" name="meter_end" :value="closingMeter">
                <input type="hidden" name="test_litres" :value="testLitres">

                <button type="submit"
                        class="btn-3d btn-3d-success w-full">
                    {{ session('locale') === 'ur' ? '✔ محفوظ کریں' : '✔ Save Reading' }}
                </button>

                <button type="button"
                        @click="step = 1"
                        class="btn-3d btn-3d-ghost w-full">
                    {{ session('locale') === 'ur' ? '← شروع سے' : '← Start Over' }}
                </button>
            </form>
        </div>

    </div>

</div>

<style>
    [x-cloak] { display: none; }
</style>
@endsection
