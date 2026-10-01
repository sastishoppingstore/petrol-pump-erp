<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Thermal Receipt — {{ $sale->invoice_number }}</title>
    @php
        $paperWidth = $paperWidth ?? '80mm';
        $is58 = $paperWidth === '58mm';
        $bodyWidth = $is58 ? '54mm' : '76mm';
        $printWidth = $is58 ? '48mm' : '72mm';
        $baseFont = $is58 ? '11px' : '12px';
        $fbrQrBox = $is58 ? '70px' : '90px';
    @endphp
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace, monospace;
        }
        body {
            background-color: #f3f4f6;
            padding: 20px;
            font-size: {{ $baseFont }};
            color: #000;
        }
        .receipt-container {
            width: {{ $bodyWidth }};
            margin: 0 auto;
            background: #fff;
            padding: 10px 8px;
            border: 1px solid #ddd;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .fw-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        .border-top { border-top: 1px dashed #000; }
        .border-bottom { border-bottom: 1px dashed #000; }
        .border-double { border-top: 2px dashed #000; border-bottom: 2px dashed #000; }
        .my-1 { margin-top: 4px; margin-bottom: 4px; }
        .my-2 { margin-top: 8px; margin-bottom: 8px; }
        .py-1 { padding-top: 4px; padding-bottom: 4px; }
        .flex { display: flex; justify-content: space-between; }
        .table { width: 100%; border-collapse: collapse; margin: 6px 0; }
        .table th, .table td { padding: 3px 0; }
        .urdu { font-family: 'Noto Nastaliq Urdu', 'Urdu Typesetting', Tahoma, sans-serif; direction: rtl; font-size: 13px; }
        .no-print { margin-bottom: 15px; text-align: center; }
        .btn {
            background: #D71920;
            color: white;
            border: none;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        .btn-grey { background: #555; }
        .fbr-box {
            border: 1.5px solid #000;
            padding: 5px 3px;
            text-align: center;
        }
        .fbr-qr { margin: 3px auto; }
        .fbr-qr svg { width: 100%; height: 100%; }

        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                width: {{ $printWidth }};
                max-width: {{ $printWidth }};
                border: none;
                padding: 0;
                margin: 0;
            }
            .no-print {
                display: none;
            }
            @page {
                size: {{ $paperWidth }} auto;
                margin: 0;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn">🖨️ Print Receipt ({{ $paperWidth }})</button>
        <a href="{{ route('pos.thermal', ['sale' => $sale, 'size' => '80mm']) }}" class="btn {{ $is58 ? 'btn-grey' : '' }}">80mm</a>
        <a href="{{ route('pos.thermal', ['sale' => $sale, 'size' => '58mm']) }}" class="btn {{ $is58 ? '' : 'btn-grey' }}">58mm</a>
        <button onclick="window.close()" class="btn btn-grey">✕ Close</button>
    </div>

    @php
        $settings = app(\App\Services\System\SettingService::class);
        $company = $settings->company();
        $posFee = $settings->money('pos_service_fee') ?: '1.00';
    @endphp

    <div class="receipt-container">
        {{-- Header --}}
        <div class="text-center">
            <h2 class="fw-bold" style="font-size: 15px;">{{ $company['company_name'] }}</h2>
            <div style="font-size: 10px; font-weight: bold; color: #D71920;">VITAL PETROLEUM FRANCHISE</div>
            <div style="font-size: 10px;">{{ $company['address'] }}</div>
            <div style="font-size: 10px;">Tel: {{ $company['phone'] }}</div>
            @if (!empty($company['strn'])) <div style="font-size: 10px;">STRN: {{ $company['strn'] }}</div> @endif
            @if (!empty($company['ntn'])) <div style="font-size: 10px;">NTN: {{ $company['ntn'] }}</div> @endif
        </div>

        <div class="border-top my-1"></div>

        {{-- Invoice Facts --}}
        <div class="flex" style="font-size: 11px;">
            <span>INV: <strong class="fw-bold">{{ $sale->invoice_number }}</strong></span>
            <span>{{ $sale->sale_date?->format('d/m/y H:i') }}</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Cashier: {{ $sale->employee?->name ?? 'Attendant' }}</span>
            <span>Shift: {{ $sale->shift?->shift_number ?? '1' }}</span>
        </div>
        @if ($sale->customer || $sale->customer_name)
            <div class="flex" style="font-size: 10px;">
                <span>Customer: {{ $sale->customer?->name ?: $sale->customer_name }}</span>
                @if ($sale->vehicle) <span>Veh: {{ $sale->vehicle->registration_number }}</span> @endif
            </div>
        @endif

        <div class="border-top my-1"></div>

        {{-- Line Items Table --}}
        <table class="table" style="font-size: 11px;">
            <thead>
                <tr class="border-bottom text-left">
                    <th>ITEM</th>
                    <th class="text-right">QTY(L)</th>
                    <th class="text-right">RATE</th>
                    <th class="text-right">AMOUNT</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr>
                        <td>{{ $item->fuelProduct?->name }}</td>
                        <td class="text-right">{{ number_format((float) $item->litres, 3) }}</td>
                        <td class="text-right">{{ number_format((float) $item->rate, 2) }}</td>
                        <td class="text-right fw-bold">{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="border-top my-1"></div>

        {{-- Totals --}}
        <div style="font-size: 11px;">
            <div class="flex">
                <span>Subtotal:</span>
                <span>Rs. {{ number_format((float) $sale->subtotal, 2) }}</span>
            </div>
            @if (\App\Support\Money::compare($sale->discount, '0') > 0)
                <div class="flex">
                    <span>Discount:</span>
                    <span>-Rs. {{ number_format((float) $sale->discount, 2) }}</span>
                </div>
            @endif
            <div class="flex">
                <span>PoS Fee (SRO 1006):</span>
                <span>Rs. {{ number_format((float) $posFee, 2) }}</span>
            </div>
            <div class="border-top my-1"></div>
            <div class="flex fw-bold" style="font-size: 14px;">
                <span>NET TOTAL:</span>
                <span>{{ \App\Support\PakistaniCurrency::format($sale->total) }}</span>
            </div>
        </div>

        {{-- Amount in Urdu words --}}
        <div class="urdu text-center my-1" style="font-size: 12px; font-weight: bold;">
            {{ \App\Support\PakistaniCurrency::toWordsUrdu($sale->total) }}
        </div>

        {{-- Payments --}}
        <div class="border-top my-1" style="font-size: 10px;">
            <div class="fw-bold uppercase">Payment Breakdown:</div>
            @foreach ($sale->payments as $payment)
                <div class="flex">
                    <span>{{ \App\Models\SalePayment::methods()[$payment->method] ?? $payment->method }}</span>
                    <span>Rs. {{ number_format((float) $payment->amount, 2) }}</span>
                </div>
            @endforeach
        </div>

        {{-- FBR Fiscal Block — sirf fiscalised sale par (fiscal number + FBR QR) --}}
        @if (! empty($fbrInvoice) && ! empty($fbrInvoice->fiscal_number))
            <div class="border-top my-1"></div>
            <div class="fbr-box">
                <div class="fw-bold" style="font-size: 11px;">FBR TAX INVOICE</div>
                <div class="urdu" style="font-size: 11px;">ایف بی آر ٹیکس انوائس</div>
                <div style="font-size: 9px;">FBR Invoice No / فِسکل نمبر:</div>
                <div class="fw-bold" style="font-size: {{ $is58 ? '10px' : '11.5px' }};">{{ $fbrInvoice->fiscal_number }}</div>
                @if (! empty($fbrQrSvg))
                    <div class="fbr-qr" style="width: {{ $fbrQrBox }}; height: {{ $fbrQrBox }};">
                        {!! $fbrQrSvg !!}
                    </div>
                    <div style="font-size: 8.5px;">Scan FBR QR to Verify with FBR</div>
                @endif
            </div>
        @endif

        <div class="border-double my-2"></div>

        {{-- Footer --}}
        <div class="text-center" style="font-size: 9px; line-height: 1.3;">
            <div>Thank you for choosing Vital Petroleum!</div>
            <div class="urdu" style="font-size: 11px;">ہماری سروس استعمال کرنے کا شکریہ - سفر بخیر</div>
            <div style="margin-top: 4px;">*** Software: Vital PPMS Sheikhupura ***</div>
        </div>
    </div>

    <script>
        // Auto trigger print dialog on thermal print load
        window.addEventListener('DOMContentLoaded', () => {
            if (window.location.search.includes('autoprint=1')) {
                window.print();
            }
        });
    </script>
</body>
</html>
