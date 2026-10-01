@extends('layouts.app')

@section('title', 'Cashier — Picture POS')
@section('breadcrumb')
    <li class="text-slate-500">Forecourt</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Cashier Screen (تصویری کیشیئر)</li>
@endsection

@section('content')
<div class="space-y-4">

    {{-- ===== Header: shift info ===== --}}
    <div class="glass-card flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-gradient-to-br from-red-600 to-red-800 text-2xl text-white shadow-glow">⛽</span>
            <div>
                <h1 class="text-lg font-black text-slate-800 dark:text-white">Cashier — Quick Bill Screen</h1>
                <p class="text-xs text-slate-500">
                    Shift <strong class="text-slate-800 dark:text-slate-200">{{ $dashboardData['shift_info']['shift_number'] ?? $shift->shift_number }}</strong>
                    · Khuli: <strong>{{ $dashboardData['shift_info']['opened_at'] ?? '—' }}</strong>
                    · Cashier: <strong>{{ $dashboardData['shift_info']['employee'] ?? '—' }}</strong>
                    · Opening Cash: <strong>{{ \App\Support\PakistaniCurrency::format($dashboardData['shift_info']['opening_cash'] ?? $shift->opening_cash) }}</strong>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('pos.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm"><span>🧾</span> Full POS Wizard</a>
            <a href="{{ route('sales.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm"><span>📜</span> Sales History</a>
        </div>
    </div>

    {{-- ===== Errors (sale fail ho to wajah yahan saaf dikhegi) ===== --}}
    @if ($errors->any())
        <div class="rounded-2xl border border-red-300 bg-red-50 p-4 text-sm text-red-800 shadow-3d dark:border-red-800 dark:bg-red-950/40 dark:text-red-200" role="alert">
            <strong>⚠ Sale mukammal nahi ho saki (Sale could not be completed):</strong>
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
                    {{ $tiles['sales_today']['trend']['percent'] ?? 0 }}% vs kal
                </div>
            @endif
        </div>

        <div class="stat-tile-3d tilt-3d p-4">
            <div class="text-[11px] font-black uppercase tracking-widest text-slate-500">⛽ {{ $tiles['fuel_sold']['label'] ?? 'Fuel Sold' }}</div>
            <div class="tabular mt-1 font-mono text-2xl font-black text-slate-800 dark:text-white">{{ number_format((float) ($tiles['fuel_sold']['value'] ?? 0), 3) }} L</div>
            @if (! empty($tiles['fuel_sold']['trend']))
                <div class="mt-1 text-[11px] font-bold {{ ($tiles['fuel_sold']['trend']['direction'] ?? '') === 'down' ? 'text-red-500' : 'text-emerald-500' }}">
                    {{ ($tiles['fuel_sold']['trend']['direction'] ?? '') === 'down' ? '▼' : (($tiles['fuel_sold']['trend']['direction'] ?? '') === 'up' ? '▲' : '•') }}
                    {{ $tiles['fuel_sold']['trend']['percent'] ?? 0 }}% vs kal
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
            <div class="mt-1 text-[11px] font-bold text-slate-500">{{ number_format((float) ($tiles['margin']['percent'] ?? 0), 2) }}% of sales</div>
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
                    <h2 class="mb-3 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">1. Nozzle Chunein (Select Nozzle)</h2>

                    @if (count($nozzleStatus) === 0)
                        <div class="p-6 text-center text-sm text-slate-400">
                            Is shift par koi nozzle assign nahi hai. Pehle Shift Management me nozzle assign karein.
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
                                <div class="tabular mt-1 text-[11px] text-slate-500">Meter: {{ number_format((float) $nz['current_meter'], 3) }}</div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                    <div class="h-full rounded-full {{ ! empty($nz['low_stock']) ? 'bg-red-500' : 'bg-emerald-500' }}" style="width: {{ min(100, max(0, (float) $nz['stock_percent'])) }}%"></div>
                                </div>
                                <div class="mt-1 flex justify-between text-[10px] {{ ! empty($nz['low_stock']) ? 'font-bold text-red-500' : 'text-slate-400' }}">
                                    <span>Tank: {{ number_format((float) $nz['tank_stock'], 0) }} L</span>
                                    <span>{{ $nz['stock_percent'] }}%</span>
                                </div>
                            </button>
                        @endforeach
                    </div>
                </div>

                <div class="glass-card p-4 sm:p-5">
                    <div class="mb-3 flex items-center justify-between">
                        <h2 class="text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">2. Miqdaar (Quantity)</h2>
                        <div class="flex rounded-lg bg-slate-100 p-0.5 dark:bg-slate-800">
                            <button type="button" data-mode="LITRES" class="mode-btn rounded-md bg-red-600 px-3 py-1 text-xs font-bold text-white shadow-sm transition">Litres (لیٹر)</button>
                            <button type="button" data-mode="AMOUNT" class="mode-btn rounded-md px-3 py-1 text-xs text-slate-600 transition dark:text-slate-300">Amount (روپے)</button>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300" id="inputLabel">Litres Likhen:</label>
                            <div class="flex items-center rounded-lg border-2 border-slate-300 bg-slate-900 px-3 py-3 font-mono text-2xl font-bold text-emerald-400 dark:border-slate-700">
                                <span id="displayValue" class="w-full text-right tracking-wider">0</span>
                            </div>
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Rate (Rs./L):</label>
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
                                <div class="text-[11px] font-semibold uppercase tracking-wider text-red-200">Litres</div>
                                <div class="font-mono text-2xl font-black" id="displayLitres">0.000 L</div>
                            </div>
                            <div class="text-right">
                                <div class="text-[11px] font-semibold uppercase tracking-wider text-red-200">Total Bill</div>
                                <div class="tabular font-mono text-3xl font-black" id="displayTotal">Rs. 0.00</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ===== Right: Payment + Banknotes + Submit ===== --}}
            <div class="space-y-4 lg:col-span-5">

                <div class="glass-card p-4 sm:p-5" id="paymentPanel">
                    <h2 class="mb-3 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">3. Payment Method</h2>

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

                    {{-- Credit customer — sirf CREDIT method par lazmi --}}
                    <div id="customerWrap" class="mt-3 hidden">
                        <label class="mb-1 block text-xs font-semibold text-slate-700 dark:text-slate-300">Credit Customer (ادھار گاہک) *</label>
                        <select name="customer_id" id="customerSelect" class="input-3d" disabled>
                            <option value="">-- Customer Chunein --</option>
                            @foreach ($customers as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}{{ $c->code ? ' (' . $c->code . ')' : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Customer Name (Optional)</label>
                        <input type="text" name="customer_name" maxlength="150" placeholder="e.g. Haji Aslam" class="input-3d">
                    </div>
                    <div class="mt-2">
                        <label class="mb-1 block text-xs font-medium text-slate-600 dark:text-slate-400">Mobile (WhatsApp Receipt ke liye)</label>
                        <input type="text" name="customer_phone" maxlength="30" placeholder="03001234567" class="input-3d">
                    </div>
                </div>

                {{-- Banknote counting helper — CashierUiService::getBanknoteDenominations --}}
                <div class="glass-card p-4 sm:p-5">
                    <h2 class="mb-1 text-center text-xs font-black uppercase tracking-[0.14em] text-slate-500">4. Note Gin lein (Cash Counting)</h2>
                    <p class="mb-3 text-center text-[11px] text-slate-400">Sirf ginne ki madad ke liye hai — bill total neeche khud match karein.</p>

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
                        <span class="font-semibold text-slate-600 dark:text-slate-300">Gine Hue Note:</span>
                        <span class="tabular font-mono text-lg font-black" id="notesTotal">Rs. 0</span>
                    </div>
                    <div class="flex items-center justify-between text-sm">
                        <span class="font-semibold text-slate-600 dark:text-slate-300">Farq (Notes − Bill):</span>
                        <span class="tabular font-mono text-lg font-black" id="notesDiff">Rs. 0.00</span>
                    </div>
                </div>

                <button type="submit" id="submitBtn"
                        class="flex min-h-[64px] w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-b from-emerald-500 to-emerald-700 px-6 py-3 text-lg font-black text-white shadow-fab transition hover:-translate-y-0.5 hover:from-emerald-400 hover:to-emerald-600 active:translate-y-0 disabled:opacity-40">
                    <span>🧾</span>
                    <span>Bill Banao — Complete Sale</span>
                </button>
                <a href="{{ route('cashier.index') }}" class="btn-3d btn-3d-ghost w-full text-center">↺ Dobara / New Bill</a>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    var NOZZLES = @js($nozzleStatus);
    var state = { nozzleId: null, rate: 0, mode: 'LITRES', buffer: '', method: 'CASH' };
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
        $('customerSelect').disabled = !isCredit;
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
        if (state.method === 'CREDIT' && !$('customerSelect').value) {
            e.preventDefault(); alert('Udhaar sale ke liye customer lazmi chunein.'); return;
        }
        $('submitBtn').disabled = true;
        $('submitBtn').style.opacity = '.55';
    });

    // Init: pehla nozzle auto-select
    markMethod();
    var first = document.querySelector('.nozzle-btn');
    if (first) first.click(); else render();
})();
</script>
@endpush
