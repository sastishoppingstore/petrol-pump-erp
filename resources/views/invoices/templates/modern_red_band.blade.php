<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Invoice {{ $invoice->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
            size: a4 portrait;
        }
        body {
            font-family: DejaVu Sans, sans-serif;
            color: {{ $theme['text_color'] ?? '#1B1B1B' }};
            font-size: 11px;
            margin: 0;
            padding: 0;
            background: #ffffff;
        }
        .header-table {
            width: 100%;
            background-color: {{ $theme['header_bg'] ?? '#D71920' }};
            color: {{ $theme['header_text'] ?? '#FFFFFF' }};
            padding: 18px 24px;
            border-bottom: 4px solid {{ $theme['dark_red_color'] ?? '#A30F15' }};
        }
        .header-title {
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .header-sub {
            font-size: 11px;
            margin-top: 3px;
        }
        .inv-badge {
            text-align: right;
        }
        .inv-badge-pill {
            background: #ffffff;
            color: {{ $theme['primary_color'] ?? '#D71920' }};
            font-size: 10px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 3px;
        }
        .inv-num {
            font-size: 16px;
            font-weight: bold;
            margin-top: 4px;
        }
        .container {
            padding: 16px 24px;
        }
        .meta-bar {
            width: 100%;
            background: {{ $theme['light_grey_color'] ?? '#F6F6F6' }};
            padding: 6px 10px;
            font-size: 10px;
            border-radius: 4px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
        }
        .info-table {
            width: 100%;
            margin-bottom: 14px;
            border-collapse: separate;
            border-spacing: 10px 0;
        }
        .info-box {
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            vertical-align: top;
            width: 50%;
        }
        .info-header {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            color: #475569;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 4px;
            margin-bottom: 6px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
        }
        .items-table th {
            background: {{ $theme['primary_color'] ?? '#D71920' }};
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 7px 8px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 11px;
        }
        .totals-table {
            width: 100%;
            margin-bottom: 14px;
        }
        .summary-card {
            border: 2px solid {{ $theme['primary_color'] ?? '#D71920' }};
            border-radius: 4px;
            width: 100%;
            border-collapse: collapse;
        }
        .summary-card td {
            padding: 5px 8px;
            font-size: 11px;
        }
        .grand-total-row td {
            background: {{ $theme['primary_color'] ?? '#D71920' }};
            color: #ffffff;
            font-weight: bold;
            font-size: 13px;
        }
        .words-box {
            border: 1px solid #e2e8f0;
            background: #fafafa;
            border-radius: 4px;
            padding: 8px 10px;
            margin-right: 10px;
        }
        .udhaar-bar {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 4px;
            padding: 7px 10px;
            margin-bottom: 12px;
            font-size: 11px;
            color: #92400e;
        }
        .qr-table {
            width: 100%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 8px 10px;
            margin-bottom: 18px;
        }
        .sig-table {
            width: 100%;
            margin-top: 15px;
            margin-bottom: 15px;
        }
        .sig-line {
            border-bottom: 1px dashed #94a3b8;
            margin-bottom: 4px;
            height: 25px;
        }
        .footer-band {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: {{ $theme['footer_bg'] ?? '#D71920' }};
            color: {{ $theme['footer_text'] ?? '#FFFFFF' }};
            padding: 8px 24px;
            font-size: 9.5px;
            border-top: 3px solid {{ $theme['dark_red_color'] ?? '#A30F15' }};
        }
    </style>
</head>
<body>

    {{-- Red Header Band --}}
    <table class="header-table" cellpadding="0" cellspacing="0">
        <tr>
            <td style="width: 65%;">
                <div class="header-title">{{ $station['station_name_en'] ?? 'MEHAR FILLING STATION' }}</div>
                @if ($theme['show_urdu_name'] ?? true)
                    <div style="font-size: 14px; margin-top: 2px;">{{ $station['station_name_ur'] ?? 'مہر فلنگ اسٹیشن' }}</div>
                @endif
                <div class="header-sub">
                    {{ $station['omc_brand'] ?? 'Vital Petroleum Pvt. Ltd. (Vital Petrol Branch • وائٹل پیٹرول)' }}
                </div>
            </td>
            <td class="inv-badge" style="width: 35%;">
                <span class="inv-badge-pill">TAX INVOICE / سیلز انوائس</span>
                <div class="inv-num">{{ $invoice->invoice_number }}</div>
                <div style="font-size: 10px; margin-top: 2px;">{{ $invoice->invoice_date->format('d M Y, h:i A') }}</div>
            </td>
        </tr>
    </table>

    <div class="container">
        {{-- Station Meta Bar --}}
        <table class="meta-bar" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 60%;">
                    📍 {{ $station['address'] ?? 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab, Pakistan' }}
                </td>
                <td style="width: 40%; text-align: right;">
                    📞 {{ $station['phone'] ?? '0300-4342343' }} &nbsp;•&nbsp; 💬 {{ $station['whatsapp'] ?? '0300-4342343' }}
                </td>
            </tr>
        </table>

        {{-- Customer & Sale Information --}}
        @if ($theme['show_customer_box'] ?? true)
            <table class="info-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="info-box">
                        <div class="info-header">Bill To / کسٹمر کی تفصیلات</div>
                        <div><strong>Name:</strong> {{ $customer['name'] ?? ($invoice->customer?->name ?? 'Walk-in Customer / کیش کسٹمر') }}</div>
                        @if (!empty($customer['phone']) || !empty($invoice->customer?->phone))
                            <div><strong>Phone:</strong> {{ $customer['phone'] ?? $invoice->customer->phone }}</div>
                        @endif
                        @if (!empty($customer['ntn']) || !empty($invoice->customer?->ntn_number))
                            <div><strong>NTN/STRN:</strong> {{ $customer['ntn'] ?? $invoice->customer->ntn_number }}</div>
                        @endif
                        @if (($theme['show_vehicle'] ?? true) && (!empty($customer['vehicle_number']) || !empty($invoice->vehicle?->registration_number)))
                            <div style="margin-top: 4px;"><strong>Vehicle:</strong> {{ $customer['vehicle_number'] ?? $invoice->vehicle->registration_number }}</div>
                        @endif
                    </td>
                    <td class="info-box">
                        <div class="info-header">Sale & Attendant Details</div>
                        <div><strong>Payment Method:</strong> {{ ucfirst($invoice->payment_method ?? 'Cash') }}</div>
                        <div><strong>Status:</strong> {{ strtoupper($invoice->status) }}</div>
                        <div><strong>Attendant:</strong> {{ $invoice->user?->name ?? 'Forecourt Cashier' }}</div>
                        @if ($invoice->shift_id)
                            <div><strong>Shift Ref:</strong> #{{ $invoice->shift_id }}</div>
                        @endif
                    </td>
                </tr>
            </table>
        @endif

        {{-- Items Table --}}
        <table class="items-table" cellpadding="0" cellspacing="0">
            <thead>
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Product / Description</th>
                    <th style="width: 15%; text-align: center;">Meters</th>
                    <th style="width: 15%; text-align: right;">Litres</th>
                    <th style="width: 12%; text-align: right;">Rate (Rs.)</th>
                    <th style="width: 13%; text-align: right;">Amount (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $idx => $item)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <strong>{{ $item->fuelProduct?->name ?? $item->item_description }}</strong>
                            @if ($item->nozzle)
                                <span style="font-size: 9px; color: #64748b;">(Nozzle {{ $item->nozzle->nozzle_number ?? $item->nozzle_id }})</span>
                            @endif
                        </td>
                        <td style="text-align: center; font-size: 10px;">
                            @if ($item->meter_start && $item->meter_end)
                                {{ number_format((float)$item->meter_start, 1) }} - {{ number_format((float)$item->meter_end, 1) }}
                            @else
                                —
                            @endif
                        </td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ number_format((float)$item->quantity, 3) }} L
                        </td>
                        <td style="text-align: right;">
                            {{ number_format((float)$item->unit_price, 2) }}
                        </td>
                        <td style="text-align: right; font-weight: bold;">
                            {{ \App\Support\AmountInWords::formatLakh($item->total_amount, false, 2) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Totals Section --}}
        <table class="totals-table" cellpadding="0" cellspacing="0">
            <tr>
                <td style="width: 55%; vertical-align: top;">
                    @if ($theme['show_amount_in_words'] ?? true)
                        <div class="words-box">
                            <div style="font-size: 9px; text-transform: uppercase; font-weight: bold; color: #64748b;">
                                Amount in Words / رقم الفاظ میں:
                            </div>
                            <div style="font-size: 12px; font-weight: bold; color: {{ $theme['dark_red_color'] ?? '#A30F15' }}; margin: 4px 0;">
                                {{ $invoice->amount_in_words_ur ?: \App\Support\AmountInWords::toUrdu($invoice->total_amount) }}
                            </div>
                            <div style="font-size: 10px; color: #475569;">
                                {{ $invoice->amount_in_words_en ?: \App\Support\AmountInWords::toEnglish($invoice->total_amount) }}
                            </div>
                        </div>
                    @endif
                </td>
                <td style="width: 45%; vertical-align: top;">
                    <table class="summary-card" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="color: #64748b;">Subtotal:</td>
                            <td style="text-align: right; font-weight: bold;">{{ \App\Support\AmountInWords::formatLakh($invoice->subtotal, true, 2) }}</td>
                        </tr>
                        @if ((float)$invoice->pos_fee > 0)
                            <tr>
                                <td style="color: #64748b;">POS Fee (SRO 1006):</td>
                                <td style="text-align: right;">{{ \App\Support\AmountInWords::formatLakh($invoice->pos_fee, true, 2) }}</td>
                            </tr>
                        @endif
                        @if ((float)$invoice->tax_amount > 0)
                            <tr>
                                <td style="color: #64748b;">Tax:</td>
                                <td style="text-align: right;">{{ \App\Support\AmountInWords::formatLakh($invoice->tax_amount, true, 2) }}</td>
                            </tr>
                        @endif
                        @if ((float)$invoice->discount_amount > 0)
                            <tr>
                                <td style="color: #16a34a;">Discount:</td>
                                <td style="text-align: right; color: #16a34a;">- {{ \App\Support\AmountInWords::formatLakh($invoice->discount_amount, true, 2) }}</td>
                            </tr>
                        @endif
                        <tr class="grand-total-row">
                            <td>TOTAL PAYABLE:</td>
                            <td style="text-align: right;">{{ \App\Support\AmountInWords::formatLakh($invoice->total_amount, true, 2) }}</td>
                        </tr>
                        <tr>
                            <td style="color: #64748b;">Paid Amount:</td>
                            <td style="text-align: right; font-weight: bold; color: #16a34a;">{{ \App\Support\AmountInWords::formatLakh($invoice->paid_amount, true, 2) }}</td>
                        </tr>
                        @if ((float)$invoice->balance_due > 0)
                            <tr style="background: #fffbeb;">
                                <td style="font-weight: bold; color: #b45309;">Balance Due:</td>
                                <td style="text-align: right; font-weight: bold; color: #b45309;">{{ \App\Support\AmountInWords::formatLakh($invoice->balance_due, true, 2) }}</td>
                            </tr>
                        @endif
                    </table>
                </td>
            </tr>
        </table>

        {{-- Udhaar Balance if any --}}
        @if (($theme['show_udhaar_balance'] ?? true) && ((float)$invoice->closing_balance > 0 || (float)$invoice->previous_balance > 0))
            <div class="udhaar-bar">
                <strong>Customer Udhaar Balance:</strong> Previous: {{ \App\Support\AmountInWords::formatLakh($invoice->previous_balance, true, 2) }} + Current Due: {{ \App\Support\AmountInWords::formatLakh($invoice->balance_due, true, 2) }} = <strong>Total Outstanding: {{ \App\Support\AmountInWords::formatLakh($invoice->closing_balance, true, 2) }}</strong>
            </div>
        @endif

        {{-- QR Code Verification Row --}}
        @if ($theme['show_qr_code'] ?? true)
            <table class="qr-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 75px; vertical-align: middle;">
                        {!! $qrSvg !!}
                    </td>
                    <td style="vertical-align: middle; padding-left: 10px;">
                        <div style="font-weight: bold; color: #166534; font-size: 10px;">
                            ✓ OFFICIAL DIGITAL TAX INVOICE • اصلی بل کی تصدیق شدہ
                        </div>
                        <div style="font-size: 9.5px; color: #475569; margin-top: 2px;">
                            Scan QR code to verify this genuine invoice on the Mehar Filling Station portal.
                        </div>
                        <div style="font-family: monospace; font-size: 9px; color: #64748b; margin-top: 2px;">
                            Code: {{ $invoice->hash }}
                        </div>
                    </td>
                </tr>
            </table>
        @endif

        {{-- Signatures --}}
        @if ($theme['show_signatures'] ?? true)
            <table class="sig-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 45%; text-align: center;">
                        <div class="sig-line"></div>
                        <div style="font-size: 10px; font-weight: bold; color: #475569;">Customer Signature / دستخط کسٹمر</div>
                    </td>
                    <td style="width: 10%;"></td>
                    <td style="width: 45%; text-align: center;">
                        <div class="sig-line"></div>
                        <div style="font-size: 10px; font-weight: bold; color: #475569;">For Mehar Filling Station / دستخط و مہر</div>
                    </td>
                </tr>
            </table>
        @endif
    </div>

    {{-- Footer Band --}}
    <table class="footer-band" cellpadding="0" cellspacing="0">
        <tr>
            <td>Thank you for your patronage. Drive safely! • ہماری سروس استعمال کرنے کا شکریہ!</td>
            <td style="text-align: right;">Mehar Filling Station (Vital Petroleum) • Powered by Wafa Tech</td>
        </tr>
    </table>

</body>
</html>
