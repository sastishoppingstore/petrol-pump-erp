@extends('layouts.app')

@section('title', 'Point of Sale')
@section('breadcrumb')
    <li class="text-slate-500">Point of Sale</li>
@endsection

@section('content')
    <h1 class="mb-1 text-xl font-bold">Point of Sale</h1>
    <p class="mb-4 text-sm text-slate-500">
        @if ($shift)
            Shift <strong>{{ $shift->shift_number }}</strong> ·
            opening cash Rs. {{ number_format((float) $shift->opening_cash, 2) }}
        @else
            <span class="font-semibold text-amber-600">No open shift.</span>
            Open a shift before selling fuel.
        @endif
    </p>

    @unless ($shift)
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
            Sales need an open shift so every sale is attributed to a cashier and till.
        </div>
    @endunless

    @if ($shift && $nozzles->isNotEmpty())
        <form method="POST" action="{{ route('pos.store') }}"
              x-data="posForm(@js($nozzles->map(fn($n) => [
                  'id' => $n->id,
                  'label' => $n->label(),
                  'fuel' => $n->fuelProduct?->name,
                  'rate' => (float) $n->fuelProduct?->currentPrice($branchId),
              ])), {{ $shift->opening_cash }})">
            @csrf

            {{-- Idempotency: one token per page load, so a double tap cannot charge twice. --}}
            <input type="hidden" name="request_token" value="{{ app(\App\Services\Sale\SaleService::class)->newRequestToken() }}">
            <input type="hidden" name="branch_id" value="{{ $branchId }}">
            <input type="hidden" name="shift_id" value="{{ $shift->id }}">

            <div class="grid gap-4 lg:grid-cols-3">

                {{-- ============ Nozzles ============ --}}
                <div class="lg:col-span-2">
                    <h2 class="mb-2 text-sm font-bold uppercase text-slate-500">Select Nozzle</h2>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                        @foreach ($nozzles as $nozzle)
                            <button type="button"
                                    @click="selectNozzle({{ $nozzle->id }})"
                                    :class="selected === {{ $nozzle->id }} ? 'ring-2 ring-amber-500 bg-navy-800 text-white' : 'bg-white border border-slate-200 dark:border-slate-700 dark:bg-slate-900'"
                                    class="min-h-[96px] rounded-lg p-3 text-left">
                                <div class="text-base font-bold">{{ $nozzle->dispenser?->dispenser_number }}/{{ $nozzle->nozzle_number }}</div>
                                <div class="mt-1 text-xs opacity-80">{{ $nozzle->fuelProduct?->name }}</div>
                                <div class="tabular mt-1 text-xs opacity-70">
                                    Rs. {{ number_format((float) $nozzle->fuelProduct?->currentPrice($branchId), 2) }}/L
                                </div>
                            </button>
                        @endforeach
                    </div>

                    {{-- ============ Quantity ============ --}}
                    <div class="mt-5 rounded-lg border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
                        <div class="mb-3 flex gap-2">
                            <button type="button" @click="mode = 'LITRES'"
                                    :class="mode === 'LITRES' ? 'bg-navy-800 text-white' : 'bg-slate-100 dark:bg-slate-800'"
                                    class="flex-1 rounded-md px-4 py-2 text-sm font-semibold">Enter Litres</button>
                            <button type="button" @click="mode = 'AMOUNT'"
                                    :class="mode === 'AMOUNT' ? 'bg-navy-800 text-white' : 'bg-slate-100 dark:bg-slate-800'"
                                    class="flex-1 rounded-md px-4 py-2 text-sm font-semibold">Enter Amount</button>
                        </div>

                        <template x-if="!selected">
                            <p class="text-sm text-slate-500">Pick a nozzle first.</p>
                        </template>

                        <template x-if="selected">
                            <div class="space-y-3">
                                <div class="flex flex-wrap gap-2">
                                    @foreach ([10, 20, 50, 100] as $quick)
                                        <button type="button" @click="value = '{{ $quick }}'"
                                                class="erp-quick min-w-[64px] rounded-md border border-slate-300 px-4 py-3 text-base font-bold dark:border-slate-700">
                                            {{ $quick }}
                                        </button>
                                    @endforeach
                                </div>

                                <div class="grid gap-3 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1 block text-sm font-medium">
                                            <span x-text="mode === 'LITRES' ? 'Litres' : 'Amount (Rs.)'"></span>
                                        </label>
                                        <input type="number" step="0.001" min="0" x-model="value"
                                               class="tabular w-full rounded-md border-slate-300 py-3 text-lg font-bold dark:border-slate-700 dark:bg-slate-800">
                                    </div>
                                    <div>
                                        <label class="mb-1 block text-sm font-medium">Rate (Rs./L)</label>
                                        <input type="text" readonly :value="rate.toFixed(2)"
                                               class="tabular w-full rounded-md border-slate-300 bg-slate-50 py-3 text-lg dark:border-slate-700 dark:bg-slate-800">
                                    </div>
                                </div>

                                <div class="rounded-md bg-navy-900 p-4 text-white">
                                    <div class="text-xs uppercase tracking-wide text-slate-400">Total payable</div>
                                    <div class="tabular text-3xl font-bold" x-text="'Rs. ' + total().toLocaleString('en-PK', {minimumFractionDigits: 2})"></div>
                                </div>

                                <input type="hidden" name="quantities[x]" :value="mode + ':' + (mode === 'LITRES' ? parseFloat(value || 0).toFixed(3) : parseFloat(value || 0).toFixed(2))">
                            </div>
                        </template>
                    </div>
                </div>

                {{-- ============ Payment ============ --}}
                <div>
                    <h2 class="mb-2 text-sm font-bold uppercase text-slate-500">Payment</h2>

                    <div class="mb-3">
                        <label class="mb-1 block text-sm font-medium">Customer (for credit)</label>
                        <select name="customer_id"
                                class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                            <option value="">Walk-in cash customer</option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-2">
                        @foreach ($methods as $key => $label)
                            <label class="flex cursor-pointer items-center gap-2 rounded-md border border-slate-200 px-3 py-2 text-sm dark:border-slate-700">
                                <input type="radio" name="payment_method" value="{{ $key }}"
                                       x-model="payMethod"
                                       @if ($loop->first) checked @endif
                                       class="text-navy-700 focus:ring-navy-600">
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 block text-sm font-medium">Amount</label>
                        <input type="number" step="0.01" min="0" name="amount" x-model="payAmount"
                               class="tabular w-full rounded-md border-slate-300 py-3 text-lg font-bold dark:border-slate-700 dark:bg-slate-800">
                    </div>

                    <div class="mt-3">
                        <label class="mb-1 block text-sm font-medium">Discount (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="discount" value="0"
                               class="tabular w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800">
                    </div>

                    <input type="hidden" name="payments[0][method]" :value="payMethod">
                    <input type="hidden" name="payments[0][amount]" :value="payAmount">

                    <button type="submit" x-show="selected && parseFloat(value) > 0"
                            x-bind:disabled="payAmount < 0.01"
                            class="mt-4 min-h-[56px] w-full rounded-md bg-emerald-600 text-lg font-bold text-white hover:bg-emerald-700 disabled:opacity-40">
                        Complete Sale
                    </button>
                </div>
            </div>
        </form>
    @elseif ($shift)
        <div class="rounded-lg border border-slate-200 bg-white p-4 text-sm text-slate-500 dark:border-slate-800 dark:bg-slate-900">
            No active nozzles on this shift. Assign nozzles when opening the shift.
        </div>
    @endif
@endsection

@push('scripts')
<script>
function posForm(nozzles, openingCash) {
    return {
        nozzles,
        selected: null,
        mode: 'LITRES',
        value: '',
        payMethod: 'CASH',
        payAmount: '',

        rate() {
            const n = this.nozzles.find(x => x.id === this.selected);
            return n ? Number(n.rate || 0) : 0;
        },

        selectNozzle(id) {
            this.selected = id;
            this.value = '';
            this.syncPay();
        },

        total() {
            const v = parseFloat(this.value || 0);
            if (!v || !this.selected) return 0;
            return this.mode === 'LITRES'
                ? Math.round(v * this.rate() * 100) / 100
                : v;
        },

        syncPay() {
            this.payAmount = this.total() > 0 ? this.total().toFixed(2) : '';
        },

        init() {
            this.$watch('value', () => this.syncPay());
            this.$watch('mode', () => this.syncPay());
        },
    };
}
</script>
@endpush
