<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Receipt {{ $invoice->invoice_number }}</title>
    @php
        $width = ($paperWidth === '58mm') ? '48mm' : '72mm';
        $bodyWidth = ($paperWidth === '58mm') ? '54mm' : '76mm';
        $fontSize = ($paperWidth === '58mm') ? '11px' : '12px';
        $qrSize = ($paperWidth === '58mm') ? '75px' : '95px';
    @endphp
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: "Courier New", Courier, monospace, sans-serif;
            font-size: {{ $fontSize }};
            color: #000000;
            background: #f1f5f9;
            padding: 10px;
            line-height: 1.35;
        }

        .font-urdu {
            font-family: "Noto Nastaliq Urdu", "Jameel Noori Nastaleeq", "Urdu Typesetting", Tahoma, sans-serif;
            direction: rtl;
        }

        .receipt-container {
            width: {{ $bodyWidth }};
            max-width: {{ $bodyWidth }};
            margin: 0 auto;
            background: #ffffff;
            padding: 8px 6px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .no-print {
            display: flex;
            justify-content: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .btn {
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
        }

        .btn-red {
            background: #D71920;
            color: #ffffff;
            border-color: #D71920;
        }

        @media print {
            body {
                background: #ffffff !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .receipt-container {
                box-shadow: none !important;
                margin: 0 !important;
                padding: 4px 2px !important;
                width: {{ $width }} !important;
            }
            @page {
                size: {{ $paperWidth }} auto;
                margin: 0;
            }
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }

        .divider {
            border-top: 1px dashed #000000;
            margin: 6px 0;
        }

        .double-divider {
            border-top: 1.5px solid #000000;
            border-bottom: 1.5px solid #000000;
            height: 3px;
            margin: 6px 0;
        }

        .row {
            display: flex;
            justify-content: space-between;
            padding: 1px 0;
        }

        .qr-section {
            text-align: center;
            margin: 8px 0;
        }

        .qr-wrapper {
            display: inline-block;
            width: {{ $qrSize }};
            height: {{ $qrSize }};
        }

        .cut-space {
            height: 35px;
        }
    </style>
</head>
<body>

    {{-- Screen Action Toolbar --}}
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-red">🖨️ Print Receipt</button>
        <a href="{{ route('invoices.thermal', ['invoice' => $invoice, 'size' => '80mm']) }}"
           class="btn {{ $paperWidth === '80mm' ? 'btn-red' : '' }}">80mm</a>
        <a href="{{ route('invoices.thermal', ['invoice' => $invoice, 'size' => '58mm']) }}"
           class="btn {{ $paperWidth === '58mm' ? 'btn-red' : '' }}">58mm</a>
        <a href="{{ route('invoices.show', $invoice) }}" class="btn">Close</a>
    </div>

    {{-- Thermal Paper Slip --}}
    <div class="receipt-container">
        {{-- Header --}}
        <div class="text-center">
            <div style="font-size: 14px; font-weight: 900; letter-spacing: 0.5px;">MEHAR FILLING STATION</div>
            <div class="font-urdu" style="font-size: 13px; font-weight: bold;">مہر فلنگ اسٹیشن</div>
            <div style="font-size: 10px; font-weight: bold;">VITAL PETROLEUM (وائٹل پیٹرول)</div>
            <div style="font-size: 9px; margin-top: 2px;">Sheikhupura–Sharaqpur Road, Sheikhupura</div>
            <div style="font-size: 9.5px;">Tel: {{ $station['phone'] ?? '0300-4342343' }}</div>
        </div>

        <div class="double-divider"></div>

        {{-- Memo Meta --}}
        <div class="text-center font-bold" style="font-size: 11px;">
            TAX INVOICE / کیش میمو
        </div>

        <div class="row">
            <span>Inv #:</span>
            <span class="font-bold">{{ $invoice->invoice_number }}</span>
        </div>
        <div class="row">
            <span>Date:</span>
            <span>{{ $invoice->invoice_date->format('d/m/Y h:i A') }}</span>
        </div>
        <div class="row">
            <span>Cashier:</span>
            <span>{{ $invoice->user?->name ?? 'Cashier' }}</span>
        </div>
        @if ($invoice->shift_id)
            <div class="row">
                <span>Shift:</span>
                <span>#{{ $invoice->shift_id }}</span>
            </div>
        @endif

        <div class="divider"></div>

        {{-- Customer Info --}}
        <div class="row">
            <span>Customer:</span>
            <span class="font-bold">{{ $customer['name'] ?? ($invoice->customer?->name ?? 'Walk-in Customer') }}</span>
        </div>
        @if (!empty($customer['vehicle_number']) || !empty($invoice->vehicle?->registration_number))
            <div class="row">
                <span>Vehicle:</span>
                <span class="font-bold">{{ $customer['vehicle_number'] ?? $invoice->vehicle->registration_number }}</span>
            </div>
        @endif

        <div class="divider"></div>

        {{-- Items List --}}
        <div class="row font-bold" style="font-size: 10px; text-transform: uppercase;">
            <span>Item / Qty</span>
            <span>Rate</span>
            <span>Amount</span>
        </div>
        <div class="divider"></div>

        @foreach ($invoice->items as $item)
            <div style="font-weight: bold;">
                {{ $item->fuelProduct?->name ?? $item->item_description }}
            </div>
            <div class="row" style="font-size: 10.5px;">
                <span>{{ number_format((float)$item->quantity, 3) }} Litres</span>
                <span>@ {{ number_format((float)$item->unit_price, 2) }}</span>
                <span class="font-bold">{{ number_format((float)$item->total_amount, 2) }}</span>
            </div>
            @if ($item->meter_start && $item->meter_end)
                <div style="font-size: 9px; color: #555;">
                    Mtr: {{ number_format((float)$item->meter_start, 1) }} - {{ number_format((float)$item->meter_end, 1) }}
                </div>
            @endif
        @endforeach

        <div class="divider"></div>

        {{-- Financial Totals --}}
        <div class="row">
            <span>Subtotal:</span>
            <span>Rs. {{ number_format((float)$invoice->subtotal, 2) }}</span>
        </div>

        @if ((float)$invoice->pos_fee > 0)
            <div class="row">
                <span>POS Fee:</span>
                <span>Rs. {{ number_format((float)$invoice->pos_fee, 2) }}</span>
            </div>
        @endif

        @if ((float)$invoice->discount_amount > 0)
            <div class="row">
                <span>Discount:</span>
                <span>- Rs. {{ number_format((float)$invoice->discount_amount, 2) }}</span>
            </div>
        @endif

        <div class="divider"></div>

        <div class="row font-bold" style="font-size: 13px;">
            <span>TOTAL:</span>
            <span>Rs. {{ number_format((float)$invoice->total_amount, 2) }}</span>
        </div>

        <div class="row">
            <span>Paid ({{ ucfirst($invoice->payment_method ?? 'cash') }}):</span>
            <span class="font-bold">Rs. {{ number_format((float)$invoice->paid_amount, 2) }}</span>
        </div>

        @if ((float)$invoice->balance_due > 0)
            <div class="row font-bold" style="color: #b45309;">
                <span>Balance Due:</span>
                <span>Rs. {{ number_format((float)$invoice->balance_due, 2) }}</span>
            </div>
        @endif

        <div class="divider"></div>

        {{-- Urdu Amount in Words --}}
        <div class="text-center font-urdu font-bold" style="font-size: 12px; margin: 4px 0;">
            {{ $invoice->amount_in_words_ur ?: \App\Support\AmountInWords::toUrdu($invoice->total_amount) }}
        </div>

        {{-- Customer Udhaar Balance if any --}}
        @if ((float)$invoice->closing_balance > 0 || (float)$invoice->previous_balance > 0)
            <div class="divider"></div>
            <div class="row" style="font-size: 10px;">
                <span>Prev Balance:</span>
                <span>Rs. {{ number_format((float)$invoice->previous_balance, 2) }}</span>
            </div>
            <div class="row font-bold" style="font-size: 10.5px;">
                <span>Total Net Balance:</span>
                <span>Rs. {{ number_format((float)$invoice->closing_balance, 2) }}</span>
            </div>
        @endif

        <div class="divider"></div>

        {{-- QR Code Verification --}}
        <div class="qr-section">
            <div class="qr-wrapper">
                {!! $qrSvg !!}
            </div>
            <div style="font-size: 8.5px; margin-top: 2px;">
                Scan QR to Verify Genuine Bill
            </div>
            <div style="font-size: 8px; font-family: monospace;">
                #{{ substr($invoice->hash, 0, 16) }}
            </div>
        </div>

        <div class="double-divider"></div>

        <div class="text-center" style="font-size: 9.5px; margin-top: 4px;">
            <div>Thank you for your patronage!</div>
            <div class="font-urdu" style="font-size: 10.5px; margin-top: 2px;">ہماری سروس استعمال کرنے کا شکریہ</div>
            <div style="font-size: 8.5px; margin-top: 3px;">Drive Safely • Wafa Tech ERP</div>
        </div>

        <div class="cut-space"></div>
    </div>

</body>
</html>
