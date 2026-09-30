@extends('layouts.app')

@section('title', 'میٹر ریڈنگ درج کریں / Meter Reading Entry')

@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('meter-readings.index') }}">Meter Readings</a></li>
    <li class="font-semibold">New Entry</li>
@endsection

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
}" class="min-h-screen bg-gradient-to-b from-slate-50 to-white dark:from-slate-950 dark:to-slate-900">

    {{-- Picture-First Meter Reading Wizard --}}
    <div class="max-w-[430px] mx-auto">

        {{-- Progress Indicator (Dots) --}}
        <div class="flex items-center justify-center gap-2 py-4 px-4">
            <span class="w-2.5 h-2.5 rounded-full" :class="step >= 1 ? 'bg-vital-primary' : 'bg-slate-300'"></span>
            <span class="w-2.5 h-2.5 rounded-full" :class="step >= 2 ? 'bg-vital-primary' : 'bg-slate-300'"></span>
            <span class="w-2.5 h-2.5 rounded-full" :class="step >= 3 ? 'bg-vital-primary' : 'bg-slate-300'"></span>
            <span class="w-2.5 h-2.5 rounded-full" :class="step >= 4 ? 'bg-vital-primary' : 'bg-slate-300'"></span>
        </div>

        {{-- STEP 1: Nozzle Selection (Picture-First) --}}
        <div x-show="step === 1" class="p-4 space-y-4">
            <div class="text-center mb-4">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">⛽</h2>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                    {{ session('locale') === 'ur' ? 'مرحلہ 1' : 'Step 1' }}
                </p>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-2">
                    {{ session('locale') === 'ur' ? 'نوزل منتخب کریں' : 'Select a Nozzle' }}
                </p>
            </div>

            {{-- Nozzle Grid (Picture-Only) --}}
            <div class="grid grid-cols-2 gap-3">
                @forelse($nozzles as $nozzle)
                    <button type="button"
                            @click="selectedNozzleId = {{ $nozzle->id }}; step = 2; openingMeter = '{{ $nozzle->current_meter }}'"
                            class="nozzle-card p-4 rounded-2xl border-2 transition" 
                            :class="selectedNozzleId === {{ $nozzle->id }} ? 'border-vital-primary bg-red-50 dark:bg-red-950' : 'border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 hover:border-vital-primary'">
                        
                        {{-- Large Nozzle Icon with Fuel Color --}}
                        <div class="text-4xl mb-2">
                            ⛽
                        </div>
                        
                        {{-- Nozzle Number (Big) --}}
                        <div class="text-3xl font-black text-vital-primary">
                            {{ $nozzle->nozzle_number ?? 'N/A' }}
                        </div>
                        
                        {{-- Fuel Type & Status --}}
                        <div class="text-xs text-slate-600 dark:text-slate-300 mt-1">
                            {{ $nozzle->fuel_product->name ?? 'Unknown' }}
                        </div>
                    </button>
                @empty
                    <div class="col-span-2 text-center py-8">
                        <p class="text-sm text-slate-500">{{ session('locale') === 'ur' ? 'کوئی نوزل دستیاب نہیں' : 'No nozzles available' }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- STEP 2: Opening Meter (Auto-Filled, Locked) --}}
        <div x-show="step === 2" class="p-4 space-y-4">
            <div class="text-center mb-4">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">📍</h2>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                    {{ session('locale') === 'ur' ? 'مرحلہ 2' : 'Step 2' }}
                </p>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-2">
                    {{ session('locale') === 'ur' ? 'شروعاتی ریڈنگ (خودکار)' : 'Opening Reading (Auto)' }}
                </p>
            </div>

            {{-- Opening Meter Display (Green Lock) --}}
            <div class="bg-emerald-50 dark:bg-emerald-950 border-2 border-emerald-200 dark:border-emerald-800 rounded-2xl p-6 text-center">
                <div class="text-[40px] font-black text-emerald-600 dark:text-emerald-400 tabular">
                    <span x-text="(parseFloat(openingMeter) || 0).toFixed(3)"></span>
                </div>
                <div class="text-sm text-emerald-700 dark:text-emerald-300 font-bold mt-2">
                    {{ session('locale') === 'ur' ? '✔️ موجودہ ریڈنگ' : '✔ Current Reading' }}
                </div>
            </div>

            {{-- Next Button --}}
            <button type="button"
                    @click="step = 3"
                    class="w-full bg-vital-primary hover:bg-vital-dark-red text-white font-black py-4 rounded-xl transition transform active:scale-95">
                {{ session('locale') === 'ur' ? 'اگلا ➜' : 'Next →' }}
            </button>
        </div>

        {{-- STEP 3: Closing Meter (Numeric Keypad) --}}
        <div x-show="step === 3" class="p-4 space-y-4">
            <div class="text-center mb-4">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">📊</h2>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                    {{ session('locale') === 'ur' ? 'مرحلہ 3' : 'Step 3' }}
                </p>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-2">
                    {{ session('locale') === 'ur' ? 'اختتام ریڈنگ (کیبورڈ)' : 'Closing Reading' }}
                </p>
            </div>

            {{-- Closing Meter Input Display --}}
            <div class="bg-slate-100 dark:bg-slate-800 rounded-2xl p-6 text-center border-2 border-slate-300 dark:border-slate-700">
                <div class="text-[40px] font-black text-slate-800 dark:text-slate-200 tabular">
                    <span x-text="closingMeter || '0.000'"></span>
                </div>
                <div class="text-xs text-slate-600 dark:text-slate-400 mt-2">
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
                            class="bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-black text-xl py-3 rounded-lg transition active:scale-95">
                        {{ $i }}
                    </button>
                @endfor
                
                {{-- Decimal Point --}}
                <button type="button"
                        @click="if (!closingMeter.includes('.')) closingMeter += '.'"
                        class="bg-blue-200 dark:bg-blue-800 hover:bg-blue-300 text-blue-800 dark:text-blue-200 font-black text-xl py-3 rounded-lg transition active:scale-95">
                    .
                </button>
                
                {{-- 0 --}}
                <button type="button"
                        @click="closingMeter += '0'"
                        class="col-span-2 bg-slate-200 dark:bg-slate-700 hover:bg-slate-300 dark:hover:bg-slate-600 text-slate-800 dark:text-slate-200 font-black text-xl py-3 rounded-lg transition active:scale-95">
                    0
                </button>
                
                {{-- Backspace --}}
                <button type="button"
                        @click="closingMeter = closingMeter.slice(0, -1)"
                        class="bg-red-200 dark:bg-red-800 hover:bg-red-300 text-red-800 dark:text-red-200 font-black text-lg py-3 rounded-lg transition active:scale-95">
                    ⌫
                </button>
            </div>

            {{-- Validation Message --}}
            <div x-show="closingMeter && parseFloat(closingMeter) < parseFloat(openingMeter)" class="bg-red-50 dark:bg-red-950 border-2 border-red-200 dark:border-red-800 rounded-lg p-3 text-center">
                <p class="text-sm font-bold text-red-700 dark:text-red-300">
                    {{ session('locale') === 'ur' ? '❌ ریڈنگ کم ہے!' : '❌ Reading is too low!' }}
                </p>
            </div>

            {{-- Next Button --}}
            <button type="button"
                    @click="step = 4"
                    :disabled="!closingMeter || parseFloat(closingMeter) < parseFloat(openingMeter)"
                    class="w-full bg-vital-primary hover:bg-vital-dark-red disabled:bg-slate-300 text-white font-black py-4 rounded-xl transition transform active:scale-95">
                {{ session('locale') === 'ur' ? 'اگلا ➜' : 'Next →' }}
            </button>
        </div>

        {{-- STEP 4: Test Litres (Optional) --}}
        <div x-show="step === 4" class="p-4 space-y-4">
            <div class="text-center mb-4">
                <h2 class="text-xl font-black text-slate-800 dark:text-white">🧪</h2>
                <p class="text-xs font-bold text-slate-600 dark:text-slate-300 uppercase tracking-wider">
                    {{ session('locale') === 'ur' ? 'مرحلہ 4' : 'Step 4' }}
                </p>
                <p class="text-sm font-bold text-slate-800 dark:text-slate-200 mt-2">
                    {{ session('locale') === 'ur' ? 'ٹیسٹ لیٹر (اختیاری)' : 'Test Litres (Optional)' }}
                </p>
            </div>

            {{-- Summary Before Confirmation --}}
            <div class="bg-slate-50 dark:bg-slate-900 rounded-2xl p-4 border border-slate-200 dark:border-slate-800 space-y-2">
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ session('locale') === 'ur' ? 'شروع:' : 'Opening:' }}</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(parseFloat(openingMeter) || 0).toFixed(3)"></span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-slate-600 dark:text-slate-400">{{ session('locale') === 'ur' ? 'اختتام:' : 'Closing:' }}</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200" x-text="(parseFloat(closingMeter) || 0).toFixed(3)"></span>
                </div>
                <div class="border-t border-slate-200 dark:border-slate-700 pt-2 flex justify-between text-sm font-bold">
                    <span class="text-slate-800 dark:text-slate-200">{{ session('locale') === 'ur' ? 'کل لیٹر:' : 'Total Litres:' }}</span>
                    <span class="text-vital-primary text-lg" x-text="(parseFloat(closingMeter) - parseFloat(openingMeter) || 0).toFixed(3)"></span>
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
                        class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-black py-4 rounded-xl transition transform active:scale-95">
                    {{ session('locale') === 'ur' ? '✔ محفوظ کریں' : '✔ Save Reading' }}
                </button>
                
                <button type="button"
                        @click="step = 1"
                        class="w-full bg-slate-300 dark:bg-slate-700 hover:bg-slate-400 text-slate-800 dark:text-slate-200 font-bold py-3 rounded-xl transition">
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
