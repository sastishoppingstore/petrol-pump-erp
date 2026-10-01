<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Invoice {{ $invoice->invoice_number }} — {{ $station['station_name_en'] ?? 'Mehar Filling Station' }}</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f1f5f9;
            color: {{ $theme['text_color'] ?? '#1B1B1B' }};
            font-size: 13px;
            line-height: 1.4;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        .font-urdu {
            font-family: "Noto Nastaliq Urdu", "Jameel Noori Nastaleeq", "Urdu Typesetting", Tahoma, sans-serif;
            direction: rtl;
        }

        .no-print {
            display: block;
        }

        @media print {
            body {
                background: #ffffff !important;
            }
            .no-print {
                display: none !important;
            }
            .a4-page {
                box-shadow: none !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
                border: none !important;
                padding: 0 !important;
            }
            @page {
                size: A4 portrait;
                margin: 8mm 10mm;
            }
        }

        .a4-page {
            width: 210mm;
            min-height: 297mm;
            margin: 20px auto;
            background: {{ $theme['background_color'] ?? '#FFFFFF' }};
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: 4px;
            position: relative;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* Toolbar */
        .toolbar {
            max-width: 210mm;
            margin: 15px auto 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 8px 16px;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }

        .btn-primary {
            background-color: {{ $theme['primary_color'] ?? '#D71920' }};
            color: #ffffff;
        }
        .btn-primary:hover {
            background-color: {{ $theme['dark_red_color'] ?? '#A30F15' }};
        }

        .btn-outline {
            background: #ffffff;
            border-color: #cbd5e1;
            color: #334155;
        }
        .btn-outline:hover {
            background: #f8fafc;
        }

        /* Header Band */
        .header-band {
            background: {{ $theme['header_bg'] ?? '#D71920' }};
            color: {{ $theme['header_text'] ?? '#FFFFFF' }};
            padding: 16px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 4px solid {{ $theme['dark_red_color'] ?? '#A30F15' }};
        }

        .brand-section {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .vital-logo-badge {
            width: 58px;
            height: 58px;
            background: #ffffff;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
            padding: 4px;
        }

        .station-titles h1 {
            font-size: 20px;
            font-weight: 900;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            line-height: 1.1;
        }

        .station-titles h2 {
            font-size: 17px;
            font-weight: 700;
            line-height: 1.2;
            margin-top: 2px;
        }

        .station-titles .franchise-sub {
            font-size: 11px;
            opacity: 0.95;
            margin-top: 3px;
            font-weight: 500;
        }

        .invoice-badge-box {
            text-align: right;
        }

        .invoice-type-pill {
            display: inline-block;
            background: #ffffff;
            color: {{ $theme['primary_color'] ?? '#D71920' }};
            font-size: 11px;
            font-weight: 800;
            padding: 3px 10px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 4px;
        }

        .invoice-num {
            font-size: 18px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .invoice-date {
            font-size: 11px;
            opacity: 0.9;
            margin-top: 2px;
        }

        /* Content Area */
        .content-area {
            padding: 20px 24px;
            flex-grow: 1;
        }

        /* Station contact meta bar */
        .station-meta-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            color: #475569;
            background: {{ $theme['light_grey_color'] ?? '#F6F6F6' }};
            border-radius: 6px;
            padding: 8px 14px;
            margin-bottom: 16px;
            border: 1px solid #e2e8f0;
        }

        /* Customer & Info Grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            margin-bottom: 18px;
        }

        .info-card {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            background: #ffffff;
            overflow: hidden;
        }

        .info-card-header {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 6px 12px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            color: #334155;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-card-body {
            padding: 10px 12px;
            font-size: 12px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }

        .info-label {
            color: #64748b;
        }

        .info-val {
            font-weight: 600;
            color: #0f172a;
        }

        /* Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .items-table th {
            background: {{ $theme['primary_color'] ?? '#D71920' }};
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 10px;
            border: 1px solid {{ $theme['primary_color'] ?? '#D71920' }};
            letter-spacing: 0.3px;
        }

        .items-table td {
            padding: 10px;
            border: 1px solid #e2e8f0;
            font-size: 12px;
        }

        .items-table tbody tr:nth-child(even) {
            background-color: #fafafa;
        }

        /* Red Total Box & Layout */
        .totals-section {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 16px;
            margin-bottom: 18px;
        }

        .words-box {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px;
            background: #fafafa;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .words-urdu {
            font-size: 15px;
            font-weight: 700;
            color: {{ $theme['dark_red_color'] ?? '#A30F15' }};
            margin-bottom: 6px;
            line-height: 1.5;
        }

        .words-en {
            font-size: 11px;
            color: #475569;
            font-weight: 600;
        }

        .summary-card {
            border: 2px solid {{ $theme['primary_color'] ?? '#D71920' }};
            border-radius: 6px;
            overflow: hidden;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 6px 12px;
            font-size: 12px;
            border-bottom: 1px solid #f1f5f9;
        }

        .summary-row.grand-total {
            background: {{ $theme['primary_color'] ?? '#D71920' }};
            color: #ffffff;
            font-size: 14px;
            font-weight: 900;
            border-bottom: none;
            padding: 8px 12px;
        }

        /* Udhaar / Customer Balance Box */
        .udhaar-card {
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 12px;
        }

        /* QR & Verification Box */
        .verification-row {
            display: flex;
            align-items: center;
            gap: 16px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 24px;
        }

        .qr-wrapper {
            flex-shrink: 0;
            width: 85px;
            height: 85px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2px;
        }

        .qr-wrapper svg {
            width: 100%;
            height: 100%;
        }

        .verify-text {
            font-size: 11px;
            color: #475569;
            line-height: 1.5;
        }

        .verify-text .verified-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 700;
            color: #166534;
            background: #dcfce7;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 4px;
        }

        /* FBR Fiscal Box */
        .fbr-row {
            display: flex;
            align-items: center;
            gap: 16px;
            background: #f0fdf4;
            border: 2px solid #16a34a;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 24px;
        }

        .fbr-row .qr-wrapper {
            border-color: #16a34a;
        }

        .fbr-badge {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            font-weight: 800;
            color: #166534;
            background: #bbf7d0;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 11px;
            margin-bottom: 4px;
            letter-spacing: 0.4px;
        }

        .fbr-number {
            font-family: monospace;
            font-size: 15px;
            font-weight: 800;
            color: #14532d;
        }

        /* Signatures */
        .signatures-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            margin-top: 10px;
            margin-bottom: 14px;
        }

        .sig-block {
            text-align: center;
        }

        .sig-line {
            border-bottom: 1.5px dashed #94a3b8;
            margin-bottom: 6px;
            height: 38px;
        }

        .sig-label {
            font-size: 11px;
            font-weight: 700;
            color: #475569;
        }

        /* Footer Band */
        .footer-band {
            background: {{ $theme['footer_bg'] ?? '#D71920' }};
            color: {{ $theme['footer_text'] ?? '#FFFFFF' }};
            padding: 10px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 10.5px;
            font-weight: 500;
            border-top: 3px solid {{ $theme['dark_red_color'] ?? '#A30F15' }};
        }
    </style>
