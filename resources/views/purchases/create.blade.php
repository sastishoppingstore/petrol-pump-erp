@extends('layouts.app')

@section('title', 'Record Fuel Tanker Decantation')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('purchases.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Purchases</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Tanker Arrival</span></li>
@endsection

@section('content')
<div class="mx-auto max-w-5xl space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white flex items-center gap-2">
                <span class="text-red-600">🚛</span> Record Fuel Tanker Decantation
            </h1>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Log OMC fuel delivery challan, pre/post decantation tank dips, volume verification, and weighted-average costing.
            </p>
        </div>
        <a href="{{ route('purchases.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 transition shadow-sm">
            ← Back to Purchases
        </a>
    </div>

    {{-- Main Form --}}
    <form method="POST" action="{{ route('purchases.store') }}" enctype="multipart/form-data"
          x-data="{
              ordered: '{{ old('volume_ordered', '') }}',
              received: '{{ old('volume_received', '') }}',
              rate: '{{ old('purchase_rate', '') }}',
              ifem: '{{ old('ifem', '0') }}',
              levy: '{{ old('petroleum_levy', '0') }}',
              freight: '{{ old('freight_charges', '0') }}',
              tax: '{{ old('tax_amount', '0') }}',
              other: '{{ old('other_charges', '0') }}',

              get subtotal() {
                  const r = parseFloat(this.received) || 0;
                  const rt = parseFloat(this.rate) || 0;
                  return (r * rt).toFixed(2);
              },
              get shortageLitres() {
                  const o = parseFloat(this.ordered) || 0;
                  const r = parseFloat(this.received) || 0;
                  return (o > r) ? (o - r).toFixed(3) : 0;
              },
              get shortageAmount() {
                  const s = parseFloat(this.shortageLitres) || 0;
                  const rt = parseFloat(this.rate) || 0;
                  return (s * rt).toFixed(2);
              },
              get totalAmount() {
                  const sub = parseFloat(this.subtotal) || 0;
                  const i = parseFloat(this.ifem) || 0;
                  const l = parseFloat(this.levy) || 0;
                  const f = parseFloat(this.freight) || 0;
                  const t = parseFloat(this.tax) || 0;
                  const o = parseFloat(this.other) || 0;
                  return (sub + i + l + f + t + o).toFixed(2);
              }
          }"
          class="space-y-6">
        @csrf

        {{-- Section 1: Tanker Challan & Destination Info --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
            <div class="border-b border-slate-200 pb-3 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📋</span> Tanker Challan & Destination Tank
                </h2>
                <p class="text-xs text-slate-500">Specify the supplying OMC, delivery challan details, and the underground destination tank.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Supplier / OMC *</label>
                    <select name="supplier_id" required class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">Select Supplier</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->id }}" @selected(old('supplier_id', request('supplier_id')) == $s->id)>
                                {{ $s->name }} ({{ $s->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('supplier_id') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Delivery Date *</label>
                    <input type="date" name="purchase_date" value="{{ old('purchase_date', today()->toDateString()) }}" required
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('purchase_date') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Fuel Product *</label>
                    <select name="fuel_product_id" required class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">Select Fuel Product</option>
                        @foreach($fuelProducts as $fp)
                            <option value="{{ $fp->id }}" @selected(old('fuel_product_id') == $fp->id)>
                                {{ $fp->name }} ({{ $fp->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('fuel_product_id') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Destination Tank *</label>
                    <select name="tank_id" required class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                        <option value="">Select Tank</option>
                        @foreach($tanks as $t)
                            @php
                                $availSpace = \App\Support\Quantity::subtract($t->capacity, $t->current_stock);
                            @endphp
                            <option value="{{ $t->id }}" @selected(old('tank_id') == $t->id)>
                                Tank #{{ $t->tank_number }} ({{ $t->fuelProduct->name ?? 'Fuel' }}) - Stock: {{ \App\Support\Quantity::format($t->current_stock) }} L / Cap: {{ \App\Support\Quantity::format($t->capacity) }} L [Avail: {{ \App\Support\Quantity::format($availSpace) }} L]
                            </option>
                        @endforeach
                    </select>
                    @error('tank_id') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Challan / Bilty Number *</label>
                    <input type="text" name="challan_number" value="{{ old('challan_number') }}" required placeholder="e.g. CH-908123"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('challan_number') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Supplier Invoice Number</label>
                    <input type="text" name="invoice_number" value="{{ old('invoice_number') }}" placeholder="e.g. INV-2026-081"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('invoice_number') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Tanker / Bowser Number *</label>
                    <input type="text" name="tanker_number" value="{{ old('tanker_number') }}" required placeholder="e.g. TL-4589"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('tanker_number') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Driver Name *</label>
                    <input type="text" name="driver_name" value="{{ old('driver_name') }}" required placeholder="e.g. Muhammad Akram"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('driver_name') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Attach Bill / Challan Photo</label>
                    <input type="file" name="bill_photo" accept="image/*,.pdf"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs file:mr-2 file:py-1 file:px-2 file:rounded file:border-0 file:text-xs file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('bill_photo') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- Section 2: Volume & Dip Readings --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
            <div class="border-b border-slate-200 pb-3 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>📏</span> Dip Measurements & Fuel Volume
                </h2>
                <p class="text-xs text-slate-500">Record dip rod measurements before and after decantation to determine received volume.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Challan Volume (Ordered Litres) *</label>
                    <input type="number" step="0.001" name="volume_ordered" x-model="ordered" required placeholder="e.g. 10000"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('volume_ordered') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Actual Received (Decanted Litres) *</label>
                    <input type="number" step="0.001" name="volume_received" x-model="received" required placeholder="e.g. 9950"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('volume_received') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Tank Dip Before (cm / L)</label>
                    <input type="number" step="0.01" name="dip_before" value="{{ old('dip_before') }}" placeholder="e.g. 45.5"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('dip_before') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Tank Dip After (cm / L)</label>
                    <input type="number" step="0.01" name="dip_after" value="{{ old('dip_after') }}" placeholder="e.g. 195.0"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('dip_after') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Product Density (kg/m³)</label>
                    <input type="number" step="0.0001" name="density" value="{{ old('density') }}" placeholder="e.g. 0.7450"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Fuel Temperature (°C)</label>
                    <input type="number" step="0.1" name="temperature" value="{{ old('temperature') }}" placeholder="e.g. 28.5"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>

            {{-- Shortage Warning Banner --}}
            <div x-show="shortageLitres > 0" class="rounded-lg border-2 border-red-500 bg-red-50 p-4 dark:bg-red-950/30">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">⚠️</span>
                    <div>
                        <h4 class="text-xs font-bold uppercase tracking-wider text-red-800 dark:text-red-300">
                            Transit Shortage Detected: <span x-text="shortageLitres"></span> Litres Short
                        </h4>
                        <p class="text-xs text-red-700 dark:text-red-400 mt-0.5">
                            Challan quantity exceeds decanted volume. Estimated shortage value: <strong>Rs. <span x-text="shortageAmount"></span></strong>. This purchase will automatically be flagged for a shortage claim against the transporter/supplier.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Pricing, Surcharges & Costing --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-4">
            <div class="border-b border-slate-200 pb-3 dark:border-slate-800">
                <h2 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <span>💰</span> Purchase Rates, IFEM, Levy & Freight
                </h2>
                <p class="text-xs text-slate-500">Official invoice rates and tax breakdown to update the weighted-average product cost.</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Purchase Rate per Litre (Rs.) *</label>
                    <input type="number" step="0.01" name="purchase_rate" x-model="rate" required placeholder="e.g. 265.50"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono font-bold text-slate-900 focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    @error('purchase_rate') <span class="text-[11px] text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">IFEM (Inland Freight Equalisation Margin)</label>
                    <input type="number" step="0.01" name="ifem" x-model="ifem" placeholder="0.00"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Petroleum Levy (PL)</label>
                    <input type="number" step="0.01" name="petroleum_levy" x-model="levy" placeholder="0.00"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Freight Charges (Bowser Carriage)</label>
                    <input type="number" step="0.01" name="freight_charges" x-model="freight" placeholder="0.00"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Provincial Tax / Sales Tax</label>
                    <input type="number" step="0.01" name="tax_amount" x-model="tax" placeholder="0.00"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Other Charges / Tolls</label>
                    <input type="number" step="0.01" name="other_charges" x-model="other" placeholder="0.00"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs font-mono focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>

                <div class="sm:col-span-3">
                    <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400">Remarks / Decantation Notes</label>
                    <input type="text" name="notes" value="{{ old('notes') }}" placeholder="e.g. Decanted at 04:30 AM under supervision of Manager Tariq"
                           class="mt-1 w-full rounded-lg border-slate-300 text-xs focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>

            {{-- Calculated Total Callout --}}
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/60">
                <div class="grid grid-cols-2 gap-4 text-xs sm:grid-cols-4">
                    <div>
                        <div class="text-slate-400 font-medium">Decanted Volume</div>
                        <div class="text-base font-bold text-slate-900 dark:text-white font-mono mt-0.5">
                            <span x-text="received || '0'"></span> L
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-400 font-medium">Fuel Base Subtotal</div>
                        <div class="text-base font-bold text-slate-900 dark:text-white font-mono mt-0.5">
                            Rs. <span x-text="subtotal"></span>
                        </div>
                    </div>

                    <div>
                        <div class="text-slate-400 font-medium">Surcharges & Freight</div>
                        <div class="text-base font-bold text-slate-900 dark:text-white font-mono mt-0.5">
                            Rs. <span x-text="(parseFloat(ifem||0) + parseFloat(levy||0) + parseFloat(freight||0) + parseFloat(tax||0) + parseFloat(other||0)).toFixed(2)"></span>
                        </div>
                    </div>

                    <div>
                        <div class="text-red-600 font-bold uppercase text-[10px]">Total Invoice Payable</div>
                        <div class="text-xl font-black text-red-600 dark:text-red-400 font-mono mt-0.5">
                            Rs. <span x-text="totalAmount"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex items-center justify-end gap-3 pt-2">
            <a href="{{ route('purchases.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-red-600 px-6 py-2.5 text-sm font-bold text-white hover:bg-red-700 shadow-md transition">
                Save & Proceed to Approval →
            </button>
        </div>
    </form>
</div>
@endsection
