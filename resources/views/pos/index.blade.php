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
    ])), @js([
        'fbrDefault' => $fbrDefault,
        'searchUrl' => route('pos.customers.search'),
        'quickStoreUrl' => route('pos.customers.quick-store'),
        'branchId' => $branchId,
    ]))" class="space-y-4">

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

                    {{-- Credit (Udhaar) fields — customer autocomplete (W2):
                         naam likhne se mojooda customer auto-fetch, na mile to
                         wahin quick-add (email ke saath) --}}
                    <div x-show="customerType === 'CREDIT'" class="space-y-3">
                        <div class="relative" @click.outside="customerDropdown = false">
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.pos.select_credit_customer') }}</label>

                            {{-- Hidden customer_id — :disabled discipline (D-011):
                                 WALKIN mode me submit hi nahi hota --}}
                            <input type="hidden" name="customer_id" :value="selectedCustomerId" :disabled="customerType !== 'CREDIT'">

                            <div class="mt-1 flex items-center gap-2">
                                <input type="text" x-model="customerQuery" @input.debounce.300ms="onCustomerQueryInput()" @focus="searchCustomers()"
                                       placeholder="Customer ka naam / phone / code likhein…"
                                       autocomplete="off"
                                       class="input-3d">
                                <button type="button" x-show="selectedCustomer" @click="clearCustomer()"
                                        class="btn-3d btn-3d-ghost btn-3d-sm shrink-0">✕</button>
                            </div>

                            {{-- Search results dropdown --}}
                            <div x-show="customerDropdown" x-cloak
                                 class="absolute inset-x-0 top-full z-30 mt-1 max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-3d-lg dark:border-slate-700 dark:bg-slate-900">
                                <template x-for="c in customerResults" :key="c.id">
                                    <button type="button" @click="pickCustomer(c)"
                                            class="flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-xs transition hover:bg-red-50 dark:hover:bg-red-950/30">
                                        <span>
                                            <span class="block font-bold text-slate-800 dark:text-slate-100" x-text="c.name"></span>
                                            <span class="block text-[11px] text-slate-500" x-text="(c.code || '') + (c.phone ? ' · ' + c.phone : '') + (c.email ? ' · ' + c.email : '')"></span>
                                        </span>
                                        <span class="tabular shrink-0 font-mono font-bold text-amber-700 dark:text-amber-300" x-text="'Rs. ' + Number(c.balance || 0).toFixed(2)"></span>
                                    </button>
                                </template>
                                <div x-show="customerResults.length === 0" class="px-3 py-2 text-xs text-slate-400">
                                    Koi customer nahi mila.
                                </div>
                                <button type="button" @click="openQuickAdd()"
                                        class="block w-full border-t border-slate-200 px-3 py-2 text-left text-xs font-bold text-emerald-700 transition hover:bg-emerald-50 dark:border-slate-700 dark:text-emerald-300 dark:hover:bg-emerald-950/30">
                                    ➕ Add New Customer / نیا کسٹمر شامل کریں
                                </button>
                            </div>

                            {{-- Quick-add inline form --}}
                            <div x-show="showQuickAdd" x-cloak class="mt-2 space-y-2 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-900 dark:bg-emerald-950/20">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Name / نام *</label>
                                    <input type="text" x-model="qaName" class="input-3d" placeholder="Customer name">
                                </div>
                                <div class="grid grid-cols-2 gap-2">
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Phone / فون</label>
                                        <input type="text" x-model="qaPhone" class="input-3d" placeholder="03001234567">
                                    </div>
                                    <div>
                                        <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Vehicle No. / گاڑی نمبر</label>
                                        <input type="text" x-model="qaVehicle" class="input-3d" placeholder="LEA-1234">
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Email / ای میل (bill bhejne ke liye)</label>
                                    <input type="email" x-model="qaEmail" class="input-3d" placeholder="customer@example.com">
                                </div>
                                <p x-show="qaError" x-text="qaError" class="text-[11px] font-semibold text-red-600"></p>
                                <div class="flex gap-2">
                                    <button type="button" @click="saveQuickAdd()" :disabled="qaSaving"
                                            class="btn-3d btn-3d-success btn-3d-sm flex-1 disabled:opacity-40">✔ Save & Select Customer</button>
                                    <button type="button" @click="showQuickAdd = false"
                                            class="btn-3d btn-3d-ghost btn-3d-sm">Cancel</button>
                                </div>
                            </div>
                        </div>

                        {{-- Bill email note — customer ka email mojood ho to --}}
                        <template x-if="selectedCustomer && selectedCustomer.email">
                            <p class="rounded-xl bg-sky-50 px-3 py-2 text-[11px] font-semibold text-sky-800 dark:bg-sky-950/30 dark:text-sky-200">
                                📧 Bill is email par jayega: <span x-text="selectedCustomer.email"></span>
                            </p>
                        </template>
                        <template x-if="selectedCustomer && !selectedCustomer.email">
                            <p class="rounded-xl bg-slate-100 px-3 py-2 text-[11px] text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                ℹ Is customer ka email nahi hai — bill email nahi jayega (print ho jayega).
                            </p>
                        </template>

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

                {{-- 3b. Bill Options (W2): FBR ya Simple — per-bill select.
                     Default global tax.fbr_invoicing_enabled se preselect hota
                     hai; cashier har bill par badal sakta hai. --}}
                <div class="glass-card p-4 sm:p-5">
                    <h2 class="mb-3 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.bill.type') }}</h2>

                    <input type="hidden" name="bill_type" :value="billType">

                    <div class="flex rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
                        <button type="button" @click="billType = 'fbr'"
                                :class="billType === 'fbr' ? 'bg-emerald-600 font-bold text-white shadow-sm' : 'text-slate-500'"
                                class="flex-1 rounded-md py-2 text-xs transition">
                            🏛 {{ __('sales.bill.fbr') }}
                        </button>
                        <button type="button" @click="billType = 'simple'"
                                :class="billType === 'simple' ? 'bg-white font-bold text-slate-800 shadow-sm dark:bg-slate-900 dark:text-white' : 'text-slate-500'"
                                class="flex-1 rounded-md py-2 text-xs transition">
                            🧾 {{ __('sales.bill.simple') }}
                        </button>
                    </div>
                    <p class="mt-2 text-center text-[11px] text-slate-400" x-text="billType === 'fbr' ? 'FBR fiscal number + QR bill par print hoga.' : 'Saada bill — FBR fiscal record nahi banega.'"></p>

                    <label class="mt-3 flex cursor-pointer items-center gap-2 border-t border-slate-200 pt-3 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="email_bill" value="1" x-model="emailBill"
                               class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>📧 {{ __('sales.bill.email_bill') }}</span>
                    </label>
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
function posWizard(nozzles, customers, config) {
    return {
        nozzles,
        customers,
        config: config || {},
        selectedNozzle: null,
        mode: 'LITRES',
        inputValue: '',
        discountAmount: '0.00',
        customerType: 'WALKIN',
        walkinName: '',
        walkinPhone: '',
        selectedCustomerId: '',
        selectedCustomer: null,
        // Bill options (W2)
        billType: (config && config.fbrDefault) ? 'fbr' : 'simple',
        emailBill: true,
        // Customer autocomplete + quick-add (W2)
        customerQuery: '',
        customerResults: [],
        customerDropdown: false,
        showQuickAdd: false,
        qaName: '',
        qaPhone: '',
        qaEmail: '',
        qaVehicle: '',
        qaError: '',
        qaSaving: false,
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
            // Khata (customer) screen se deep-link: /pos?customer_id=ID aaye
            // to wahi customer preselect kar do (CREDIT tab + pickCustomer).
            try {
                const cid = new URLSearchParams(window.location.search).get('customer_id');
                if (cid) {
                    const found = (this.customers || []).find(c => String(c.id) === String(cid));
                    if (found) {
                        this.customerType = 'CREDIT';
                        this.pickCustomer(found);
                    }
                }
            } catch (e) { /* preselect optional hai — fail ho to wizard normal khule */ }
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

        normalizeCustomer(c) {
            return {
                id: c.id,
                name: c.name,
                code: c.code || '',
                phone: c.phone || '',
                email: c.email || '',
                balance: parseFloat(c.balance || 0),
                limit: parseFloat(c.credit_limit !== undefined ? c.credit_limit : (c.limit || 0)),
                is_unlimited: !!c.is_unlimited,
                vehicles: c.vehicles || [],
            };
        },

        onCustomerQueryInput() {
            // Query badal jaye to purani selection khatam — bill galat
            // customer par na jaye.
            if (this.selectedCustomer && (this.customerQuery || '').trim() !== (this.selectedCustomer.name || '').trim()) {
                this.selectedCustomer = null;
                this.selectedCustomerId = '';
            }
            this.searchCustomers();
        },

        searchCustomers() {
            const q = (this.customerQuery || '').trim();
            if (q.length < 1 || !this.config.searchUrl) {
                this.customerResults = [];
                this.customerDropdown = false;
                return;
            }
            const url = this.config.searchUrl + '?q=' + encodeURIComponent(q) + '&branch_id=' + encodeURIComponent(this.config.branchId || '');
            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(r => r.json())
                .then(list => {
                    this.customerResults = Array.isArray(list) ? list : [];
                    this.customerDropdown = true;
                })
                .catch(() => { this.customerResults = []; this.customerDropdown = false; });
        },

        pickCustomer(c) {
            const normalized = this.normalizeCustomer(c);
            this.selectedCustomer = normalized;
            this.selectedCustomerId = normalized.id;
            this.customerQuery = normalized.name;
            this.customerDropdown = false;
            this.showQuickAdd = false;
            // Mojooda rawaiya barqarar: credit customer chunte hi
            // single payment method CREDIT ho jata hai.
            this.singleMethod = 'CREDIT';
        },

        clearCustomer() {
            this.selectedCustomer = null;
            this.selectedCustomerId = '';
            this.customerQuery = '';
            this.customerResults = [];
            this.showQuickAdd = false;
        },

        openQuickAdd() {
            this.qaName = (this.customerQuery || '').trim();
            this.qaPhone = '';
            this.qaEmail = '';
            this.qaVehicle = '';
            this.qaError = '';
            this.customerDropdown = false;
            this.showQuickAdd = true;
        },

        saveQuickAdd() {
            if (!this.qaName.trim()) {
                this.qaError = 'Customer ka naam lazmi hai.';
                return;
            }
            this.qaSaving = true;
            this.qaError = '';
            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';
            fetch(this.config.quickStoreUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': token,
                },
                body: JSON.stringify({
                    name: this.qaName.trim(),
                    phone: this.qaPhone.trim() || null,
                    email: this.qaEmail.trim() || null,
                    vehicle_no: this.qaVehicle.trim() || null,
                    branch_id: this.config.branchId || null,
                }),
            })
                .then(async r => {
                    const data = await r.json().catch(() => null);
                    if (!r.ok) {
                        const firstError = data && data.errors ? Object.values(data.errors)[0][0] : null;
                        throw new Error(firstError || 'Customer save nahi ho saka.');
                    }
                    return data;
                })
                .then(customer => {
                    this.customers.push(this.normalizeCustomer(customer));
                    this.pickCustomer(customer);
                })
                .catch(err => { this.qaError = err.message || 'Customer save nahi ho saka.'; })
                .finally(() => { this.qaSaving = false; });
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
