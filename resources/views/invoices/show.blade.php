@extends('layouts.app')

@section('title', "Invoice {$invoice->invoice_number} — Mehar Filling Station")

@section('breadcrumb')
    <li class="flex items-center gap-1 text-slate-400">
        <span>/</span>
        <a href="{{ route('invoices.index') }}" class="hover:text-slate-700 dark:hover:text-slate-300">Invoices</a>
    </li>
    <li class="flex items-center gap-1 text-slate-400">
        <span>/</span>
        <span class="text-slate-800 dark:text-slate-200 font-medium">{{ $invoice->invoice_number }}</span>
    </li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Top Action Toolbar --}}
    <div class="bg-white rounded-xl border border-slate-200/80 p-4 shadow-sm dark:bg-slate-900 dark:border-slate-800 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('invoices.index') }}"
               class="p-2 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-600 dark:bg-slate-800 dark:border-slate-700 dark:text-slate-300 transition">
                ←
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-xl font-black text-slate-900 dark:text-white">
                        {{ $invoice->invoice_number }}
                    </h1>
                    @if ($invoice->status === 'paid')
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                            ✓ Paid
                        </span>
                    @elseif ($invoice->status === 'issued')
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                            Udhaar / Issued
                        </span>
                    @else
                        <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ ucfirst($invoice->status) }}
                        </span>
                    @endif

                    @if ($snapshotVerified)
                        <span class="hidden sm:inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-400"
                              title="Immutable Snapshot SHA-256 Cryptographic Hash Verified">
                            <span>🔒</span>
                            <span>Snapshot Integrity Verified</span>
                        </span>
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-0.5">
                    Issued {{ $invoice->invoice_date->format('d M Y, h:i A') }} • Cashier: {{ $invoice->user?->name ?? 'Attendant' }}
                </p>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="flex flex-wrap items-center gap-2">
            {{-- Print A4 Modern Red Band --}}
            <a href="{{ route('invoices.a4', $invoice) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-[#D71920] hover:bg-[#A30F15] rounded-lg shadow-sm transition">
                <span>📄</span>
                <span>Print A4 Invoice</span>
            </a>

            {{-- Thermal 80mm --}}
            <a href="{{ route('invoices.thermal', ['invoice' => $invoice, 'size' => '80mm']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-sm transition dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700">
                <span>🧾</span>
                <span>Thermal 80mm</span>
            </a>

            {{-- Thermal 58mm --}}
            <a href="{{ route('invoices.thermal', ['invoice' => $invoice, 'size' => '58mm']) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-sm transition dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700">
                <span>🧾</span>
                <span>58mm</span>
            </a>

            {{-- PDF Download --}}
            <a href="{{ route('invoices.pdf', $invoice) }}"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 hover:bg-slate-50 rounded-lg shadow-sm transition dark:bg-slate-800 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-700">
                <span>⬇️</span>
                <span>PDF</span>
            </a>

            {{-- Public Verification Link --}}
            <a href="{{ route('invoice.verify', $invoice->hash) }}" target="_blank"
               class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-200 hover:bg-emerald-100 rounded-lg shadow-sm transition dark:bg-emerald-950 dark:border-emerald-800 dark:text-emerald-300">
                <span>🔍</span>
                <span>Verify Online</span>
            </a>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left 2 Columns: Invoice Document Card --}}
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl border border-slate-200/80 shadow-sm overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                {{-- Red Header Banner --}}
                <div class="bg-[#D71920] p-6 text-white border-b-4 border-[#A30F15] flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-12 h-12 rounded-lg bg-white p-1.5 flex items-center justify-center shadow">
                            <svg viewBox="0 0 100 100" width="36" height="36">
                                <circle cx="50" cy="50" r="46" fill="#D71920" />
                                <circle cx="50" cy="50" r="39" fill="#FFFFFF" />
                                <polygon points="50,16 78,74 65,74 50,42 35,74 22,74" fill="#D71920" />
                                <circle cx="50" cy="74" r="5" fill="#D71920" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black tracking-tight leading-none uppercase">
                                {{ $station['station_name_en'] ?? 'MEHAR FILLING STATION' }}
                            </h2>
                            <h3 class="text-base font-bold font-urdu mt-1 text-red-100">
                                {{ $station['station_name_ur'] ?? 'مہر فلنگ اسٹیشن' }}
                            </h3>
                            <div class="text-xs text-red-100 mt-1">
                                {{ $station['omc_brand'] ?? 'Vital Petroleum Pvt. Ltd. (Vital Petrol Branch • وائٹل پیٹرول)' }}
                            </div>
                        </div>
                    </div>

                    <div class="sm:text-right">
                        <span class="inline-block bg-white text-[#D71920] text-[11px] font-black uppercase px-2.5 py-0.5 rounded shadow-sm">
                            Tax Invoice
                        </span>
                        <div class="text-base font-bold mt-1 tracking-wider">{{ $invoice->invoice_number }}</div>
                        <div class="text-xs text-red-100">{{ $invoice->invoice_date->format('d M Y, h:i A') }}</div>
                    </div>
                </div>

                {{-- Station Address Bar --}}
                <div class="bg-slate-50 px-6 py-2.5 border-b border-slate-200 text-xs text-slate-600 flex flex-wrap items-center justify-between gap-2 dark:bg-slate-800/50 dark:border-slate-800 dark:text-slate-400">
                    <div>
                        📍 {{ $station['address'] ?? 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab' }}
                    </div>
                    <div>
                        📞 {{ $station['phone'] ?? '0300-4342343' }} &nbsp;•&nbsp; 💬 {{ $station['whatsapp'] ?? '0300-4342343' }}
                    </div>
                </div>

                {{-- Customer & Sale Info Cards --}}
                <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-4 border-b border-slate-200 dark:border-slate-800">
                    <div class="p-4 rounded-lg border border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/30">
                        <div class="text-xs font-bold uppercase text-slate-500 mb-2 flex items-center justify-between">
                            <span>Customer / خریدار</span>
                            <span>👤</span>
                        </div>
                        <div class="font-bold text-slate-900 dark:text-white text-sm">
                            {{ $customer['name'] ?? ($invoice->customer?->name ?? 'Walk-in Customer / کیش کسٹمر') }}
                        </div>
                        @if (!empty($customer['phone']) || !empty($invoice->customer?->phone))
                            <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                                Phone: {{ $customer['phone'] ?? $invoice->customer->phone }}
                            </div>
                        @endif
                        @if (!empty($customer['ntn']) || !empty($invoice->customer?->ntn_number))
                            <div class="text-xs text-slate-600 dark:text-slate-400">
                                NTN: {{ $customer['ntn'] ?? $invoice->customer->ntn_number }}
                            </div>
                        @endif
                        @if (!empty($customer['vehicle_number']) || !empty($invoice->vehicle?->registration_number))
                            <div class="mt-2 pt-2 border-t border-slate-200 dark:border-slate-700 flex items-center gap-1.5">
                                <span class="text-xs">🚗 Vehicle:</span>
                                <span class="font-mono text-xs font-bold px-1.5 py-0.5 rounded bg-white border border-slate-300 dark:bg-slate-800 dark:border-slate-700">
                                    {{ $customer['vehicle_number'] ?? $invoice->vehicle->registration_number }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="p-4 rounded-lg border border-slate-200 bg-slate-50/50 dark:border-slate-800 dark:bg-slate-800/30">
                        <div class="text-xs font-bold uppercase text-slate-500 mb-2 flex items-center justify-between">
                            <span>Payment & Operation</span>
                            <span>⛽</span>
                        </div>
                        <div class="text-xs space-y-1.5">
                            <div class="flex justify-between">
                                <span class="text-slate-500">Payment Method:</span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 capitalize">
                                    {{ $invoice->payment_method ?? 'Cash' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Status:</span>
                                <span class="font-bold {{ $invoice->isPaid() ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ strtoupper($invoice->status) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">Attendant:</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ $invoice->user?->name ?? 'Cashier' }}
                                </span>
                            </div>
                            @if ($invoice->shift_id)
                                <div class="flex justify-between">
                                    <span class="text-slate-500">Shift Ref:</span>
                                    <span class="font-mono text-slate-700 dark:text-slate-300">#{{ $invoice->shift_id }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="p-6">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead>
                                <tr class="border-b border-slate-200 bg-slate-50 text-slate-700 font-bold dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-300">
                                    <th class="py-2.5 px-3">#</th>
                                    <th class="py-2.5 px-3">Item / Product</th>
                                    <th class="py-2.5 px-3 text-center">Meter Readings</th>
                                    <th class="py-2.5 px-3 text-right">Quantity (Litres)</th>
                                    <th class="py-2.5 px-3 text-right">Rate (Rs.)</th>
                                    <th class="py-2.5 px-3 text-right">Total (Rs.)</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-800 font-medium">
                                @forelse ($invoice->items as $idx => $item)
                                    <tr>
                                        <td class="py-3 px-3 text-slate-400">{{ $idx + 1 }}</td>
                                        <td class="py-3 px-3">
                                            <div class="font-bold text-slate-900 dark:text-white">
                                                {{ $item->fuelProduct?->name ?? $item->item_description }}
                                            </div>
                                            @if ($item->nozzle)
                                                <div class="text-[10px] text-slate-500">
                                                    Nozzle {{ $item->nozzle->nozzle_number ?? $item->nozzle_id }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 text-center font-mono text-slate-500">
                                            @if ($item->meter_start && $item->meter_end)
                                                {{ number_format((float)$item->meter_start, 1) }} → {{ number_format((float)$item->meter_end, 1) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-800 dark:text-slate-200">
                                            {{ number_format((float)$item->quantity, 3) }} L
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono text-slate-700 dark:text-slate-300">
                                            {{ number_format((float)$item->unit_price, 2) }}
                                        </td>
                                        <td class="py-3 px-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                            {{ \App\Support\AmountInWords::formatLakh($item->total_amount, false, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400">No items on this invoice.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals Summary Section --}}
                    <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 items-start">
                        {{-- Left: Urdu Amount in Words --}}
                        <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 dark:bg-slate-800/40 dark:border-slate-800">
                            <div class="text-xs uppercase font-bold text-slate-500 mb-1">
                                Amount in Words / رقم الفاظ میں:
                            </div>
                            <div class="text-base font-bold text-[#D71920] font-urdu leading-relaxed">
                                {{ $invoice->amount_in_words_ur ?: \App\Support\AmountInWords::toUrdu($invoice->total_amount) }}
                            </div>
                            <div class="text-xs text-slate-600 dark:text-slate-400 mt-1">
                                {{ $invoice->amount_in_words_en ?: \App\Support\AmountInWords::toEnglish($invoice->total_amount) }}
                            </div>
                        </div>

                        {{-- Right: Financial Totals Box --}}
                        <div class="rounded-lg border-2 border-[#D71920] overflow-hidden">
                            <div class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                                <div class="p-2.5 px-4 flex justify-between">
                                    <span class="text-slate-500">Subtotal:</span>
                                    <span class="font-semibold">{{ \App\Support\AmountInWords::formatLakh($invoice->subtotal, true, 2) }}</span>
                                </div>
                                @if ((float)$invoice->pos_fee > 0)
                                    <div class="p-2.5 px-4 flex justify-between">
                                        <span class="text-slate-500">POS Fee (SRO 1006):</span>
                                        <span>{{ \App\Support\AmountInWords::formatLakh($invoice->pos_fee, true, 2) }}</span>
                                    </div>
                                @endif
                                @if ((float)$invoice->tax_amount > 0)
                                    <div class="p-2.5 px-4 flex justify-between">
                                        <span class="text-slate-500">Tax / PRA:</span>
                                        <span>{{ \App\Support\AmountInWords::formatLakh($invoice->tax_amount, true, 2) }}</span>
                                    </div>
                                @endif
                                @if ((float)$invoice->discount_amount > 0)
                                    <div class="p-2.5 px-4 flex justify-between text-emerald-600">
                                        <span>Discount:</span>
                                        <span>- {{ \App\Support\AmountInWords::formatLakh($invoice->discount_amount, true, 2) }}</span>
                                    </div>
                                @endif
                                <div class="p-3 px-4 flex justify-between bg-[#D71920] text-white font-black text-sm">
                                    <span>TOTAL AMOUNT:</span>
                                    <span>{{ \App\Support\AmountInWords::formatLakh($invoice->total_amount, true, 2) }}</span>
                                </div>
                                <div class="p-2.5 px-4 flex justify-between bg-slate-50 dark:bg-slate-800/40 font-semibold text-emerald-600">
                                    <span>Paid:</span>
                                    <span>{{ \App\Support\AmountInWords::formatLakh($invoice->paid_amount, true, 2) }}</span>
                                </div>
                                @if ((float)$invoice->balance_due > 0)
                                    <div class="p-2.5 px-4 flex justify-between bg-amber-50 dark:bg-amber-950/30 font-bold text-amber-700">
                                        <span>Balance Due (ادھار):</span>
                                        <span>{{ \App\Support\AmountInWords::formatLakh($invoice->balance_due, true, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Customer Udhaar Balance if any --}}
                    @if ((float)$invoice->closing_balance > 0 || (float)$invoice->previous_balance > 0)
                        <div class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs flex flex-wrap items-center justify-between gap-2 text-amber-900 dark:bg-amber-950/30 dark:border-amber-900 dark:text-amber-200">
                            <div>
                                <strong>Customer Running Balance:</strong>
                                Previous: {{ \App\Support\AmountInWords::formatLakh($invoice->previous_balance, true, 2) }} + Current Due: {{ \App\Support\AmountInWords::formatLakh($invoice->balance_due, true, 2) }}
                            </div>
                            <div class="font-black text-sm text-amber-800 dark:text-amber-300">
                                Total Outstanding: {{ \App\Support\AmountInWords::formatLakh($invoice->closing_balance, true, 2) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column: QR Code, Snapshot & Security Details --}}
        <div class="space-y-6">
            {{-- QR Code Card --}}
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800 text-center">
                <div class="text-xs uppercase font-bold text-slate-500 mb-3">
                    Public Verification QR Code
                </div>
                <div class="inline-block p-3 bg-white rounded-xl border border-slate-200 shadow-sm mx-auto">
                    <div class="w-32 h-32 flex items-center justify-center">
                        {!! $qrSvg !!}
                    </div>
                </div>
                <div class="mt-3 text-xs text-slate-600 dark:text-slate-400">
                    Scan to verify genuine bill on official Mehar Filling Station portal
                </div>
                <div class="mt-4 pt-4 border-t border-slate-100 dark:border-slate-800">
                    <a href="{{ route('invoice.verify', $invoice->hash) }}" target="_blank"
                       class="text-xs font-bold text-[#D71920] hover:underline flex items-center justify-center gap-1">
                        <span>Open Verification Page</span>
                        <span>↗</span>
                    </a>
                </div>
            </div>

            {{-- Cryptographic Snapshot Card --}}
            <div class="bg-white rounded-xl border border-slate-200/80 p-6 shadow-sm dark:bg-slate-900 dark:border-slate-800">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-xs uppercase font-bold text-slate-500">Snapshot Integrity</h3>
                    @if ($snapshotVerified)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                            <span>✓</span> Valid
                        </span>
                    @else
                        <span class="text-[11px] font-bold text-amber-600">Unverified</span>
                    @endif
                </div>

                <div class="space-y-2 text-xs">
                    <div>
                        <div class="text-[10px] text-slate-400 uppercase font-mono">Invoice Hash:</div>
                        <div class="font-mono text-[11px] text-slate-700 dark:text-slate-300 break-all bg-slate-50 dark:bg-slate-800 p-1.5 rounded mt-0.5">
                            {{ $invoice->hash }}
                        </div>
                    </div>

                    @if ($invoice->snapshot?->snapshot_hash)
                        <div>
                            <div class="text-[10px] text-slate-400 uppercase font-mono">SHA-256 Snapshot Hash:</div>
                            <div class="font-mono text-[11px] text-emerald-700 dark:text-emerald-400 break-all bg-emerald-50 dark:bg-emerald-950/40 p-1.5 rounded mt-0.5 border border-emerald-200 dark:border-emerald-900">
                                {{ $invoice->snapshot->snapshot_hash }}
                            </div>
                        </div>
                    @endif

                    <div class="text-[11px] text-slate-500 mt-2">
                        🔒 Immutability Rule: Invoice facts, rates, and identity are frozen at the second of issuance.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
