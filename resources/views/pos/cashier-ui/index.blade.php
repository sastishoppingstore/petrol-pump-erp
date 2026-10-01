@extends('layouts.app')

@section('title', {{ __('sales.cashier.title') }})
@section('breadcrumb')
    <li class="text-slate-500">{{ __('sales.cashier.breadcrumb_forecourt') }}</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.cashier.breadcrumb_cashier') }}</li>
@endsection

@section('content')
<div class="space-y-4">

    {{-- ===== Header: shift info ===== --}}
    <div class="glass-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-red-600 to-red-800 text-2xl text-white shadow-glow">⛽</span>
            <div>
                <h1 class="text-lg font-black text-slate-800 dark:text-white">{{ __('sales.cashier.heading') }}</h1>
                <p class="text-xs text-slate-500">
                    {{ __('sales.cashier.shift') }} <strong class="text-slate-800 dark:text-slate-200">{{ $dashboardData['shift_info']['shift_number'] ?? $shift->shift_number }}</strong>
                    · {{ __('sales.cashier.opened') }}: <strong>{{ $dashboardData['shift_info']['opened_at'] ?? '—' }}</strong>
                    · {{ __('sales.cashier.cashier') }}: <strong>{{ $dashboardData['shift_info']['employee'] ?? '—' }}</strong>
                    · {{ __('sales.cashier.opening_cash') }}: <strong>{{ \App\Support\PakistaniCurrency::format($dashboardData['shift_info']['opening_cash'] ?? $shift->opening_cash) }}</strong>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm"><span>🧾</span> {{ __('sales.cashier.full_pos_wizard') }}</a>
            <a href="{{ route('sales.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm"><span>📜</span> {{ __('sales.cashier.sales_history') }}</a>
        </div>
    </div>

    {{-- ===== Errors (sale fail ho to wajah yahan saaf dikhegi) ===== --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 shadow-3d dark:border-red-800 dark:bg-red-950/40 dark:text-red-200" role="alert">
            <strong>⚠ {{ __('sales.cashier.sale_failed') }}:</strong>
            <ul class="mt-1 list-disc ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ===== 4 massive tiles (CashierUiService::getDashboardData) ===== --}}
    <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
        @php $tiles = $dashboardData['tiles'] ?? []; @endphp

        <div class="stat-tile-3d tilt-3d p-4">
            <div class="text-[11px] font-black uppercase tracking-widest text-slate-500">💰 {{ $tiles['sales_today']['label'] ?? 'Sales Today' }}</div>
            <div class="tabular mt-1 font-mono text-2xl font-black text-slate-800 dark:text-white">Rs. {{ number_format((float) ($tiles['sales_today']['value'] ?? 0), 2) }}</div>
            @if (! empty($tiles['sales_today']['trend']))
                <div class="mt-1 text-[11px] font-bold {{ ($tiles['sales_today']['trend']['direction'] ?? '') === 'down' ? 'text-red-500' : 'text-emerald-500' }}">
                    {{ ($tiles['sales_today']['trend']['direction'] ?? '') === 'down' ? '▼' : (($tiles['sales_today']['trend']['direction'] ?? '') === 'up' ? '▲' : '•') }}
                    {{ $tiles['sales_today']['trend']['percent'] ?? 0 }}% {{ __('sales.cashier.vs_yesterday') }}
                </div>
            @endif
        </div>

        <div class="stat-tile-3d tilt-3d p-4">
            <div class="text-[11px] font-black uppercase tracking-widest text-slate-500">⛽ {{ $tiles['fuel_sold']['label'] ?? 'Fuel Sold' }}</div>
            <div class="tabular mt-1 font-mono text-2xl font-black text-slate-800 dark:text-white">{{ number_format((float) ($tiles['fuel_sold']['value'] ?? 0), 3) }} L</div>
            @if (! empty($tiles['fuel_sold']['trend']))
                <div class="mt-1 text-[11px] font-bold {{ ($tiles['fuel_sold']['trend']['direction'] ?? '') === 'down' ? 'text-red-500' : 'text-emerald-500' }}">
                    {{ ($tiles['fuel_sold']['trend']['direction'] ?? '') === 'down' ? '▼' : (($tiles['fuel_sold']['trend']['direction'] ?? '') === 'up' ? '▲' : '•') }}
                    {{ $tiles['fuel_sold']['trend']['percent'] ?? 0 }}% {{ __('sales.cashier.vs_yesterday') }}
                </div>
            @endif
        </div>

        <div class="stat-tile-3d tilt-3d p-4">
            <div class="text-[11px] font-black uppercase tracking-widest text-slate-500">🧾 {{ $tiles['collections']['label'] ?? 'Collections' }}</div>
            <div class="tabular mt-1 font-mono text-2xl font-black text-slate-800 dark:text-white">Rs. {{ number_format((float) ($tiles['collections']['value'] ?? 0), 2) }}</div>
            <div class="mt-1 space-y-0.5 text-[11px] text-slate-500">
                @foreach (($tiles['collections']['breakdown'] ?? []) as $m => $amt)
                    @if ((float) $amt > 0)
                        <div class="flex justify-between"><span>{{ \App\Models\SalePayment::methods()[$m] ?? $m }}</span><span class="tabular font-bold">{{ number_format((float) $amt, 0) }}</span></div>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="stat-tile-3d tilt-3d p-4">
            <div class="text-[11px] font-black uppercase tracking-widest text-slate-500">📈 {{ $tiles['margin']['label'] ?? 'Gross Margin' }}</div>
            <div class="tabular mt-1 font-mono text-2xl font-black text-slate-800 dark:text-white">Rs. {{ number_format((float) ($tiles['margin']['value'] ?? 0), 2) }}</div>
            <div class="mt-1 text-[11px] font-bold text-slate-500">{{ number_format((float) ($tiles['margin']['percent'] ?? 0), 2) }}% {{ __('sales.cashier.of_sales') }}</div>
        </div>
    </div>

    {{-- ===== Sale form — mojooda pos.store endpoint par submit hota hai ===== --}}
    <form id="cashierForm" method="POST" action="{{ route('pos.store') }}">
        @csrf
        <input type="hidden" name="request_token" value="{{ app(\App\Services\Sale\SaleService::class)->newRequestToken() }}">
        <input type="hidden" name="branch_id" value="{{ $branchId }}">
        <input type="hidden" name="shift_id" value="{{ $shift->id }}">
        <input type="hidden" id="qtyField" value="">
        <input type="hidden" name="payments[0][method]" id="payMethod" value="CASH">
        <input type="hidden" name="payments[0][amount]" id="payAmount" value="0.00">

        <div class="grid gap-4 lg:grid-cols-12">

            {{-- ===== Left: Nozzles + Keypad ===== --}}
            <div class="space-y-4 lg:col-span-7">

                <div class="glass-card p-4 sm:p-5">
                    <h2 class="mb-3 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.cashier.step1_nozzle') }}</h2>

                    @if (count($nozzleStatus) === 0)
                        <div class="p-6 text-center text-sm text-slate-400">
                            {{ __('sales.cashier.no_nozzles') }}
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($nozzleStatus as $nz)
                            <button type="button"
                                    class="nozzle-btn relative rounded-2xl border-2 border-slate-200 bg-white/60 p-3 text-left shadow-3d transition hover:-translate-y-0.5 active:translate-y-0 dark:border-slate-700 dark:bg-slate-800/60"
                                    data-id="{{ $nz['id'] }}"
                                    data-rate="{{ $nz['rate'] }}"
                                    data-fuel="{{ $nz['fuel'] }}">
                                <div class="flex items-center justify-between">
                                    <span class="text-[11px] font-extrabold uppercase text-slate-500">
                                        @if (! empty($nz['dispenser_number'])) D-{{ $nz['dispenser_number'] }} · @endif N-{{ $nz['nozzle_number'] }}
                                    </span>
                                    <span class="text-lg">⛽</span>
                                </div>
                                <div class="mt-0.5 text-sm font-black text-slate-800 dark:text-white">{{ $nz['fuel'] }}</div>
                                <div class="tabular text-xs font-bold text-slate-600 dark:text-slate-300">Rs. {{ number_format((float) $nz['rate'], 2) }}/L</div>
                                <div class="tabular mt-1 text-[11px] text-slate-500">{{ __('sales.cashier.meter') }}: {{ number_format((float) $nz['current_meter'], 3) }}</div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                    <div class="h-full rounded-full {{ ! empty($nz['low_stock']) ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min(100, max(0, (float) $nz['stock_percent'])) }}%"></div>
                                </div>
                                <div class="mt-1 flex justify-between text-[10px] {{ ! empty($nz['low_stock']) ? 'font-bold text-red-500' : 'text-slate-400' }}">
                                    <span>{{ __('sales.cashier.tank') }}: {{ number_format((float) $nz['tank_stock'], 0) }} L</span>
                                    <span>{{ $nz['stock_percent'] }}%</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.cashier.step2_quantity') }}</h2>
                        <div class="flex rounded-lg bg-slate-100 p-0.5 dark:bg-slate-800">
                            <button type="button" data-mode="LITRES" class="mode-btn rounded-md bg-red-600 px-3 py-1 text-xs font-bold text-white shadow-sm transition">{{ __('sales.cashier.litres') }}</button>
                            <button type="button" data-mode="AMOUNT" class="mode-btn rounded-md px-3 py-1 text-xs text-slate-600 transition dark:text-slate-300">{{ __('sales.cashier.amount') }}</button>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300" id="inputLabel">{{ __('sales.cashier.litres_input') }}</label>
                            <div class="flex items-center rounded-lg border-2 border-slate-300 bg-slate-900 px-3 py-3 font-mono text-2xl font-bold text-emerald-400 dark:border-slate-700">
                                <span id="displayValue" class="w-full text-right tracking-wider">0</span>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.cashier.rate') }}</label>
                            <div class="flex items-center rounded-lg border border-slate-300 bg-slate-100 px-3 py-3 font-mono text-2xl font-bold text-slate-800 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                <span id="displayRate" class="w-full text-right">Rs. 0.00</span>
                            </div>
                        </div>
                    </div>

                    {{-- Keypad — CashierUiService::getKeypadConfig se render hota hai --}}
                    <div class="mt-3 grid grid-cols-4 gap-1.5">
                        @foreach (($keypadConfig['numbers'] ?? []) as $num)
                            <button type="button" data-key="{{ $num['value'] }}"
                                    class="flex h-14 items-center justify-center rounded-xl border border-slate-200 bg-white text-xl font-black text-slate-800 shadow-3d transition hover:-translate-y-0.5 hover:bg-slate-50 active:translate-y-0 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                                {{ $num['label'] }}
                            </button>
                        @endforeach
                        @foreach (($keypadConfig['operations'] ?? []) as $op)
                            <button type="button" data-action="{{ $op['action'] }}"
                                    class="flex h-14 items-center justify-center rounded-xl text-xl font-black text-white shadow-3d transition hover:-translate-y-0.5 active:translate-y-0"
                                    style="background: {{ $op['color'] }}">
                                {{ $op['label'] }}
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-3 rounded-2xl bg-gradient-to-br from-red-600 to-red-800 p-4 text-white shadow-glow">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-[11px] font-semibold uppercase tracking-wider text-red-200">{{ __('sales.cashier.litres') }}</div>
                                <div class="font-mono text-2xl font-black" id="displayLitres">0.000 L</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] font-semibold uppercase tracking-wider text-red-200">{{ __('sales.cashier.total_bill') }}</div>
                                <div class="tabular font-mono text-3xl font-black" id="displayTotal">Rs. 0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Right: Payment + Banknotes + Submit ===== --}}
            <div class="space-y-4 lg:col-span-5">

                <div class="glass-card p-4 sm:p-5" id="paymentPanel">
                    <h2 class="mb-3 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.cashier.step3_payment') }}</h2>

                    <div class="grid grid-cols-2 gap-2">
                        @foreach ($paymentMethods as $pm)
                            <button type="button"
                                    class="method-btn flex min-h-[56px] items-center gap-2 rounded-xl border-2 border-slate-200 p-2.5 text-left text-xs font-bold text-slate-700 shadow-3d transition hover:-translate-y-0.5 active:translate-y-0 dark:border-slate-700 dark:text-slate-200"
                                    data-method="{{ $pm['method'] }}"
                                    style="--pm-color: {{ $pm['color'] }}">
                                <span class="text-xl">{{ $pm['icon'] }}</span>
                                <span>{{ $pm['label'] }}</span>
                            </button>
                        @endforeach
                    </div>

                    {{-- Credit customer — sirf CREDIT method par lazmi.
                         Autocomplete (W2): naam likhne se customer auto-fetch,
                         na mile to wahin quick-add (email ke saath). --}}
                    <div id="customerWrap" class="mt-3 hidden">
                        <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.cashier.credit_customer') }} *</label>

                        {{-- Asal submit hone wala customer_id — JS selection par
                             enable hota hai, warna disabled (submit nahi hota) --}}
                        <input type="hidden" name="customer_id" id="customerId" value="" disabled>

                        <div class="relative">
                            <input type="text" id="customerSearch" autocomplete="off"
                                   placeholder="Customer ka naam / phone / code likhein…"
                                   class="input-3d">
                            <div id="customerResults" class="absolute inset-x-0 top-full z-30 mt-1 hidden max-h-64 overflow-auto rounded-xl border border-slate-200 bg-white shadow-3d-lg dark:border-slate-700 dark:bg-slate-900"></div>
                        </div>

                        <div id="customerInfo" class="mt-2 hidden rounded-xl bg-amber-50 px-3 py-2 text-[11px] font-semibold text-amber-900 dark:bg-amber-950/40 dark:text-amber-200">
                            <div class="flex items-center justify-between gap-2">
                                <span id="customerInfoText"></span>
                                <button type="button" id="customerClear" class="shrink-0 font-black text-red-600">✕</button>
                            </div>
                            <div id="customerEmailNote" class="mt-0.5 font-normal"></div>
                        </div>

                        {{-- Quick-add inline form — inputs par name NAHI hai,
                             ye kabhi form ke saath submit nahi hote (JS POST) --}}
                        <div id="quickAddWrap" class="mt-2 hidden space-y-2 rounded-xl border border-emerald-200 bg-emerald-50/60 p-3 dark:border-emerald-900 dark:bg-emerald-950/20">
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Name / نام *</label>
                                <input type="text" id="qaName" class="input-3d" placeholder="Customer name">
                            </div>
                            <div class="grid grid-cols-2 gap-2">
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Phone / فون</label>
                                    <input type="text" id="qaPhone" class="input-3d" placeholder="03001234567">
                                </div>
                                <div>
                                    <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Vehicle No. / گاڑی نمبر</label>
                                    <input type="text" id="qaVehicle" class="input-3d" placeholder="LEA-1234">
                                </div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-semibold text-slate-600 dark:text-slate-300">Email / ای میل (bill bhejne ke liye)</label>
                                <input type="email" id="qaEmail" class="input-3d" placeholder="customer@example.com">
                            </div>
                            <p id="qaError" class="hidden text-[11px] font-semibold text-red-600"></p>
                            <div class="flex gap-2">
                                <button type="button" id="qaSave" class="btn-3d btn-3d-success btn-3d-sm flex-1">✔ Save & Select</button>
                                <button type="button" id="qaCancel" class="btn-3d btn-3d-ghost btn-3d-sm">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('sales.cashier.customer_name_optional') }}</label>
                        <input type="text" name="customer_name" maxlength="150" placeholder="{{ __('sales.cashier.placeholder_name') }}" class="input-3d">
                    </div>
                    <div class="mt-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">{{ __('sales.cashier.mobile_whatsapp') }}</label>
                        <input type="text" name="customer_phone" maxlength="30" placeholder="03001234567" class="input-3d">
                    </div>
                </div>

                {{-- Bill Options (W2): FBR ya Simple — per-bill select.
                     Default global tax.fbr_invoicing_enabled se preselect. --}}
                @php($fbrDefault = (float) (app(\App\Services\System\SettingService::class)->get('fbr_invoicing_enabled') ?? '0') > 0)
                <div class="glass-card p-4 sm:p-5">
                    <h2 class="mb-3 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.bill.type') }}</h2>

                    <input type="hidden" name="bill_type" id="billTypeField" value="{{ $fbrDefault ? 'fbr' : 'simple' }}">

                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" id="billTypeFbr"
                                class="flex min-h-[48px] items-center justify-center gap-2 rounded-xl border-2 border-slate-200 p-2.5 text-xs font-bold text-slate-700 shadow-3d transition active:translate-y-0 dark:border-slate-700 dark:text-slate-200">
                            🏛 {{ __('sales.bill.fbr') }}
                        </button>
                        <button type="button" id="billTypeSimple"
                                class="flex min-h-[48px] items-center justify-center gap-2 rounded-xl border-2 border-slate-200 p-2.5 text-xs font-bold text-slate-700 shadow-3d transition active:translate-y-0 dark:border-slate-700 dark:text-slate-200">
                            🧾 {{ __('sales.bill.simple') }}
                        </button>
                    </div>
                    <p id="billTypeNote" class="mt-2 text-center text-[11px] text-slate-400"></p>

                    <label class="mt-3 flex cursor-pointer items-center gap-2 border-t border-slate-200 pt-3 text-xs font-semibold text-slate-700 dark:border-slate-700 dark:text-slate-200">
                        <input type="checkbox" name="email_bill" value="1" id="emailBill" checked
                               class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                        <span>📧 {{ __('sales.bill.email_bill') }}</span>
                    </label>
                </div>

                {{-- Banknote counting helper — CashierUiService::getBanknoteDenominations --}}
                <div class="glass-card p-4 sm:p-5">
                    <h2 class="mb-1 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">{{ __('sales.cashier.step4_counting') }}</h2>
                    <p class="mb-3 text-center text-[11px] text-slate-400">{{ __('sales.cashier.counting_help') }}</p>

                    <div class="space-y-1.5">
                        @foreach ($banknoteDenominations as $denom)
                            <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 px-2.5 py-1.5 dark:border-slate-700">
                                <span class="text-sm font-black" style="color: {{ $denom['color'] }}">{{ $denom['icon'] }} Rs. {{ number_format($denom['denomination']) }}</span>
                                <div class="flex items-center gap-1.5">
                                    <button type="button" class="note-btn flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-lg font-black text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200" data-denom="{{ $denom['denomination'] }}" data-delta="-1">−</button>
                                    <span class="tabular w-10 text-center font-mono text-sm font-bold note-count" data-denom="{{ $denom['denomination'] }}">0</span>
                                    <button type="button" class="note-btn flex h-9 w-9 items-center justify-center rounded-lg bg-slate-100 text-lg font-black text-slate-700 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200" data-denom="{{ $denom['denomination'] }}" data-delta="1">+</button>
                                </div>
                                <span class="tabular w-24 text-right font-mono text-sm font-bold text-slate-700 dark:text-slate-200 note-line" data-denom="{{ $denom['denomination'] }}">Rs. 0</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="mt-3 flex items-center justify-between border-t border-slate-200 pt-2 text-sm dark:border-slate-700">
                        <span class="font-semibold text-slate-600 dark:text-slate-300">{{ __('sales.cashier.counted_notes') }}</span>
                        <span class="tabular font-mono text-lg font-black" id="notesTotal">Rs. 0</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-600 dark:text-slate-300">{{ __('sales.cashier.difference_notes') }}</span>
                        <span class="tabular font-mono text-lg font-black" id="notesDiff">Rs. 0.00</span>
                    </div>
                </div>

                <button type="submit" id="submitBtn"
                        class="flex min-h-[64px] w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-b from-emerald-500 to-emerald-700 px-6 py-3 text-lg font-black text-white shadow-fab transition hover:-translate-y-0.5 hover:from-emerald-400 hover:to-emerald-600 active:translate-y-0 disabled:opacity-40">
                    <span>🧾</span>
                    <span>{{ __('sales.cashier.complete_sale') }}</span>
                </button>
                <a href="{{ route('cashier.index') }}" class="btn-3d btn-3d-ghost w-full text-center">↺ {{ __('sales.cashier.new_bill') }}</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var NOZZLES = @js($nozzleStatus);
    var state = { nozzleId: null, rate: 0, mode: 'LITRES', buffer: '', method: 'CASH', customerId: null, billType: 'simple' };
    var noteCounts = {};

    var $ = function (id) { return document.getElementById(id); };
    var fmt = function (n, d) { return Number(n || 0).toLocaleString('en-PK', { minimumFractionDigits: d, maximumFractionDigits: d }); };

    function selectedNozzle() {
        for (var i = 0; i < NOZZLES.length; i++) { if (NOZZLES[i].id === state.nozzleId) return NOZZLES[i]; }
        return null;
    }

    function computed() {
        var val = parseFloat(state.buffer || '0') || 0;
        if (!state.nozzleId || state.rate <= 0 || val <= 0) return { litres: 0, amount: 0 };
        if (state.mode === 'LITRES') return { litres: val, amount: Math.round(val * state.rate * 100) / 100 };
        return { litres: Math.round((val / state.rate) * 1000) / 1000, amount: val };
    }

    function render() {
        var c = computed();
        $('displayValue').textContent = state.buffer === '' ? '0' : state.buffer;
        $('displayRate').textContent = 'Rs. ' + fmt(state.rate, 2);
        $('displayLitres').textContent = fmt(c.litres, 3) + ' L';
        $('displayTotal').textContent = 'Rs. ' + fmt(c.amount, 2);
        $('inputLabel').textContent = state.mode === 'LITRES' ? 'Litres Likhen:' : 'Amount (Rs.) Likhen:';

        var qty = $('qtyField');
        if (state.nozzleId && c.amount > 0) {
            qty.name = 'quantities[' + state.nozzleId + ']';
            qty.value = state.mode + ':' + (state.mode === 'LITRES' ? c.litres.toFixed(3) : c.amount.toFixed(2));
        } else {
            qty.name = '';
            qty.value = '';
        }
        $('payAmount').value = c.amount.toFixed(2);
        $('submitBtn').disabled = !(state.nozzleId && c.amount > 0);
        renderNotes();
    }

    function renderNotes() {
        var total = 0;
        document.querySelectorAll('.note-count').forEach(function (el) {
            var d = el.getAttribute('data-denom');
            var count = noteCounts[d] || 0;
            el.textContent = count;
            total += count * parseInt(d, 10);
        });
        document.querySelectorAll('.note-line').forEach(function (el) {
            var d = el.getAttribute('data-denom');
            el.textContent = 'Rs. ' + fmt((noteCounts[d] || 0) * parseInt(d, 10), 0);
        });
        $('notesTotal').textContent = 'Rs. ' + fmt(total, 0);
        var diff = total - computed().amount;
        var diffEl = $('notesDiff');
        diffEl.textContent = 'Rs. ' + fmt(diff, 2);
        diffEl.style.color = Math.abs(diff) < 0.01 ? '#059669' : '#dc2626';
    }

    // Nozzle selection
    document.querySelectorAll('.nozzle-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.nozzle-btn').forEach(function (b) { b.style.outline = ''; b.style.borderColor = ''; });
            btn.style.borderColor = '#dc2626';
            btn.style.outline = '2px solid rgba(220,38,38,.35)';
            state.nozzleId = parseInt(btn.getAttribute('data-id'), 10);
            state.rate = parseFloat(btn.getAttribute('data-rate')) || 0;
            if (state.buffer === '') state.buffer = state.mode === 'LITRES' ? '10' : '1000';
            render();
        });
    });

    // Mode toggle
    document.querySelectorAll('.mode-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            state.mode = btn.getAttribute('data-mode');
            document.querySelectorAll('.mode-btn').forEach(function (b) {
                b.classList.remove('bg-red-600', 'text-white', 'font-bold', 'shadow-sm');
                b.classList.add('text-slate-600');
            });
            btn.classList.add('bg-red-600', 'text-white', 'font-bold', 'shadow-sm');
            btn.classList.remove('text-slate-600');
            state.buffer = state.mode === 'LITRES' ? '10' : '1000';
            render();
        });
    });

    // Keypad
    document.querySelectorAll('[data-key]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var k = btn.getAttribute('data-key');
            if (k === '.' && state.buffer.indexOf('.') !== -1) return;
            if (state.buffer.replace('.', '').length >= 10) return;
            state.buffer += k;
            render();
        });
    });
    document.querySelectorAll('[data-action]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var a = btn.getAttribute('data-action');
            if (a === 'clear' || a === 'cancel') state.buffer = '';
            else if (a === 'backspace') state.buffer = state.buffer.slice(0, -1);
            else if (a === 'confirm') { $('paymentPanel').scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
            render();
        });
    });

    // Payment methods
    function markMethod() {
        document.querySelectorAll('.method-btn').forEach(function (b) {
            var active = b.getAttribute('data-method') === state.method;
            b.style.borderColor = active ? (b.style.getPropertyValue('--pm-color') || '#dc2626') : '';
            b.style.background = active ? 'rgba(220,38,38,.06)' : '';
        });
        var isCredit = state.method === 'CREDIT';
        $('customerWrap').classList.toggle('hidden', !isCredit);
        // customer_id sirf tab submit ho jab CREDIT ho AUR customer chuna gaya ho
        $('customerId').disabled = !(isCredit && state.customerId);
    }
    document.querySelectorAll('.method-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            state.method = btn.getAttribute('data-method');
            $('payMethod').value = state.method;
            markMethod();
        });
    });

    // Banknotes
    document.querySelectorAll('.note-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var d = btn.getAttribute('data-denom');
            var next = (noteCounts[d] || 0) + parseInt(btn.getAttribute('data-delta'), 10);
            noteCounts[d] = Math.max(0, next);
            renderNotes();
        });
    });

    // Submit guard — server dobara validate karta hai, ye sirf fori feedback hai
    $('cashierForm').addEventListener('submit', function (e) {
        var c = computed();
        if (!state.nozzleId) { e.preventDefault(); alert('Pehle nozzle chunein.'); return; }
        if (c.amount <= 0) { e.preventDefault(); alert('Miqdaar (litres/amount) likhen.'); return; }
        if (state.method === 'CREDIT' && !$('customerId').value) {
            e.preventDefault(); alert('Udhaar sale ke liye customer lazmi chunein.'); return;
        }
        $('submitBtn').disabled = true;
        $('submitBtn').style.opacity = '.55';
    });

    // ===== Bill Type toggle (W2) =====
    var SEARCH_URL = @js(route('pos.customers.search'));
    var QUICK_STORE_URL = @js(route('pos.customers.quick-store'));
    var BRANCH_ID = @js($branchId);

    function markBillType() {
        var isFbr = state.billType === 'fbr';
        var fbrBtn = $('billTypeFbr'), simpleBtn = $('billTypeSimple');
        fbrBtn.style.borderColor = isFbr ? '#059669' : '';
        fbrBtn.style.background = isFbr ? 'rgba(5,150,105,.08)' : '';
        simpleBtn.style.borderColor = !isFbr ? '#334155' : '';
        simpleBtn.style.background = !isFbr ? 'rgba(51,65,85,.07)' : '';
        $('billTypeNote').textContent = isFbr
            ? 'FBR fiscal number + QR bill par print hoga.'
            : 'Saada bill — FBR fiscal record nahi banega.';
    }
    function setBillType(t) {
        state.billType = t;
        $('billTypeField').value = t;
        markBillType();
    }
    $('billTypeFbr').addEventListener('click', function () { setBillType('fbr'); });
    $('billTypeSimple').addEventListener('click', function () { setBillType('simple'); });
    state.billType = $('billTypeField').value === 'fbr' ? 'fbr' : 'simple';
    markBillType();

    // ===== Customer autocomplete + quick-add (W2) =====
    var searchTimer = null;

    function hideResults() { $('customerResults').classList.add('hidden'); }

    function pickCustomer(c) {
        state.customerId = c.id;
        var hidden = $('customerId');
        hidden.value = c.id;
        hidden.disabled = state.method !== 'CREDIT';
        $('customerSearch').value = c.name;
        hideResults();
        $('quickAddWrap').classList.add('hidden');
        $('customerInfoText').textContent = '👤 ' + c.name + (c.code ? ' (' + c.code + ')' : '') + ' · Balance Rs. ' + fmt(c.balance || 0, 2);
        $('customerEmailNote').textContent = c.email
            ? '📧 Bill is email par jayega: ' + c.email
            : 'ℹ Is customer ka email nahi hai — bill email nahi jayega.';
        $('customerInfo').classList.remove('hidden');
    }

    function clearCustomer() {
        state.customerId = null;
        var hidden = $('customerId');
        hidden.value = '';
        hidden.disabled = true;
        $('customerSearch').value = '';
        $('customerInfo').classList.add('hidden');
        hideResults();
    }
    $('customerClear').addEventListener('click', clearCustomer);

    function renderResults(list, query) {
        var box = $('customerResults');
        box.innerHTML = '';
        list.forEach(function (c) {
            var row = document.createElement('button');
            row.type = 'button';
            row.className = 'flex w-full items-center justify-between gap-2 px-3 py-2 text-left text-xs transition hover:bg-red-50 dark:hover:bg-red-950/30';
            var left = document.createElement('span');
            var nameEl = document.createElement('span');
            nameEl.className = 'block font-bold text-slate-800 dark:text-slate-100';
            nameEl.textContent = c.name;
            var subEl = document.createElement('span');
            subEl.className = 'block text-[11px] text-slate-500';
            subEl.textContent = (c.code || '') + (c.phone ? ' · ' + c.phone : '') + (c.email ? ' · ' + c.email : '');
            left.appendChild(nameEl);
            left.appendChild(subEl);
            var bal = document.createElement('span');
            bal.className = 'tabular shrink-0 font-mono font-bold text-amber-700 dark:text-amber-300';
            bal.textContent = 'Rs. ' + fmt(c.balance || 0, 2);
            row.appendChild(left);
            row.appendChild(bal);
            row.addEventListener('click', function () { pickCustomer(c); });
            box.appendChild(row);
        });
        if (list.length === 0) {
            var empty = document.createElement('div');
            empty.className = 'px-3 py-2 text-xs text-slate-400';
            empty.textContent = 'Koi customer nahi mila.';
            box.appendChild(empty);
        }
        var addBtn = document.createElement('button');
        addBtn.type = 'button';
        addBtn.className = 'block w-full border-t border-slate-200 px-3 py-2 text-left text-xs font-bold text-emerald-700 transition hover:bg-emerald-50 dark:border-slate-700 dark:text-emerald-300 dark:hover:bg-emerald-950/30';
        addBtn.textContent = '➕ Add New Customer / نیا کسٹمر شامل کریں';
        addBtn.addEventListener('click', function () {
            $('qaName').value = query || '';
            $('qaPhone').value = '';
            $('qaEmail').value = '';
            $('qaVehicle').value = '';
            $('qaError').classList.add('hidden');
            hideResults();
            $('quickAddWrap').classList.remove('hidden');
        });
        box.appendChild(addBtn);
        box.classList.remove('hidden');
    }

    function runSearch() {
        var q = $('customerSearch').value.trim();
        if (q.length < 1) { hideResults(); return; }
        fetch(SEARCH_URL + '?q=' + encodeURIComponent(q) + '&branch_id=' + encodeURIComponent(BRANCH_ID), {
            headers: { 'Accept': 'application/json' }
        })
            .then(function (r) { return r.json(); })
            .then(function (list) { renderResults(Array.isArray(list) ? list : [], q); })
            .catch(function () { hideResults(); });
    }
    $('customerSearch').addEventListener('input', function () {
        // Nayi typing par purani selection khatam — galat customer par bill
        // na jaye. Input ki value ko haath NAHI lagate (user likh raha hai).
        if (state.customerId) {
            state.customerId = null;
            var hidden = $('customerId');
            hidden.value = '';
            hidden.disabled = true;
            $('customerInfo').classList.add('hidden');
        }
        if (searchTimer) clearTimeout(searchTimer);
        searchTimer = setTimeout(runSearch, 300);
    });
    $('customerSearch').addEventListener('focus', runSearch);
    document.addEventListener('click', function (e) {
        if (!$('customerWrap').contains(e.target)) hideResults();
    });

    $('qaCancel').addEventListener('click', function () { $('quickAddWrap').classList.add('hidden'); });
    $('qaSave').addEventListener('click', function () {
        var name = $('qaName').value.trim();
        var errEl = $('qaError');
        if (!name) {
            errEl.textContent = 'Customer ka naam lazmi hai.';
            errEl.classList.remove('hidden');
            return;
        }
        var token = (document.querySelector('meta[name="csrf-token"]') || {}).content || '';
        var btn = $('qaSave');
        btn.disabled = true;
        fetch(QUICK_STORE_URL, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                name: name,
                phone: $('qaPhone').value.trim() || null,
                email: $('qaEmail').value.trim() || null,
                vehicle_no: $('qaVehicle').value.trim() || null,
                branch_id: BRANCH_ID
            })
        })
            .then(function (r) {
                return r.json().catch(function () { return null; }).then(function (data) {
                    if (!r.ok) {
                        var firstError = data && data.errors ? Object.values(data.errors)[0][0] : null;
                        throw new Error(firstError || 'Customer save nahi ho saka.');
                    }
                    return data;
                });
            })
            .then(function (customer) { pickCustomer(customer); })
            .catch(function (err) {
                errEl.textContent = err.message || 'Customer save nahi ho saka.';
                errEl.classList.remove('hidden');
            })
            .finally(function () { btn.disabled = false; });
    });

    // Init: pehla nozzle auto-select
    markMethod();
    var first = document.querySelector('.nozzle-btn');
    if (first) first.click(); else render();
})();
</script>
@endpush
