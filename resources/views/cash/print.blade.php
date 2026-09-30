<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Voucher — {{ $entry->voucher_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Courier New', Courier, monospace;
        }
        body {
            background-color: #f3f4f6;
            padding: 20px;
            font-size: 12px;
            color: #000;
        }
        .voucher-container {
            width: 76mm;
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
        .flex { display: flex; justify-content: space-between; }
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
        }
        @media print {
            body { background: #fff; padding: 0; margin: 0; }
            .voucher-container { width: 100%; max-width: 76mm; border: none; padding: 0; margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" class="btn">🖨️ Print Voucher</button>
        <button onclick="window.close()" class="btn" style="background:#555;">✕ Close</button>
    </div>

    <div class="voucher-container">
        <div class="text-center">
            <h2 class="fw-bold" style="font-size: 15px;">MEHAR FILLING STATION</h2>
            <div style="font-size: 10px; font-weight: bold; color: #D71920;">VITAL PETROLEUM FRANCHISE</div>
            <div style="font-size: 10px;">Sheikhupura–Sharaqpur Road, Sheikhupura</div>
            <div class="border-top my-1"></div>
            <div class="fw-bold uppercase" style="font-size: 13px;">
                {{ $entry->isCashIn() ? 'CASH RECEIPT VOUCHER (CRV)' : 'CASH PAYMENT VOUCHER (CPV)' }}
            </div>
            <div class="urdu" style="font-size: 12px; font-weight: bold;">
                {{ $entry->isCashIn() ? 'کیش وصولی واؤچر' : 'کیش ادائیگی واؤچر' }}
            </div>
        </div>

        <div class="border-top my-1"></div>

        <div class="flex" style="font-size: 11px;">
            <span>VOUCHER: <strong class="fw-bold">{{ $entry->voucher_number }}</strong></span>
            <span>{{ $entry->entry_date?->format('d/m/y H:i') }}</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Operator: {{ $entry->user?->name ?? 'Cashier' }}</span>
            <span>Shift: {{ $entry->shift?->shift_number ?? '1' }}</span>
        </div>
        <div class="flex" style="font-size: 10px;">
            <span>Category: {{ str_replace('_', ' ', $entry->category) }}</span>
            @if ($entry->reference_no) <span>Ref: {{ $entry->reference_no }}</span> @endif
        </div>

        <div class="border-top my-1"></div>

        <div style="font-size: 11px; margin: 6px 0;">
            <div><strong>{{ $entry->isCashIn() ? 'RECEIVED FROM:' : 'PAID TO:' }}</strong></div>
            <div style="font-size: 13px; font-weight: bold;">{{ $entry->person_name }}</div>
        </div>

        @if ($entry->notes)
            <div style="font-size: 10px; color: #333; margin-bottom: 6px;">
                <em>Notes: {{ $entry->notes }}</em>
            </div>
        @endif

        <div class="border-top my-1"></div>

        <div class="flex fw-bold" style="font-size: 15px; margin: 6px 0;">
            <span>AMOUNT:</span>
            <span>{{ \App\Support\PakistaniCurrency::format($entry->amount) }}</span>
        </div>

        <div class="urdu text-center my-1" style="font-size: 13px; font-weight: bold;">
            {{ \App\Support\PakistaniCurrency::toWordsUrdu($entry->amount) }}
        </div>

        <div class="border-double my-2"></div>

        <div style="margin-top: 25px; display: flex; justify-content: space-between; font-size: 9px; text-align: center;">
            <div style="border-top: 1px solid #000; width: 45%; padding-top: 2px;">
                Prepared By: {{ $entry->user?->name }}
            </div>
            <div style="border-top: 1px solid #000; width: 45%; padding-top: 2px;">
                Recipient / Approved
            </div>
        </div>

        <div class="text-center" style="font-size: 9px; margin-top: 15px;">
            <div>*** Vital PPMS Roznamcha ***</div>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            if (window.location.search.includes('autoprint=1')) {
                window.print();
            }
        });
    </script>
</body>
</html>
