<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>
        @if ($verified)
            ✓ {{ __('sales.invoice_verify.title_verified') }} — {{ $invoice->invoice_number }}
        @else
            ⚠️ {{ __('sales.invoice_verify.title_failed') }}
        @endif
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .font-urdu {
            font-family: "Noto Nastaliq Urdu", "Jameel Noori Nastaleeq", "Urdu Typesetting", Tahoma, sans-serif;
            direction: rtl;
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 antialiased py-8 px-4 flex flex-col items-center justify-center">

    <div class="w-full max-w-2xl bg-white rounded-2xl shadow-xl border border-slate-200/80 overflow-hidden">
        {{-- Header Bar --}}
        <div class="bg-[#D71920] p-6 text-white text-center border-b-4 border-[#A30F15]">
            <div class="inline-flex items-center justify-center w-14 h-14 rounded-full bg-white text-[#D71920] shadow-md mb-3">
                <svg viewBox="0 0 100 100" width="38" height="38">
                    <circle cx="50" cy="50" r="46" fill="#D71920" />
                    <circle cx="50" cy="50" r="39" fill="#FFFFFF" />
                    <polygon points="50,16 78,74 65,74 50,42 35,74 22,74" fill="#D71920" />
                    <circle cx="50" cy="74" r="5" fill="#D71920" />
                </svg>
            </div>
            <h1 class="text-xl sm:text-2xl font-black uppercase tracking-tight">
                {{ $station['station_name_en'] ?? 'MEHAR FILLING STATION' }}
            </h1>
            <h2 class="text-lg font-bold font-urdu mt-1 text-red-100">
                {{ $station['station_name_ur'] ?? 'مہر فلنگ اسٹیشن' }}
            </h2>
            <p class="text-xs text-red-100 mt-1">
                {{ $station['omc_brand'] ?? 'Vital Petroleum Pvt. Ltd. (Vital Petrol Branch • وائٹل پیٹرول)' }}
            </p>
        </div>

        @if ($verified && $invoice)
            {{-- Verified Badge --}}
            <div class="bg-emerald-50 border-b border-emerald-100 p-4 text-center">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-600 text-white font-black text-xs uppercase tracking-wider shadow-sm mb-1">
                    <span>✓</span>
                    <span>{{ __('sales.invoice_verify.verified_badge') }}</span>
                </div>
                <div class="text-sm font-bold text-emerald-800 font-urdu mt-1">
                    {{ __('sales.invoice_verify.verified_urdu') }}
                </div>
            </div>

            {{-- Invoice Key Facts --}}
            <div class="p-6 space-y-6">
                {{-- Bill Summary Box --}}
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs">
                    <div>
                        <div class="text-slate-400 uppercase font-semibold text-[10px]">{{ __('sales.invoice_verify.invoice_number') }}</div>
                        <div class="font-bold text-slate-900 text-sm mt-0.5">{{ $invoice->invoice_number }}</div>
                    </div>
                    <div>
                        <div class="text-slate-400 uppercase font-semibold text-[10px]">{{ __('sales.invoice_verify.date_time') }}</div>
                        <div class="font-bold text-slate-800 mt-0.5">{{ $invoice->invoice_date->format('d M Y') }}</div>
                        <div class="text-slate-500 text-[10px]">{{ $invoice->invoice_date->format('h:i A') }}</div>
                    </div>
                    <div>
                        <div class="text-slate-400 uppercase font-semibold text-[10px]">{{ __('sales.invoice_verify.status') }}</div>
                        <div class="mt-0.5">
                            @if ($invoice->isPaid())
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-800">
                                    ✓ {{ __('sales.invoice_verify.paid') }}
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-800">
                                    {{ __('sales.invoice_verify.udhaar_credit') }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-slate-400 uppercase font-semibold text-[10px]">{{ __('sales.invoice_verify.payment_method') }}</div>
                        <div class="font-bold text-slate-800 capitalize mt-0.5">{{ $invoice->payment_method ?? 'Cash' }}</div>
                    </div>
                </div>

                {{-- Customer & Vehicle --}}
                <div class="p-4 rounded-xl border border-slate-200 space-y-2 text-xs">
                    <div class="flex justify-between items-center pb-2 border-b border-slate-100">
                        <span class="text-slate-500 font-semibold">{{ __('sales.invoice_verify.customer') }}</span>
                        <span class="font-bold text-slate-900 text-sm">
                            {{ $customer['name'] ?? ($invoice->customer?->name ?? 'Walk-in Customer / کیش کسٹمر') }}
                        </span>
                    </div>
                    @if (!empty($customer['phone']) || !empty($invoice->customer?->phone))
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">{{ __('sales.invoice_verify.phone') }}</span>
                            <span class="font-medium text-slate-700">{{ $customer['phone'] ?? $invoice->customer->phone }}</span>
                        </div>
                    @endif
                    @if (!empty($customer['vehicle_number']) || !empty($invoice->vehicle?->registration_number))
                        <div class="flex justify-between items-center">
                            <span class="text-slate-500">{{ __('sales.invoice_verify.vehicle') }}</span>
                            <span class="font-mono font-bold text-slate-900 px-2 py-0.5 bg-slate-100 rounded">
                                🚗 {{ $customer['vehicle_number'] ?? $invoice->vehicle->registration_number }}
                            </span>
                        </div>
                    @endif
                </div>

                {{-- Fuel Items Table --}}
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-bold">
                                <th class="py-2.5 px-3">{{ __('sales.invoice_verify.item_product') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('sales.invoice_verify.quantity') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('sales.invoice_verify.rate') }}</th>
                                <th class="py-2.5 px-3 text-right">{{ __('sales.invoice_verify.amount') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @foreach ($invoice->items as $item)
                                <tr>
                                    <td class="py-2.5 px-3">
                                        <div class="font-bold text-slate-900">{{ $item->fuelProduct?->name ?? $item->item_description }}</div>
                                    </td>
                                    <td class="py-2.5 px-3 text-right font-mono">{{ number_format((float)$item->quantity, 3) }} L</td>
                                    <td class="py-2.5 px-3 text-right font-mono">Rs. {{ number_format((float)$item->unit_price, 2) }}</td>
                                    <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">
                                        {{ \App\Support\AmountInWords::formatLakh($item->total_amount, true, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Grand Total & Urdu Words --}}
                <div class="p-4 rounded-xl bg-red-50 border-2 border-[#D71920] text-center">
                    <div class="text-xs uppercase font-bold text-[#A30F15] tracking-wider">
                        {{ __('sales.invoice_verify.total_invoiced') }}
                    </div>
                    <div class="text-3xl font-black text-[#D71920] my-1">
                        {{ \App\Support\AmountInWords::formatLakh($invoice->total_amount, true, 2) }}
                    </div>
                    <div class="text-base font-bold font-urdu text-slate-800 mt-2">
                        {{ $invoice->amount_in_words_ur ?: \App\Support\AmountInWords::toUrdu($invoice->total_amount) }}
                    </div>
                    <div class="text-xs text-slate-500 mt-1">
                        {{ $invoice->amount_in_words_en ?: \App\Support\AmountInWords::toEnglish($invoice->total_amount) }}
                    </div>
                </div>

                {{-- Cryptographic Verification Seal --}}
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-center space-y-1 text-xs">
                    <div class="flex items-center justify-center gap-1 font-bold text-emerald-700">
                        <span>🔒</span>
                        <span>{{ __('sales.invoice_verify.sealed') }}</span>
                    </div>
                    <div class="font-mono text-[10px] text-slate-500 break-all">
                        {{ __('sales.invoice_verify.hash_label') }}: {{ $invoice->hash }}
                    </div>
                    <div class="text-[10px] text-slate-400">
                        {{ __('sales.invoice_verify.compliance') }}
                    </div>
                </div>
            </div>
        @else
            {{-- Unverified / Invalid Bill Notice --}}
            <div class="p-8 text-center space-y-4">
                <div class="w-16 h-16 rounded-full bg-red-100 text-red-600 flex items-center justify-center mx-auto text-2xl font-bold shadow-sm">
                    ⚠️
                </div>
                <h2 class="text-xl font-black text-slate-900">
                    {{ __('sales.invoice_verify.failed_heading') }}
                </h2>
                <p class="text-sm text-slate-600 max-w-md mx-auto">
                    {{ __('sales.invoice_verify.not_found') }}
                </p>
                <div class="font-mono text-xs text-slate-500 bg-slate-100 p-2 rounded-lg max-w-sm mx-auto break-all">
                    {{ __('sales.invoice_verify.code') }}: {{ $hash }}
                </div>
                <div class="pt-4 border-t border-slate-100 text-xs text-slate-500">
                    {{ __('sales.invoice_verify.contact_text') }}
                    <div class="font-bold text-slate-800 mt-1">
                        📞 {{ $station['phone'] ?? '0300-4342343' }} (Muhammad Rizwan Aslam)
                    </div>
                </div>
            </div>
        @endif

        {{-- Footer Band --}}
        <div class="bg-slate-900 text-slate-400 text-center py-4 px-6 text-xs">
            {{ __('sales.invoice_verify.footer_band') }}
        </div>
    </div>

</body>
</html>
