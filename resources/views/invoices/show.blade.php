@extends('layouts.app')

@section('title', "Invoice {$invoice->invoice_number} — Mehar Filling Station")

@section('breadcrumb')
    <li class="flex items-center gap-1 text-slate-400">
        <span>/</span>
        <a href="{{ route('invoices.index') }}" class="hover:text-slate-700 dark:hover:text-slate-300">{{ __('sales.invoice_show.breadcrumb_invoices') }}</a>
    </li>
    <li class="flex items-center gap-1 text-slate-400">
        <span>/</span>
        <span class="font-medium text-slate-800 dark:text-slate-200">{{ $invoice->invoice_number }}</span>
    </li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Top Action Toolbar --}}
    <div class="glass-card p-4 text-center">
        <div class="flex flex-wrap items-center justify-center gap-2">
            <span class="tabular text-xl font-black text-slate-900 dark:text-white">
                {{ $invoice->invoice_number }}
            </span>
            @if ($invoice->status === 'paid')
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-bold text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                    ✓ {{ __('sales.invoice_show.paid') }}
                </span>
            @elseif ($invoice->status === 'issued')
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-bold text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                    {{ __('sales.invoice_show.issued_badge') }}
                </span>
            @else
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                    {{ ucfirst($invoice->status) }}
                </span>
            @endif

            @if ($snapshotVerified)
                <span class="hidden items-center gap-1 rounded-full border border-emerald-200 bg-emerald-50 px-2 py-0.5 text-[11px] font-semibold text-emerald-700 sm:inline-flex dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-400"
                      title="{{ __('sales.invoice_show.snapshot_title') }}">
                    <span>🔒</span>
                    <span>{{ __('sales.invoice_show.snapshot_verified') }}</span>
                </span>
            @endif
        </div>
        <p class="mt-0.5 text-xs text-slate-500">
            {{ __('sales.invoice_show.issued') }} {{ $invoice->invoice_date->format('d M Y, h:i A') }} • {{ __('sales.invoice_show.cashier') }}: {{ $invoice->user?->name ?? 'Attendant' }}
        </p>

        {{-- Action Buttons --}}
        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
            <a href="{{ route('invoices.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                ← {{ __('sales.invoice_show.all_invoices') }}
            </a>

            {{-- Print A4 Modern Red Band --}}
            <a href="{{ route('invoices.a4', $invoice) }}" target="_blank" class="btn-3d btn-3d-primary btn-3d-sm">
                <span>📄</span>
                <span>{{ __('sales.invoice_show.print_a4') }}</span>
            </a>

            {{-- Thermal 80mm --}}
            <a href="{{ route('invoices.thermal', ['invoice' => $invoice, 'size' => '80mm']) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm">
                <span>🧾</span>
                <span>{{ __('sales.invoice_show.thermal80') }}</span>
            </a>

            {{-- Thermal 58mm --}}
            <a href="{{ route('invoices.thermal', ['invoice' => $invoice, 'size' => '58mm']) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm">
                <span>🧾</span>
                <span>{{ __('sales.invoice_show.thermal58') }}</span>
            </a>

            {{-- PDF Download --}}
            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                <span>⬇️</span>
                <span>{{ __('sales.invoice_show.pdf') }}</span>
            </a>

            {{-- Email Bill to Customer (A4 PDF attached) --}}
            <form method="POST" action="{{ route('invoices.email', $invoice) }}" class="inline-flex">
                @csrf
                <button type="submit" class="btn-3d btn-3d-success btn-3d-sm">
                    <span>📧</span>
                    <span>Email Bill</span>
                </button>
            </form>
            @if (empty($invoice->customer?->email))
                <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">⚠️ Customer ka email nahi hai — email bill nahi jayega</span>
            @endif

            {{-- Public Verification Link --}}
            <a href="{{ route('invoice.verify', $invoice->hash) }}" target="_blank" class="btn-3d btn-3d-success btn-3d-sm">
                <span>🔍</span>
                <span>{{ __('sales.invoice_show.verify_online') }}</span>
            </a>
        </div>
    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Left 2 Columns: Invoice Document Card --}}
        <div class="space-y-6 lg:col-span-2">
            <div class="glass-card overflow-hidden">
                {{-- Red Header Banner --}}
                <div class="flex flex-col gap-4 border-b-4 border-vital-darkred bg-gradient-to-br from-vital-primary to-vital-darkred p-6 text-white sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-4">
                        <div class="flex h-12 w-12 items-center justify-center rounded-lg bg-white p-1.5 shadow">
                            <svg viewBox="0 0 100 100" width="36" height="36">
                                <circle cx="50" cy="50" r="46" fill="#D71920" />
                                <circle cx="50" cy="50" r="39" fill="#FFFFFF" />
                                <polygon points="50,16 78,74 65,74 50,42 35,74 22,74" fill="#D71920" />
                                <circle cx="50" cy="74" r="5" fill="#D71920" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-black uppercase leading-none tracking-tight">
                                {{ $station['station_name_en'] ?? 'MEHAR FILLING STATION' }}
                            </h2>
                            <h3 class="font-urdu mt-1 text-base font-bold text-red-100">
                                {{ $station['station_name_ur'] ?? 'مہر فلنگ اسٹیشن' }}
                            </h3>
                            <div class="mt-1 text-xs text-red-100">
                                {{ $station['omc_brand'] ?? 'Vital Petroleum Pvt. Ltd. (Vital Petrol Branch • وائٹل پیٹرول)' }}
                            </div>
                        </div>
                    </div>

                    <div class="sm:text-right">
                        <span class="inline-block rounded bg-white px-2.5 py-0.5 text-[11px] font-black uppercase text-vital-primary shadow-sm">
                            {{ __('sales.invoice_show.tax_invoice') }}
                        </span>
                        <div class="tabular mt-1 text-base font-bold tracking-wider">{{ $invoice->invoice_number }}</div>
                        <div class="text-xs text-red-100">{{ $invoice->invoice_date->format('d M Y, h:i A') }}</div>
                    </div>
                </div>

                {{-- Station Address Bar --}}
                <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 bg-slate-50/70 px-6 py-2.5 text-center text-xs text-slate-600 dark:border-slate-800 dark:bg-slate-800/50 dark:text-slate-400">
                    <div>
                        📍 {{ $station['address'] ?? 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab' }}
                    </div>
                    <div>
                        📞 {{ $station['phone'] ?? '0300-4342343' }} &nbsp;•&nbsp; 💬 {{ $station['whatsapp'] ?? '0300-4342343' }}
                    </div>
                </div>

                {{-- Customer & Sale Info Cards --}}
                <div class="grid grid-cols-1 gap-4 border-b border-slate-200 p-6 text-center md:grid-cols-2 dark:border-slate-800">
                    <div class="rounded-xl border border-white/60 bg-white/50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-800/30">
                        <div class="mb-2 flex items-center justify-between text-xs font-bold uppercase text-slate-500">
                            <span>{{ __('sales.invoice_show.customer') }}</span>
                            <span>👤</span>
                        </div>
                        <div class="text-sm font-bold text-slate-900 dark:text-white">
                            {{ $customer['name'] ?? ($invoice->customer?->name ?? 'Walk-in Customer / کیش کسٹمر') }}
                        </div>
                        @if (!empty($customer['phone']) || !empty($invoice->customer?->phone))
                            <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                                {{ __('sales.invoice_show.phone') }}: {{ $customer['phone'] ?? $invoice->customer->phone }}
                            </div>
                        @endif
                        @if (!empty($customer['ntn']) || !empty($invoice->customer?->ntn_number))
                            <div class="text-xs text-slate-600 dark:text-slate-400">
                                NTN: {{ $customer['ntn'] ?? $invoice->customer->ntn_number }}
                            </div>
                        @endif
                        @if (!empty($customer['vehicle_number']) || !empty($invoice->vehicle?->registration_number))
                            <div class="mt-2 flex items-center justify-center gap-1.5 border-t border-slate-200 pt-2 dark:border-slate-700">
                                <span class="text-xs">🚗 {{ __('sales.invoice_show.vehicle') }}:</span>
                                <span class="rounded border border-slate-300 bg-white px-1.5 py-0.5 font-mono text-xs font-bold dark:border-slate-700 dark:bg-slate-800">
                                    {{ $customer['vehicle_number'] ?? $invoice->vehicle->registration_number }}
                                </span>
                            </div>
                        @endif
                    </div>

                    <div class="rounded-xl border border-white/60 bg-white/50 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-800/30">
                        <div class="mb-2 flex items-center justify-between text-xs font-bold uppercase text-slate-500">
                            <span>{{ __('sales.invoice_show.payment_operation') }}</span>
                            <span>⛽</span>
                        </div>
                        <div class="space-y-1.5 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500">{{ __('sales.invoice_show.payment_method') }}</span>
                                <span class="font-semibold capitalize text-slate-800 dark:text-slate-200">
                                    {{ $invoice->payment_method ?? 'Cash' }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">{{ __('sales.invoice_show.status') }}</span>
                                <span class="font-bold {{ $invoice->isPaid() ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ strtoupper($invoice->status) }}
                                </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-slate-500">{{ __('sales.invoice_show.attendant') }}</span>
                                <span class="font-medium text-slate-700 dark:text-slate-300">
                                    {{ $invoice->user?->name ?? 'Cashier' }}
                                </span>
                            </div>
                            @if ($invoice->shift_id)
                                <div class="flex justify-between">
                                    <span class="text-slate-500">{{ __('sales.invoice_show.shift_ref') }}</span>
                                    <span class="tabular font-mono text-slate-700 dark:text-slate-300">#{{ $invoice->shift_id }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Items Table --}}
                <div class="p-6">
                    <div class="table-3d">
                        <table class="text-xs">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ __('sales.invoice_show.item_product') }}</th>
                                    <th>{{ __('sales.invoice_show.meter_readings') }}</th>
                                    <th>{{ __('sales.invoice_show.quantity_litres') }}</th>
                                    <th>{{ __('sales.invoice_show.rate_rs') }}</th>
                                    <th>{{ __('sales.invoice_show.total_rs') }}</th>
                                </tr>
                            </thead>
                            <tbody class="font-medium">
                                @forelse ($invoice->items as $idx => $item)
                                    <tr>
                                        <td class="text-slate-400">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="font-bold text-slate-900 dark:text-white">
                                                {{ $item->fuelProduct?->name ?? $item->item_description }}
                                            </div>
                                            @if ($item->nozzle)
                                                <div class="text-[10px] text-slate-500">
                                                    {{ __('sales.invoice_show.nozzle') }} {{ $item->nozzle->nozzle_number ?? $item->nozzle_id }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="tabular font-mono text-slate-500">
                                            @if ($item->meter_start && $item->meter_end)
                                                {{ number_format((float)$item->meter_start, 1) }} → {{ number_format((float)$item->meter_end, 1) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="tabular font-mono font-bold text-slate-800 dark:text-slate-200">
                                            {{ number_format((float)$item->quantity, 3) }} L
                                        </td>
                                        <td class="tabular font-mono text-slate-700 dark:text-slate-300">
                                            {{ number_format((float)$item->unit_price, 2) }}
                                        </td>
                                        <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                            {{ \App\Support\AmountInWords::formatLakh($item->total_amount, false, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="py-8 text-center text-slate-400">{{ __('sales.invoice_show.no_items') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Totals Summary Section --}}
                    <div class="mt-6 grid grid-cols-1 items-start gap-6 md:grid-cols-2">
                        {{-- Left: Urdu Amount in Words --}}
                        <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4 text-center dark:border-slate-800 dark:bg-slate-800/40">
                            <div class="mb-1 text-xs font-bold uppercase text-slate-500">
                                {{ __('sales.invoice_show.amount_in_words') }}
                            </div>
                            <div class="font-urdu text-base font-bold leading-relaxed text-vital-primary">
                                {{ $invoice->amount_in_words_ur ?: \App\Support\AmountInWords::toUrdu($invoice->total_amount) }}
                            </div>
                            <div class="mt-1 text-xs text-slate-600 dark:text-slate-400">
                                {{ $invoice->amount_in_words_en ?: \App\Support\AmountInWords::toEnglish($invoice->total_amount) }}
                            </div>
                        </div>

                        {{-- Right: Financial Totals Box --}}
                        <div class="overflow-hidden rounded-xl border-2 border-vital-primary shadow-3d">
                            <div class="divide-y divide-slate-100 text-xs dark:divide-slate-800">
                                <div class="flex justify-between p-2.5 px-4">
                                    <span class="text-slate-500">{{ __('sales.invoice_show.subtotal') }}</span>
                                    <span class="tabular font-semibold">{{ \App\Support\AmountInWords::formatLakh($invoice->subtotal, true, 2) }}</span>
                                </div>
                                @if ((float)$invoice->pos_fee > 0)
                                    <div class="flex justify-between p-2.5 px-4">
                                        <span class="text-slate-500">{{ __('sales.invoice_show.pos_fee') }}</span>
                                        <span class="tabular">{{ \App\Support\AmountInWords::formatLakh($invoice->pos_fee, true, 2) }}</span>
                                    </div>
                                @endif
                                @if ((float)$invoice->tax_amount > 0)
                                    <div class="flex justify-between p-2.5 px-4">
                                        <span class="text-slate-500">{{ __('sales.invoice_show.tax_pra') }}</span>
                                        <span class="tabular">{{ \App\Support\AmountInWords::formatLakh($invoice->tax_amount, true, 2) }}</span>
                                    </div>
                                @endif
                                @if ((float)$invoice->discount_amount > 0)
                                    <div class="flex justify-between p-2.5 px-4 text-emerald-600">
                                        <span>{{ __('sales.invoice_show.discount') }}</span>
                                        <span class="tabular">- {{ \App\Support\AmountInWords::formatLakh($invoice->discount_amount, true, 2) }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between bg-vital-primary p-3 px-4 text-sm font-black text-white">
                                    <span>{{ __('sales.invoice_show.total_amount') }}</span>
                                    <span class="tabular">{{ \App\Support\AmountInWords::formatLakh($invoice->total_amount, true, 2) }}</span>
                                </div>
                                <div class="flex justify-between bg-slate-50 p-2.5 px-4 font-semibold text-emerald-600 dark:bg-slate-800/40">
                                    <span>{{ __('sales.invoice_show.paid_label') }}</span>
                                    <span class="tabular">{{ \App\Support\AmountInWords::formatLakh($invoice->paid_amount, true, 2) }}</span>
                                </div>
                                @if ((float)$invoice->balance_due > 0)
                                    <div class="flex justify-between bg-amber-50 p-2.5 px-4 font-bold text-amber-700 dark:bg-amber-950/30">
                                        <span>{{ __('sales.invoice_show.balance_due') }}</span>
                                        <span class="tabular">{{ \App\Support\AmountInWords::formatLakh($invoice->balance_due, true, 2) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Customer Udhaar Balance if any --}}
                    @if ((float)$invoice->closing_balance > 0 || (float)$invoice->previous_balance > 0)
                        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-center text-xs text-amber-900 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                            <div>
                                <strong>{{ __('sales.invoice_show.running_balance') }}</strong>
                                {{ __('sales.invoice_show.previous') }}: {{ \App\Support\AmountInWords::formatLakh($invoice->previous_balance, true, 2) }} + {{ __('sales.invoice_show.current_due') }}: {{ \App\Support\AmountInWords::formatLakh($invoice->balance_due, true, 2) }}
                            </div>
                            <div class="tabular text-sm font-black text-amber-800 dark:text-amber-300">
                                {{ __('sales.invoice_show.total_outstanding') }}: {{ \App\Support\AmountInWords::formatLakh($invoice->closing_balance, true, 2) }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Right Column: QR Code, Snapshot & Security Details --}}
        <div class="space-y-6">
            {{-- QR Code Card --}}
            <div class="glass-card card-3d p-6 text-center">
                <div class="mb-3 text-xs font-bold uppercase text-slate-500">
                    {{ __('sales.invoice_show.qr_heading') }}
                </div>
                <div class="mx-auto inline-block rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="flex h-32 w-32 items-center justify-center">
                        {!! $qrSvg !!}
                    </div>
                </div>
                <div class="mt-3 text-xs text-slate-600 dark:text-slate-400">
                    {{ __('sales.invoice_show.qr_hint') }}
                </div>
                <div class="mt-4 border-t border-slate-100 pt-4 dark:border-slate-800">
                    <a href="{{ route('invoice.verify', $invoice->hash) }}" target="_blank"
                       class="flex items-center justify-center gap-1 text-xs font-bold text-vital-primary hover:underline">
                        <span>{{ __('sales.invoice_show.open_verification') }}</span>
                        <span>↗</span>
                    </a>
                </div>
            </div>

            {{-- Cryptographic Snapshot Card --}}
            <div class="glass-card card-3d p-6">
                <div class="mb-3 flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase text-slate-500">{{ __('sales.invoice_show.snapshot_integrity') }}</h3>
                    @if ($snapshotVerified)
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-600">
                            <span>✓</span> {{ __('sales.invoice_show.valid') }}
                        </span>
                    @else
                        <span class="text-[11px] font-bold text-amber-600">{{ __('sales.invoice_show.unverified') }}</span>
                    @endif
                </div>

                <div class="space-y-2 text-xs">
                    <div>
                        <div class="font-mono text-[10px] uppercase text-slate-400">{{ __('sales.invoice_show.invoice_hash') }}</div>
                        <div class="tabular mt-0.5 break-all rounded bg-slate-50 p-1.5 font-mono text-[11px] text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                            {{ $invoice->hash }}
                        </div>
                    </div>

                    @if ($invoice->snapshot?->snapshot_hash)
                        <div>
                            <div class="font-mono text-[10px] uppercase text-slate-400">{{ __('sales.invoice_show.snapshot_hash') }}</div>
                            <div class="tabular mt-0.5 break-all rounded border border-emerald-200 bg-emerald-50 p-1.5 font-mono text-[11px] text-emerald-700 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-400">
                                {{ $invoice->snapshot->snapshot_hash }}
                            </div>
                        </div>
                    @endif

                    <div class="mt-2 text-[11px] text-slate-500">
                        🔒 {{ __('sales.invoice_show.immutability') }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
