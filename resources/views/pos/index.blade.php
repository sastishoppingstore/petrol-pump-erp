@extends('layouts.app')

@section('title', {{ __('sales.pos.title') }})
@section('breadcrumb')
    <li class="text-slate-500">{{ __('sales.pos.breadcrumb_forecourt') }}</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.pos.breadcrumb_pos') }}</li>
@endsection

@section('content')
<div x-data="posWizard(@js($nozzles->map(fn($n) => [
        'id' => $n->id,
        'number' => $n->nozzle_number,
        'dispenser' => $n->dispenser?->dispenser_number ?? '1',
        'label' => $n->label(),
        'fuel' => $n->fuelProduct?->name ?? 'Fuel',
        'rate' => (float) $n->fuelProduct?->currentPrice($branchId),
        'tank_stock' => (float) $n->tank?->current_stock,
        'tank_name' => $n->tank?->name,
    ])), @js($customers->map(fn($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'code' => $c->code,
        'phone' => $c->phone,
        'balance' => (float) $c->outstandingBalance(),
        'limit' => (float) $c->credit_limit,
        'is_unlimited' => $c->creditLimitIsUnlimited(),
        'vehicles' => $c->vehicles->map(fn($v) => ['id' => $v->id, 'reg' => $v->registration_number]),
    ])))" class="space-y-4">

    {{-- Top Bar: Shift info & quick status --}}
    <div class="glass-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-vital-primary to-vital-darkred text-xl text-white shadow-glow">⛽</span>
            <div>
                <div class="flex items-center gap-2">
                    <span class="rounded bg-red-100 px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider text-red-800 dark:bg-red-950 dark:text-red-300">{{ __('sales.pos.tile_badge') }}</span>
                    <h1 class="text-base font-bold text-slate-800 dark:text-white">{{ __('sales.pos.heading') }}</h1>
                </div>
                <p class="text-xs text-slate-500">
                    @if ($shift)
                        {{ __('sales.pos.active_shift') }}: <strong class="text-slate-800 dark:text-slate-200">{{ $shift->shift_number }}</strong> · {{ __('sales.pos.float_label') }}: <strong>{{ \App\Support\PakistaniCurrency::format($shift->opening_cash) }}</strong>
                    @else
                        <span class="font-semibold text-amber-600">{{ __('sales.pos.no_shift') }}</span>
                    @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('forecourt.meters.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                <span>📐</span> {{ __('sales.pos.meter_readings') }}
            </a>
            <a href="{{ route('sales.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                <span>📜</span> {{ __('sales.pos.sales_history') }}
            </a>
        </div>
    </div>

    {{-- Validation / failure messages — pehle ye kahin dikhte hi nahi thay,
         is liye sale fail hone par cashier ko sirf "bill nahi bana" nazar aata tha --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 shadow-3d dark:border-red-800 dark:bg-red-950/40 dark:text-red-200" role="alert">
            <strong>⚠ {{ __('sales.pos.sale_failed') }}:</strong>
            <ul class="mt-1 list-disc ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Main POS Layout Grid --}}
    <form method="POST" action="{{ route('pos.store') }}" @submit="handleSubmit($event)">
        @csrf
        <input type="hidden" name="request_token" value="{{ app(\App\Services\Sale\SaleService::class)->newRequestToken() }}">
        <input type="hidden" name="branch_id" value="{{ $branchId }}">
        @if ($shift)
            <input type="hidden" name="shift_id" value="{{ $shift->id }}">
        @endif

        <div class="grid gap-4 lg:grid-cols-12">

            {{-- Left: Dispensers & Nozzles + Quantity Pad (7 cols) --}}
            <div class="space-y-4 lg:col-span-7">

                {{-- 1. Dispenser & Nozzle Selector --}}
                <div class="glass-card p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.pos.step1_nozzle') }}</h2>
                        <span class="text-xs text-slate-400" x-text="nozzles.length + ' nozzles active'"></span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        <template x-for="n in nozzles" :key="n.id">
                            <button type="button" @click="selectNozzle(n)"
                                    :class="{
                                        'ring-2 ring-red-600 shadow-md': selectedNozzle && selectedNozzle.id === n.id,
                                        'border-emerald-500 bg-emerald-50/40 text-emerald-950 dark:bg-emerald-950/20 dark:text-emerald-100': isPetrol(n.fuel),
                                        'border-amber-500 bg-amber-50/40 text-amber-950 dark:bg-amber-950/20 dark:text-amber-100': isDiesel(n.fuel),
                                        'border-blue-500 bg-blue-50/40 text-blue-950 dark:bg-blue-950/20 dark:text-blue-100': isHiOctane(n.fuel),
                                    }"
                                    class="relative rounded-2xl border-l-4 p-3 text-left shadow-3d transition hover:-translate-y-0.5 hover:shadow-3d-lg active:translate-y-0">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-extrabold uppercase" x-text="'D-' + n.dispenser + ' · N-' + n.number"></span>
                                    <span class="text-lg">⛽</span>
                                </div>
                                <div class="mt-1 text-sm font-black" x-text="n.fuel"></div>
                                <div class="tabular mt-1 text-xs font-bold text-slate-700 dark:text-slate-300" x-text="'Rs. ' + n.rate.toFixed(2) + '/L'"></div>
                                <div class="mt-1 text-[10px] text-slate-500" x-text="'Stock: ' + n.tank_stock.toFixed(0) + ' L'"></div>
                            </button>
                        </template>
                    </div>

                    <div class="hidden" aria-hidden="true">
                        @foreach ($nozzles as $noz)
                            <span>D-{{ $noz->dispenser?->dispenser_number ?? '1' }} · N-{{ $noz->nozzle_number }}</span>
                        @endforeach
                    </div>

                    <template x-if="nozzles.length === 0">
                        <div class="p-6 text-center text-sm text-slate-400">
                            {{ __('sales.pos.no_nozzles') }}
                        </div>
                    </template>
                </div>

                {{-- 2. Litres / Amount Quantity Calculator --}}
                <div class="glass-card p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.pos.step2_quantity') }}</h2>
                        <div class="flex rounded-lg bg-slate-100 p-0.5 dark:bg-slate-800">
                            <button type="button" @click="setMode('LITRES')"
                                    :class="mode === 'LITRES' ? 'bg-red-600 text-white font-bold shadow-sm' : 'text-slate-600 dark:text-slate-300'"
                                    class="rounded-md px-3 py-1 text-xs transition">
                                {{ __('sales.pos.litres_mode') }}
                            </button>
                            <button type="button" @click="setMode('AMOUNT')"
                                    :class="mode === 'AMOUNT' ? 'bg-red-600 text-white font-bold shadow-sm' : 'text-slate-600 dark:text-slate-300'"
                                    class="rounded-md px-3 py-1 text-xs transition">
                                {{ __('sales.pos.amount_mode') }}
                            </button>
                        </div>
                    </div>

                    <template x-if="!selectedNozzle">
                        <div class="p-8 text-center text-sm text-slate-400">
                            👈 {{ __('sales.pos.pick_nozzle') }}
                        </div>
                    </template>

                    <template x-if="selectedNozzle">
                        <div class="space-y-4">
                            {{-- Quick Preset Buttons --}}
                            <div>
                                <span class="mb-1.5 block text-[11px] font-semibold text-slate-500 uppercase">{{ __('sales.pos.quick_presets') }}</span>
                                <div class="grid grid-cols-5 gap-2">
                                    <template x-if="mode === 'LITRES'">
                                        <template x-for="q in ['5', '10', '20', '35', '50']">
                                            <button type="button" @click="inputValue = q"
                                                    :class="inputValue === q ? 'bg-slate-900 text-white' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800'"
                                                    class="rounded-xl py-2.5 text-center text-xs font-bold shadow-3d transition hover:-translate-y-0.5 active:translate-y-0">
                                                <span x-text="q + ' L'"></span>
                                            </button>
                                        </template>
                                    </template>
                                    <template x-if="mode === 'AMOUNT'">
                                        <template x-for="a in ['500', '1000', '2000', '3000', '5000']">
                                            <button type="button" @click="inputValue = a"
                                                    :class="inputValue === a ? 'bg-slate-900 text-white' : 'bg-slate-100 hover:bg-slate-200 dark:bg-slate-800'"
                                                    class="rounded-xl py-2.5 text-center text-xs font-bold shadow-3d transition hover:-translate-y-0.5 active:translate-y-0">
                                                <span x-text="'Rs.' + a"></span>
                                            </button>
                                        </template>
                                    </template>
                                </div>
                            </div>

                            {{-- Display & Digital Input --}}
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">
                                        <span x-text="mode === 'LITRES' ? 'Enter Litres Dispensed:' : 'Enter Amount (Rs.):'"></span>
                                    </label>
                                    <div class="flex items-center rounded-lg border-2 border-slate-300 bg-slate-900 px-3 py-2.5 font-mono text-xl font-bold text-emerald-400 dark:border-slate-700">
                                        <span x-text="inputValue || '0'" class="w-full text-right tracking-wider"></span>
                                    </div>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.pos.fuel_rate') }}</label>
                                    <div class="flex items-center rounded-lg border border-slate-300 bg-slate-100 px-3 py-2.5 font-mono text-xl font-bold text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                        <span x-text="'Rs. ' + selectedNozzle.rate.toFixed(2)" class="w-full text-right"></span>
                                    </div>
                                </div>
                            </div>

                            {{-- Touch Keypad for Quick Amount/Litre Entry --}}
                            <div class="grid grid-cols-6 gap-1.5">
                                @foreach (['1','2','3','4','5','6','7','8','9','0','.'] as $key)
                                    <button type="button" @click="pressKey('{{ $key }}')"
                                            class="flex h-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-base font-bold text-slate-800 shadow-3d transition hover:-translate-y-0.5 hover:bg-slate-50 active:translate-y-0 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                        {{ $key }}
                                    </button>
                                @endforeach
                                <button type="button" @click="backspace()"
                                        class="flex h-11 items-center justify-center rounded-xl border border-red-200 bg-red-50 text-sm font-bold text-red-700 shadow-3d transition hover:-translate-y-0.5 hover:bg-red-100 active:translate-y-0 dark:border-red-950 dark:bg-red-950 dark:text-red-300">
                                    ⌫
                                </button>
                            </div>

                            {{-- Hidden quantity parameter --}}
                            <input type="hidden" :name="'quantities[' + selectedNozzle.id + ']'"
                                   :value="mode + ':' + (mode === 'LITRES' ? parseFloat(computedLitres()).toFixed(3) : parseFloat(computedAmount()).toFixed(2))">

                            {{-- Live Summary Highlight --}}
                            <div class="rounded-2xl bg-gradient-to-br from-vital-primary to-vital-darkred p-4 text-white shadow-glow">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-red-200">{{ __('sales.pos.calculated_litres') }}</div>
                                        <div class="font-mono text-2xl font-black" x-text="computedLitres() + ' Litres'"></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-[11px] font-semibold uppercase tracking-wider text-red-200">{{ __('sales.pos.total_payable') }}</div>
                                        <div class="tabular font-mono text-3xl font-black" x-text="'Rs. ' + computedNet().toLocaleString('en-PK', {minimumFractionDigits: 2})"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Right: Customer & Payment Checkout (5 cols) --}}
            <div class="space-y-4 lg:col-span-5">

                {{-- 3. Customer Picker: Walk-in vs Udhaar --}}
                <div class="glass-card p-4 sm:p-5">
                    <h2 class="mb-3 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.pos.step3_customer') }}</h2>

                    <div class="mb-3 flex rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                        <button type="button" @click="customerType = 'WALKIN'"
                                :class="customerType === 'WALKIN' ? 'bg-white font-bold text-slate-800 shadow-sm dark:bg-slate-900 dark:text-white' : 'text-slate-500'"
                                class="flex-1 rounded-md py-1.5 text-xs transition">
                            {{ __('sales.pos.walkin_customer') }}
                        </button>
                        <button type="button" @click="customerType = 'CREDIT'"
                                :class="customerType === 'CREDIT' ? 'bg-white font-bold text-slate-800 shadow-sm dark:bg-slate-900 dark:text-white' : 'text-slate-500'"
                                class="flex-1 rounded-md py-1.5 text-xs transition">
                            {{ __('sales.pos.credit_customer_tab') }}
                        </button>
                    </div>

                    {{-- Walk-in fields --}}
                    <div x-show="customerType === 'WALKIN'" class="space-y-2">
                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('sales.pos.customer_name_optional') }}</label>
                            <input type="text" name="customer_name" x-model="walkinName" placeholder="{{ __('sales.pos.placeholder_name') }}"
                                   class="input-3d">
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('sales.pos.mobile_whatsapp') }}</label>
                            <input type="text" name="customer_phone" x-model="walkinPhone" placeholder="03001234567"
                                   class="input-3d">
                        </div>
                    </div>

                    {{-- Credit (Udhaar) fields --}}
                    <div x-show="customerType === 'CREDIT'" class="space-y-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.pos.select_credit_customer') }}</label>
                            <select name="customer_id" x-model="selectedCustomerId" @change="onCustomerChange()" :disabled="customerType !== 'CREDIT'"
                                    class="input-3d">
                                <option value="">{{ __('sales.pos.choose_credit_customer') }}</option>
                                <template x-for="c in customers" :key="c.id">
                                    <option :value="c.id" x-text="c.name + ' (' + c.code + ')'"></option>
                                </template>
                            </select>
                        </div>

                        <template x-if="selectedCustomer">
                            <div class="rounded-2xl bg-amber-50 p-3 text-xs text-amber-900 shadow-inner dark:bg-amber-950/40 dark:text-amber-200">
                                <div class="flex justify-between">
                                    <span>{{ __('sales.pos.outstanding_balance') }}</span>
                                    <strong class="font-mono" x-text="'Rs. ' + selectedCustomer.balance.toFixed(2)"></strong>
                                </div>
                                <div class="flex justify-between">
                                    <span>{{ __('sales.pos.credit_limit') }}</span>
                                    <strong class="font-mono" x-text="selectedCustomer.is_unlimited ? 'Unlimited' : ('Rs. ' + selectedCustomer.limit.toFixed(2))"></strong>
                                </div>
                                <div class="flex justify-between border-t border-amber-200/60 pt-1 font-semibold">
                                    <span>{{ __('sales.pos.available_limit') }}</span>
                                    <strong class="font-mono" x-text="selectedCustomer.is_unlimited ? 'Unlimited' : ('Rs. ' + (selectedCustomer.limit - selectedCustomer.balance).toFixed(2))"></strong>
                                </div>
                            </div>
                        </template>

                        {{-- Vehicle Selector --}}
                        <template x-if="selectedCustomer && selectedCustomer.vehicles.length > 0">
                            <div>
                                <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('sales.pos.select_vehicle') }}</label>
                                <select name="vehicle_id" :disabled="customerType !== 'CREDIT'" class="input-3d">
                                    <option value="">{{ __('sales.pos.no_vehicle') }}</option>
                                    <template x-for="v in selectedCustomer.vehicles" :key="v.id">
                                        <option :value="v.id" x-text="v.reg"></option>
                                    </template>
                                </select>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- 4. Payment Modes & Split Payments --}}
                <div class="glass-card p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.pos.step4_payment') }}</h2>
                        <label class="inline-flex cursor-pointer items-center gap-1.5 text-xs text-slate-600 dark:text-slate-400">
                            <input type="checkbox" x-model="isSplitPayment" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                            <span>{{ __('sales.pos.split_payment') }}</span>
                        </label>
                    </div>

                    {{-- Single Payment Method Selector --}}
                    <div x-show="!isSplitPayment" class="space-y-1.5">
                        @foreach ($methods as $methodKey => $methodLabel)
                            <label class="flex cursor-pointer items-center justify-between rounded-xl border border-slate-200 p-2.5 text-xs shadow-3d transition hover:-translate-y-0.5 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800"
                                   :class="singleMethod === '{{ $methodKey }}' ? 'border-red-600 bg-red-50/50 font-bold dark:bg-red-950/20' : ''">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="single_method_radio" value="{{ $methodKey }}"
                                           x-model="singleMethod"
                                           class="text-red-600 focus:ring-red-500">
                                    <span>{{ $methodLabel }}</span>
                                </div>
                                <span class="text-slate-400">
                                    @if ($methodKey === 'CASH') 💵 @elseif ($methodKey === 'CARD') 💳 @elseif ($methodKey === 'JAZZCASH') 📱 @elseif ($methodKey === 'EASYPAISA') 🟢 @elseif ($methodKey === 'VITAL_CARD') ⛽ @elseif ($methodKey === 'CHEQUE') 📑 @elseif ($methodKey === 'CREDIT') 📝 @else 🏦 @endif
                                </span>
                            </label>
                        @endforeach

                        {{-- Hidden inputs for single payment.
                             :disabled is CRITICAL: disabled inputs are not submitted.
                             Baghair is ke split wale khaali inputs (jo DOM me baad me
                             aate hain) in ko overwrite karke har sale fail kar dete thay. --}}
                        <input type="hidden" name="payments[0][method]" :value="singleMethod" :disabled="isSplitPayment">
                        <input type="hidden" name="payments[0][amount]" :value="computedNet().toFixed(2)" :disabled="isSplitPayment">
                    </div>

                    {{-- Split Payments Dynamic List --}}
                    <div x-show="isSplitPayment" class="space-y-3">
                        <template x-for="(split, index) in splitPayments" :key="index">
                            <div class="flex items-center gap-2 rounded-lg border border-slate-200 p-2 text-xs dark:border-slate-700">
                                <select :name="'payments[' + index + '][method]'" x-model="split.method" :disabled="!isSplitPayment"
                                        class="rounded border-slate-300 py-1 text-xs dark:border-slate-700 dark:bg-slate-800">
                                    @foreach ($methods as $k => $l)
                                        <option value="{{ $k }}">{{ $l }}</option>
                                    @endforeach
                                </select>
                                <input type="number" step="0.01" min="0" :name="'payments[' + index + '][amount]'" x-model="split.amount" :disabled="!isSplitPayment"
                                       class="tabular w-28 rounded border-slate-300 py-1 text-right text-xs font-bold dark:border-slate-700 dark:bg-slate-800">
                                <button type="button" @click="removeSplit(index)" x-show="splitPayments.length > 1"
                                        class="rounded bg-red-50 p-1 text-red-600 hover:bg-red-100">✕</button>
                            </div>
                        </template>

                        <div class="flex items-center justify-between pt-1">
                            <button type="button" @click="addSplit()"
                                    class="text-xs font-bold text-red-600 hover:text-red-700">
                                + {{ __('sales.pos.add_split') }}
                            </button>
                            <span class="text-xs font-mono font-bold"
                                  :class="splitSum() === computedNet() ? 'text-emerald-600' : 'text-red-600'"
                                  x-text="'Sum: Rs. ' + splitSum().toFixed(2) + ' / ' + computedNet().toFixed(2)"></span>
                        </div>
                    </div>

                    {{-- Discount Input --}}
                    <div class="mt-4 border-t border-slate-200 pt-3 dark:border-slate-700">
                        <label class="block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('sales.pos.discount') }}</label>
                        <input type="number" step="0.01" min="0" name="discount" x-model="discountAmount"
                               class="input-3d text-center font-mono font-bold">
                    </div>

                    {{-- Complete Sale Button --}}
                    <button type="submit"
                            :disabled="!isValid()"
                            class="mt-4 flex min-h-[54px] w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-b from-emerald-500 to-emerald-700 px-6 py-3 text-base font-black text-white shadow-fab transition hover:-translate-y-0.5 hover:from-emerald-400 hover:to-emerald-600 active:translate-y-0 disabled:opacity-40">
                        <span>🧾</span>
                        <span>{{ __('sales.pos.complete_sale') }}</span>
                    </button>
                </div>

            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function posWizard(nozzles, customers) {
    return {
        nozzles,
        customers,
        selectedNozzle: null,
        mode: 'LITRES',
        inputValue: '',
        discountAmount: '0.00',
        customerType: 'WALKIN',
        walkinName: '',
        walkinPhone: '',
        selectedCustomerId: '',
        selectedCustomer: null,
        singleMethod: 'CASH',
        isSplitPayment: false,
        splitPayments: [
            { method: 'CASH', amount: '' },
            { method: 'JAZZCASH', amount: '' },
        ],

        init() {
            if (this.nozzles.length > 0) {
                this.selectNozzle(this.nozzles[0]);
            }
        },

        isPetrol(fuel) {
            const f = (fuel || '').toLowerCase();
            return f.includes('petrol') || f.includes('super') || f.includes('pmg');
        },
        isDiesel(fuel) {
            const f = (fuel || '').toLowerCase();
            return f.includes('diesel') || f.includes('hsd');
        },
        isHiOctane(fuel) {
            const f = (fuel || '').toLowerCase();
            return f.includes('octane') || f.includes('hobc');
        },

        selectNozzle(n) {
            this.selectedNozzle = n;
            this.inputValue = '10';
            this.syncSplits();
        },

        setMode(m) {
            this.mode = m;
            this.inputValue = m === 'LITRES' ? '10' : '1000';
            this.syncSplits();
        },

        pressKey(char) {
            if (char === '.' && this.inputValue.includes('.')) return;
            this.inputValue += char;
            this.syncSplits();
        },

        backspace() {
            this.inputValue = this.inputValue.slice(0, -1);
            this.syncSplits();
        },

        computedLitres() {
            const val = parseFloat(this.inputValue || 0);
            if (!this.selectedNozzle || val <= 0) return '0.000';
            if (this.mode === 'LITRES') return val.toFixed(3);
            return (val / this.selectedNozzle.rate).toFixed(3);
        },

        computedAmount() {
            const val = parseFloat(this.inputValue || 0);
            if (!this.selectedNozzle || val <= 0) return '0.00';
            if (this.mode === 'AMOUNT') return val.toFixed(2);
            return (val * this.selectedNozzle.rate).toFixed(2);
        },

        computedNet() {
            const amt = parseFloat(this.computedAmount() || 0);
            const disc = parseFloat(this.discountAmount || 0);
            const net = amt - disc;
            return net > 0 ? net : 0;
        },

        onCustomerChange() {
            const id = parseInt(this.selectedCustomerId);
            this.selectedCustomer = this.customers.find(c => c.id === id) || null;
            if (this.selectedCustomer) {
                this.singleMethod = 'CREDIT';
            }
        },

        addSplit() {
            this.splitPayments.push({ method: 'CASH', amount: '' });
        },

        removeSplit(idx) {
            this.splitPayments.splice(idx, 1);
        },

        splitSum() {
            return this.splitPayments.reduce((acc, p) => acc + (parseFloat(p.amount) || 0), 0);
        },

        syncSplits() {
            if (this.splitPayments.length > 0 && !this.isSplitPayment) {
                this.splitPayments[0].amount = this.computedNet().toFixed(2);
            }
        },

        isValid() {
            if (!this.selectedNozzle) return false;
            const net = this.computedNet();
            if (net <= 0) return false;
            if (this.customerType === 'CREDIT' && !this.selectedCustomerId) return false;
            if (this.isSplitPayment) {
                return Math.abs(this.splitSum() - net) < 0.01;
            }
            return true;
        },

        handleSubmit(event) {
            if (this.customerType === 'CREDIT' && !this.selectedCustomerId) {
                alert('Please select a customer for credit (udhaar) sale.');
                event.preventDefault();
                return;
            }
            if (this.isSplitPayment && Math.abs(this.splitSum() - this.computedNet()) >= 0.01) {
                alert('Split payments must match the total net amount exactly.');
                event.preventDefault();
                return;
            }
        }
    };
}
</script>
@endpush