</head>
<body>

    @php
        $fbr = $fbrInvoice ?? ($invoice->fbrInvoice ?? null);
        $isFbr = $fbr && ! empty($fbr->fiscal_number);
    @endphp

    {{-- Screen Action Toolbar (hidden on print) --}}
    <div class="toolbar no-print">
        <div style="display: flex; align-items: center; gap: 8px;">
            <a href="{{ route('invoices.show', $invoice) }}" class="btn btn-outline">
                ← Back to Details
            </a>
            <span style="font-size: 12px; color: #64748b;">
                Invoice #<strong>{{ $invoice->invoice_number }}</strong>
            </span>
        </div>
        <div style="display: flex; align-items: center; gap: 8px;">
            <button onclick="window.print()" class="btn btn-primary">
                🖨️ Print A4 Invoice
            </button>
            <a href="{{ route('invoices.thermal', ['invoice' => $invoice, 'size' => '80mm']) }}" target="_blank" class="btn btn-outline">
                🧾 Thermal 80mm
            </a>
            <a href="{{ route('invoices.pdf', $invoice) }}" class="btn btn-outline">
                ⬇️ Download PDF
            </a>
        </div>
    </div>

    {{-- A4 Page Container --}}
    <div class="a4-page">
        <div>
            {{-- 1. Full Red Header Band --}}
            <header class="header-band">
                <div class="brand-section">
                    {{-- Vital Petroleum SVG Emblem --}}
                    @if ($theme['show_logo'] ?? true)
                        <div class="vital-logo-badge">
                            <svg viewBox="0 0 100 100" width="48" height="48">
                                <circle cx="50" cy="50" r="46" fill="#D71920" />
                                <circle cx="50" cy="50" r="39" fill="#FFFFFF" />
                                <polygon points="50,16 78,74 65,74 50,42 35,74 22,74" fill="#D71920" />
                                <circle cx="50" cy="74" r="5" fill="#D71920" />
                            </svg>
                        </div>
                    @endif

                    <div class="station-titles">
                        <h1>{{ strtoupper($station['station_name_en'] ?? 'MEHAR FILLING STATION') }}</h1>
                        @if ($theme['show_urdu_name'] ?? true)
                            <h2 class="font-urdu">{{ $station['station_name_ur'] ?? 'مہر فلنگ اسٹیشن' }}</h2>
                        @endif
                        <div class="franchise-sub">
                            {{ strtoupper($station['omc_brand'] ?? 'Vital Petroleum Pvt. Ltd. (Vital Petrol Branch • وائٹل پیٹرول)') }}
                        </div>
                    </div>
                </div>

                <div class="invoice-badge-box">
                    <span class="invoice-type-pill">{{ $isFbr ? 'FBR TAX INVOICE / ایف بی آر انوائس' : 'INVOICE / بل' }}</span>
                    <div class="invoice-num">{{ $invoice->invoice_number }}</div>
                    <div class="invoice-date">
                        {{ $invoice->invoice_date->format('d M Y, h:i A') }}
                    </div>
                </div>
            </header>

            {{-- 2. Station Contact Meta Bar --}}
            <div style="padding: 12px 24px 0 24px;">
                <div class="station-meta-bar">
                    <div>
                        📍 {{ $station['address'] ?? 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab, Pakistan' }}
                    </div>
                    <div>
                        📞 {{ $station['phone'] ?? '0300-4342343' }} &nbsp;•&nbsp; 💬 {{ $station['whatsapp'] ?? '0300-4342343' }}
                    </div>
                </div>
            </div>

            {{-- Content Area --}}
            <main class="content-area">
                {{-- 3. Customer Box & Invoice Metadata --}}
                @if ($theme['show_customer_box'] ?? true)
                    <div class="info-grid">
                        {{-- Bill To / Customer --}}
                        <div class="info-card">
                            <div class="info-card-header">
                                <span>Bill To / خریدار</span>
                                <span class="font-urdu">کسٹمر کی تفصیلات</span>
                            </div>
                            <div class="info-card-body">
                                <div class="info-row">
                                    <span class="info-label">Customer Name:</span>
                                    <span class="info-val">{{ $customer['name'] ?? ($invoice->customer?->name ?? 'Walk-in Customer / کیش کسٹمر') }}</span>
                                </div>
                                @if (!empty($customer['phone']) || !empty($invoice->customer?->phone))
                                    <div class="info-row">
                                        <span class="info-label">Phone / فون:</span>
                                        <span class="info-val">{{ $customer['phone'] ?? $invoice->customer->phone }}</span>
                                    </div>
                                @endif
                                @if (!empty($customer['ntn']) || !empty($invoice->customer?->ntn_number))
                                    <div class="info-row">
                                        <span class="info-label">NTN / STRN:</span>
                                        <span class="info-val">{{ $customer['ntn'] ?? $invoice->customer->ntn_number }}</span>
                                    </div>
                                @endif
                                @if (($theme['show_vehicle'] ?? true) && (!empty($customer['vehicle_number']) || !empty($invoice->vehicle?->registration_number)))
                                    <div class="info-row" style="margin-top: 4px; padding-top: 4px; border-top: 1px dashed #e2e8f0;">
                                        <span class="info-label">🚗 Vehicle No:</span>
                                        <span class="info-val" style="font-family: monospace; font-size: 13px;">
                                            {{ $customer['vehicle_number'] ?? $invoice->vehicle->registration_number }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Invoice / Transaction Details --}}
                        <div class="info-card">
                            <div class="info-card-header">
                                <span>Sale & Dispenser Details</span>
                                <span class="font-urdu">ڈسپنسر و شفٹ</span>
                            </div>
                            <div class="info-card-body">
                                <div class="info-row">
                                    <span class="info-label">Payment Method:</span>
                                    <span class="info-val" style="text-transform: capitalize;">
                                        {{ $invoice->payment_method ?? 'Cash' }}
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Status / کیفیت:</span>
                                    <span class="info-val" style="color: {{ $invoice->isPaid() ? '#16a34a' : '#d97706' }};">
                                        {{ strtoupper($invoice->status) }}
                                    </span>
                                </div>
                                <div class="info-row">
                                    <span class="info-label">Attendant / آپریٹر:</span>
                                    <span class="info-val">{{ $invoice->user?->name ?? 'Forecourt Cashier' }}</span>
                                </div>
                                @if ($invoice->shift_id)
                                    <div class="info-row">
                                        <span class="info-label">Shift Ref:</span>
                                        <span class="info-val">#{{ $invoice->shift_id }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 4. Fuel & Lubricant Items Table --}}
                <table class="items-table">
                    <thead>
                        <tr>
                            <th style="width: 5%; text-align: center;">#</th>
                            <th style="width: 35%;">Item & Fuel Product / تفصیل پراڈکٹ</th>
                            <th style="width: 15%; text-align: center;">Meter Readings</th>
                            <th style="width: 15%; text-align: right;">Litres / Qty (لیٹرز)</th>
                            <th style="width: 15%; text-align: right;">Rate / فی لیٹر (Rs.)</th>
                            <th style="width: 15%; text-align: right;">Amount / رقم (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($invoice->items as $idx => $item)
                            <tr>
                                <td style="text-align: center; color: #64748b;">{{ $idx + 1 }}</td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a;">
                                        {{ $item->fuelProduct?->name ?? $item->item_description }}
                                    </div>
                                    @if ($item->nozzle)
                                        <div style="font-size: 10px; color: #64748b;">
                                            Nozzle {{ $item->nozzle->nozzle_number ?? $item->nozzle_id }}
                                        </div>
                                    @endif
                                </td>
                                <td style="text-align: center; font-family: monospace; font-size: 11px; color: #475569;">
                                    @if ($item->meter_start && $item->meter_end)
                                        {{ number_format((float)$item->meter_start, 1) }} → {{ number_format((float)$item->meter_end, 1) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td style="text-align: right; font-weight: 700; font-family: monospace;">
                                    {{ number_format((float)$item->quantity, 3) }} L
                                </td>
                                <td style="text-align: right; font-family: monospace;">
                                    {{ number_format((float)$item->unit_price, 2) }}
                                </td>
                                <td style="text-align: right; font-weight: 700; font-family: monospace;">
                                    {{ \App\Support\AmountInWords::formatLakh($item->total_amount, false, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="text-align: center; color: #64748b; padding: 20px;">
                                    No items recorded.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                {{-- 5. Totals & Urdu Amount in Words --}}
                <div class="totals-section">
                    {{-- Left: Words & Notes --}}
                    <div class="words-box">
                        <div>
                            @if ($theme['show_amount_in_words'] ?? true)
                                <div style="font-size: 11px; text-transform: uppercase; font-weight: 700; color: #64748b; margin-bottom: 2px;">
                                    Amount in Words / رقم الفاظ میں:
                                </div>
                                <div class="words-urdu font-urdu">
                                    {{ $invoice->amount_in_words_ur ?: \App\Support\AmountInWords::toUrdu($invoice->total_amount) }}
                                </div>
                                <div class="words-en">
                                    {{ $invoice->amount_in_words_en ?: \App\Support\AmountInWords::toEnglish($invoice->total_amount) }}
                                </div>
                            @endif
                        </div>

                        @if ($invoice->notes)
                            <div style="margin-top: 10px; font-size: 11px; color: #64748b; border-top: 1px dashed #e2e8f0; padding-top: 6px;">
                                <strong>Notes:</strong> {{ $invoice->notes }}
                            </div>
                        @endif
                    </div>

                    {{-- Right: Financial Summary Box --}}
                    <div class="summary-card">
                        <div class="summary-row">
                            <span style="color: #64748b;">Subtotal (میزان):</span>
                            <span style="font-weight: 700;">{{ \App\Support\AmountInWords::formatLakh($invoice->subtotal, true, 2) }}</span>
                        </div>

                        @if ((float)$invoice->pos_fee > 0)
                            <div class="summary-row">
                                <span style="color: #64748b;">POS Fee (SRO 1006):</span>
                                <span>{{ \App\Support\AmountInWords::formatLakh($invoice->pos_fee, true, 2) }}</span>
                            </div>
                        @endif

                        @if ((float)$invoice->tax_amount > 0)
                            <div class="summary-row">
                                <span style="color: #64748b;">Sales Tax / PRA:</span>
                                <span>{{ \App\Support\AmountInWords::formatLakh($invoice->tax_amount, true, 2) }}</span>
                            </div>
                        @endif

                        @if ((float)$invoice->discount_amount > 0)
                            <div class="summary-row" style="color: #16a34a;">
                                <span>Discount (رعایت):</span>
                                <span>- {{ \App\Support\AmountInWords::formatLakh($invoice->discount_amount, true, 2) }}</span>
                            </div>
                        @endif

                        {{-- Red Grand Total Row --}}
                        <div class="summary-row grand-total">
                            <span>TOTAL PAYABLE (کل رقم):</span>
                            <span>{{ \App\Support\AmountInWords::formatLakh($invoice->total_amount, true, 2) }}</span>
                        </div>

                        <div class="summary-row" style="background: #fafafa;">
                            <span style="color: #64748b;">Paid Amount (وصول):</span>
                            <span style="font-weight: 700; color: #16a34a;">
                                {{ \App\Support\AmountInWords::formatLakh($invoice->paid_amount, true, 2) }}
                            </span>
                        </div>

                        @if ((float)$invoice->balance_due > 0)
                            <div class="summary-row" style="background: #fffbeb;">
                                <span style="font-weight: 700; color: #b45309;">Balance Due (بقايا ادھار):</span>
                                <span style="font-weight: 800; color: #b45309;">
                                    {{ \App\Support\AmountInWords::formatLakh($invoice->balance_due, true, 2) }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- 6. Udhaar Balance Box (if credit customer or closing_balance > 0) --}}
                @if (($theme['show_udhaar_balance'] ?? true) && ((float)$invoice->closing_balance > 0 || (float)$invoice->previous_balance > 0))
                    <div class="udhaar-card">
                        <div>
                            <span style="font-weight: 700; color: #92400e;">Customer Udhaar Balance / کھاتہ ادھار:</span>
                            <span style="color: #78350f; margin-left: 8px;">
                                Previous: <strong>{{ \App\Support\AmountInWords::formatLakh($invoice->previous_balance, true, 2) }}</strong>
                                &nbsp;+&nbsp; Current Due: <strong>{{ \App\Support\AmountInWords::formatLakh($invoice->balance_due, true, 2) }}</strong>
                            </span>
                        </div>
                        <div style="font-weight: 800; color: #92400e; font-size: 13px;">
                            Total Net Balance (کل واجب الادا بقایا): {{ \App\Support\AmountInWords::formatLakh($invoice->closing_balance, true, 2) }}
                        </div>
                    </div>
                @endif

                {{-- 7. QR Verification Box — station portal ki bill verification (FBR nahi) --}}
                @if ($theme['show_qr_code'] ?? true)
                    <div class="verification-row">
                        <div class="qr-wrapper">
                            {!! $qrSvg !!}
                        </div>
                        <div class="verify-text">
                            <div class="verified-badge">
                                <span>✓</span>
                                <span>BILL VERIFICATION • بل کی تصدیق</span>
                            </div>
                            <div>
                                Scan QR code with any smartphone camera to verify this bill on the {{ $station['station_name_en'] ?? 'station' }} portal. This QR is the station's own bill verification — it is not an FBR verification.
                            </div>
                            <div style="font-family: monospace; font-size: 10px; color: #64748b; margin-top: 3px;">
                                Verification Code: {{ $invoice->hash }}
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 7b. FBR Fiscal Box — sirf fiscalised invoice par (fiscal number + FBR QR) --}}
                @if ($isFbr)
                    <div class="fbr-row">
                        @if (! empty($fbrQrSvg))
                            <div class="qr-wrapper">
                                {!! $fbrQrSvg !!}
                            </div>
                        @endif
                        <div class="verify-text">
                            <div class="fbr-badge">
                                <span>FBR</span>
                                <span>FBR TAX INVOICE • ایف بی آر ٹیکس انوائس</span>
                            </div>
                            <div>
                                FBR Invoice No / فِسکل نمبر: <span class="fbr-number">{{ $fbr->fiscal_number }}</span>
                            </div>
                            <div>
                                This invoice has been fiscalised with the Federal Board of Revenue (FBR) under SRO 1006(I)/2021. Scan the FBR QR code to verify it with FBR.
                            </div>
                        </div>
                    </div>
                @endif

                {{-- 8. Signatures --}}
                @if ($theme['show_signatures'] ?? true)
                    <div class="signatures-grid">
                        <div class="sig-block">
                            <div class="sig-line"></div>
                            <div class="sig-label">Customer Signature / دستخط کسٹمر</div>
                        </div>
                        <div class="sig-block">
                            <div class="sig-line"></div>
                            <div class="sig-label">For {{ $station['station_name_en'] ?? 'Mehar Filling Station' }} / دستخط و مہر</div>
                        </div>
                    </div>
                @endif
            </main>
        </div>

        {{-- 9. Red Footer Band --}}
        <footer class="footer-band">
            <div>
                Thank you for your patronage. Drive safely! • ہماری سروس استعمال کرنے کا شکریہ! باحفاظت سفر کریں۔
            </div>
            <div>
                Mehar Filling Station (Vital Petroleum) • Sheikhupura • Wafa Tech ERP
            </div>
        </footer>
    </div>

</body>
</html>
