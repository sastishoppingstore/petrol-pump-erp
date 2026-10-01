@extends('layouts.app')

@section('title', 'Internal Fuel Consumption / اندرونی کھپت')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('tanks.index') }}" class="hover:text-vital-primary">Tanks</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Internal Consumption</li>
@endsection

{{--
    Internal Fuel Consumption (internal-consumption.index).
    Form + history dono InternalFuelConsumptionController ke JSON
    endpoints se chalte hain: store, history, daily-summary, reverse.
    Tanks aur consumption types controller se aate hain — asal data.
--}}
@section('content')
    <div class="page-head">
        <h1>⛽ Internal Fuel Consumption</h1>
        <p>Generator, station vehicle, testing aur cleaning ke liye tank se nikla fuel — stock se automatic deduction</p>
        <div class="page-actions">
            <a href="{{ route('tanks.index') }}" class="btn-3d btn-3d-ghost">Tanks</a>
            <a href="{{ route('tank-readings.index') }}" class="btn-3d btn-3d-ghost">Tank Readings</a>
        </div>
    </div>

    <div id="ic-msg" class="mb-5 hidden rounded-2xl px-5 py-3 text-center text-sm font-bold"></div>

    {{-- ================= Today's summary ================= --}}
    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">Aaj ki Total Khapat</div>
            <div class="stat-value"><span id="sum-total">—</span> L</div>
            <div class="stat-sub">{{ now()->format('d M Y') }}</div>
        </div>
        @foreach ($consumptionTypes as $type)
            <div class="stat-tile-3d tilt-3d stat-slate">
                <div class="stat-label">{{ ucwords(strtolower(str_replace('_', ' ', $type))) }}</div>
                <div class="stat-value"><span id="sum-type-{{ $type }}">—</span> L</div>
                <div class="stat-sub">Aaj ke din</div>
            </div>
        @endforeach
    </div>

    <div class="grid items-start gap-5 xl:grid-cols-5">
        {{-- ================= Record form ================= --}}
        <div class="glass-card p-6 xl:col-span-2">
            <h2 class="mb-4 text-center text-base font-black text-slate-800 dark:text-white">✍️ Record Consumption</h2>
            <form id="ic-form" class="space-y-4">
                <div class="field-3d">
                    <label for="ic_tank" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Tank *</label>
                    <select id="ic_tank" name="tank_id" class="input-3d" required>
                        <option value="">— Select tank —</option>
                        @foreach ($tanks as $tank)
                            <option value="{{ $tank->id }}">
                                {{ $tank->name ?? $tank->tank_number }} — {{ $tank->fuelProduct?->name }} (Stock: {{ number_format((float) $tank->current_stock, 1) }} L)
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="field-3d">
                    <label for="ic_type" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Consumption Type *</label>
                    <select id="ic_type" name="consumption_type" class="input-3d" required>
                        @foreach ($consumptionTypes as $type)
                            <option value="{{ $type }}">{{ ucwords(strtolower(str_replace('_', ' ', $type))) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="field-3d">
                        <label for="ic_litres" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Litres *</label>
                        <input type="number" id="ic_litres" name="litres_consumed" step="0.001" min="0.001" max="1000" class="input-3d" required>
                    </div>
                    <div class="field-3d">
                        <label for="ic_date" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Date *</label>
                        <input type="date" id="ic_date" name="consumption_date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" class="input-3d" required>
                    </div>
                </div>
                <div class="field-3d">
                    <label for="ic_desc" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Description *</label>
                    <input type="text" id="ic_desc" name="description" maxlength="500" class="input-3d" placeholder="e.g. Generator running 6 hours" required>
                </div>
                <button type="submit" class="btn-3d btn-3d-primary w-full">✔ Save Consumption</button>
            </form>
        </div>

        {{-- ================= History ================= --}}
        <div class="glass-card overflow-hidden xl:col-span-3">
            <form id="hist-form" class="grid items-end gap-3 border-b border-slate-200/60 p-4 sm:grid-cols-4 dark:border-slate-700/50">
                <div class="field-3d">
                    <label for="hist_from" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">From</label>
                    <input type="date" id="hist_from" value="{{ now()->startOfMonth()->toDateString() }}" class="input-3d">
                </div>
                <div class="field-3d">
                    <label for="hist_to" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">To</label>
                    <input type="date" id="hist_to" value="{{ now()->toDateString() }}" class="input-3d">
                </div>
                <div class="field-3d">
                    <label for="hist_type" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Type</label>
                    <select id="hist_type" class="input-3d">
                        <option value="">All types</option>
                        @foreach ($consumptionTypes as $type)
                            <option value="{{ $type }}">{{ ucwords(strtolower(str_replace('_', ' ', $type))) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="btn-3d btn-3d-navy w-full">Load History</button>
            </form>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Tank</th><th>Type</th><th>Litres</th><th>Description</th><th></th></tr>
                    </thead>
                    <tbody id="hist-body">
                        <tr><td colspan="6" class="py-8 text-slate-400">Loading…</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const msg = document.getElementById('ic-msg');
    const showMsg = (ok, text) => {
        msg.textContent = text;
        msg.className = 'mb-5 rounded-2xl px-5 py-3 text-center text-sm font-bold ' +
            (ok ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-red-500/15 text-red-700 dark:text-red-300');
        msg.classList.remove('hidden');
    };
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    async function loadSummary() {
        try {
            const res = await fetch('{{ route('internal-consumption.daily-summary') }}?date={{ now()->toDateString() }}', { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (res.ok && data.success) {
                document.getElementById('sum-total').textContent = Number(data.summary.total_litres).toLocaleString('en-PK', { maximumFractionDigits: 3 });
                @foreach ($consumptionTypes as $type)
                    document.getElementById('sum-type-{{ $type }}').textContent =
                        Number((data.summary.by_type || {})['{{ $type }}'] || 0).toLocaleString('en-PK', { maximumFractionDigits: 3 });
                @endforeach
            }
        } catch (err) { /* summary khali rahe to tiles par dash rahega */ }
    }

    async function loadHistory() {
        const from = document.getElementById('hist_from').value;
        const to = document.getElementById('hist_to').value;
        const type = document.getElementById('hist_type').value;
        let url = '{{ route('internal-consumption.history') }}?from_date=' + from + '&to_date=' + to;
        if (type) url += '&consumption_type=' + type;
        const body = document.getElementById('hist-body');
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (!(res.ok && data.success)) { body.innerHTML = '<tr><td colspan="6" class="py-8 text-red-400">History load nahi ho saki.</td></tr>'; return; }
            if (!data.history.length) { body.innerHTML = '<tr><td colspan="6" class="py-8 text-slate-400">Is period me koi consumption record nahi.</td></tr>'; return; }
            body.innerHTML = data.history.map((h) => '<tr>' +
                '<td class="whitespace-nowrap text-xs">' + esc(h.date) + '</td>' +
                '<td class="font-semibold text-xs">' + esc(h.tank) + '</td>' +
                '<td class="text-xs">' + esc(h.type) + (h.is_reversal ? ' <span class="pill-status pill-danger">REVERSAL</span>' : '') + '</td>' +
                '<td class="tabular text-xs font-black">' + Number(h.litres).toLocaleString('en-PK', { maximumFractionDigits: 3 }) + ' L</td>' +
                '<td class="text-xs text-slate-400">' + esc(h.description) + '</td>' +
                '<td>' + (h.is_reversal ? '' : '<button type="button" class="btn-3d btn-3d-ghost btn-3d-sm rev-btn" data-id="' + h.id + '">Reverse</button>') + '</td>' +
                '</tr>').join('');
            body.querySelectorAll('.rev-btn').forEach((btn) => btn.addEventListener('click', () => reverseEntry(btn.dataset.id)));
        } catch (err) { body.innerHTML = '<tr><td colspan="6" class="py-8 text-red-400">Network error — history load nahi ho saki.</td></tr>'; }
    }

    async function reverseEntry(id) {
        const reason = window.prompt('Reverse karne ki wajah likhein (lazmi):');
        if (!reason) return;
        try {
            const res = await fetch('{{ route('internal-consumption.reverse', ['consumption' => '__ID__']) }}'.replace('__ID__', id), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ reason }),
            });
            const data = await res.json();
            showMsg(res.ok && data.success, data.message || 'Reverse failed.');
            if (res.ok && data.success) { loadHistory(); loadSummary(); }
        } catch (err) { showMsg(false, 'Network error — entry reverse nahi ho saki.'); }
    }

    document.getElementById('ic-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await fetch('{{ route('internal-consumption.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            showMsg(res.ok && data.success, data.message || 'Save failed.');
            if (res.ok && data.success) { e.target.reset(); document.getElementById('ic_date').value = '{{ now()->toDateString() }}'; loadHistory(); loadSummary(); }
        } catch (err) { showMsg(false, 'Network error — consumption save nahi ho saki.'); }
    });

    document.getElementById('hist-form').addEventListener('submit', (e) => { e.preventDefault(); loadHistory(); });

    loadSummary();
    loadHistory();
</script>
@endpush
