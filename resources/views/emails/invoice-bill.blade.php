<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bill {{ $invoice->invoice_number }} — {{ $station['station_name_en'] ?? 'Mehar Filling Station' }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #F6F6F6; color: #1B1B1B; }
        .wrapper { max-width: 640px; margin: 20px auto; background-color: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #D71920 0%, #A30F15 100%); color: #FFFFFF; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 23px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0 0; font-size: 13px; opacity: 0.9; }
        .urdu-title { font-family: 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma, sans-serif; font-size: 17px; margin-top: 4px; }
        .content { padding: 25px; }
        .greeting { font-size: 15px; margin: 0 0 4px 0; }
        .greeting-ur { font-size: 14px; color: #555; margin: 0 0 18px 0; }
        table.meta { width: 100%; border-collapse: collapse; font-size: 14px; margin-bottom: 18px; }
        table.meta td { padding: 6px 8px; border-bottom: 1px solid #EBEBEB; }
        table.meta td.label { color: #666; width: 38%; font-weight: bold; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        table.data-table th { background-color: #F0F0F0; color: #333; text-align: left; padding: 10px; border-bottom: 2px solid #D71920; }
        table.data-table td { padding: 10px; border-bottom: 1px solid #EBEBEB; }
        .text-right { text-align: right; }
        table.totals { width: 100%; border-collapse: collapse; font-size: 14px; margin-top: 14px; }
        table.totals td { padding: 6px 8px; }
        table.totals td.label { color: #555; text-align: right; }
        table.totals td.value { text-align: right; font-weight: bold; width: 35%; }
        table.totals tr.grand td { border-top: 2px solid #D71920; font-size: 16px; color: #A30F15; }
        table.totals tr.due td { color: #C5221F; }
        .words { font-size: 12px; color: #666; font-style: italic; margin-top: 10px; }
        .fbr-box { background-color: #E6F4EA; border: 1px solid #137333; border-radius: 6px; padding: 12px 15px; margin-top: 20px; font-size: 14px; color: #137333; }
        .fbr-box .fbr-title { font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .attach-note { background-color: #FBFBFB; border: 1px solid #EBEBEB; border-left: 4px solid #D71920; border-radius: 4px; padding: 12px 15px; margin-top: 20px; font-size: 13px; color: #444; }
        .footer { background-color: #F6F6F6; padding: 15px 25px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #EBEBEB; }
        .footer a { color: #A30F15; text-decoration: none; }
    </style>
</head>
<body>
    @php
        $methodLabels = [
            'cash' => 'Cash / نقد',
            'card' => 'Debit/Credit Card / کارڈ',
            'bank_transfer' => 'Bank Transfer / بینک ٹرانسفر',
            'mobile_wallet' => 'Mobile Wallet / موبائل والٹ',
            'jazzcash' => 'JazzCash / جاز کیش',
            'easypaisa' => 'Easypaisa / ایزی پیسہ',
            'raast' => 'Raast / راست',
            'fleet_card' => 'Fleet Card / فلیٹ کارڈ',
            'credit' => 'Credit (Udhaar) / ادھار',
            'split' => 'Split Payment / ملی جلی ادائیگی',
        ];
        $methodKey = strtolower((string) $invoice->payment_method);
        $methodLabel = $methodLabels[$methodKey] ?? ucwords(str_replace('_', ' ', $methodKey));
        $customerName = $invoice->customer?->name ?? 'Valued Customer';
    @endphp

    <div class="wrapper">
        <div class="header">
            <h1>{{ $station['station_name_en'] ?? 'Mehar Filling Station' }}</h1>
            <div class="urdu-title">{{ $station['station_name_ur'] ?? 'مہر فلنگ اسٹیشن' }}</div>
            <p>{{ $station['omc_brand_name'] ?? '' }}</p>
            <p>{{ $station['address'] ?? '' }} | {{ $station['phone'] ?? '' }}</p>
        </div>

        <div class="content">
            <p class="greeting">Dear {{ $customerName }},</p>
            <p class="greeting-ur">شکریہ! آپ کی خریداری کا بل نیچے دیا گیا ہے اور PDF کاپی ساتھ منسلک ہے۔</p>

            <table class="meta">
                <tr>
                    <td class="label">Bill / Invoice No.</td>
                    <td><strong>{{ $invoice->invoice_number }}</strong></td>
                </tr>
                <tr>
                    <td class="label">Date / تاریخ</td>
                    <td>{{ $invoice->invoice_date?->format('d M Y, h:i A') }}</td>
                </tr>
                <tr>
                    <td class="label">Customer / کسٹمر</td>
                    <td>{{ $customerName }}</td>
                </tr>
                @if ($invoice->vehicle?->registration_number)
                    <tr>
                        <td class="label">Vehicle / گاڑی</td>
                        <td>{{ $invoice->vehicle->registration_number }}</td>
                    </tr>
                @endif
                <tr>
                    <td class="label">Payment Method / ادائیگی</td>
                    <td>{{ $methodLabel }}</td>
                </tr>
            </table>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Item / تفصیل</th>
                        <th class="text-right">Litres / لیٹر</th>
                        <th class="text-right">Rate / ریٹ</th>
                        <th class="text-right">Amount / رقم</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($invoice->items as $item)
                        <tr>
                            <td>{{ $item->item_description }}</td>
                            <td class="text-right">{{ number_format((float) $item->quantity, 3) }}</td>
                            <td class="text-right">Rs. {{ number_format((float) $item->unit_price, 2) }}</td>
                            <td class="text-right">Rs. {{ number_format((float) $item->total_amount, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="totals">
                <tr>
                    <td class="label">Subtotal</td>
                    <td class="value">Rs. {{ number_format((float) $invoice->subtotal, 2) }}</td>
                </tr>
                @if ((float) $invoice->discount_amount > 0)
                    <tr>
                        <td class="label">Discount / رعایت</td>
                        <td class="value">− Rs. {{ number_format((float) $invoice->discount_amount, 2) }}</td>
                    </tr>
                @endif
                @if ((float) $invoice->tax_amount > 0)
                    <tr>
                        <td class="label">Tax / ٹیکس</td>
                        <td class="value">Rs. {{ number_format((float) $invoice->tax_amount, 2) }}</td>
                    </tr>
                @endif
                @if ((float) $invoice->pos_fee > 0)
                    <tr>
                        <td class="label">Service Fee</td>
                        <td class="value">Rs. {{ number_format((float) $invoice->pos_fee, 2) }}</td>
                    </tr>
                @endif
                <tr class="grand">
                    <td class="label">Total / کل رقم</td>
                    <td class="value">Rs. {{ number_format((float) $invoice->total_amount, 2) }}</td>
                </tr>
                <tr>
                    <td class="label">Paid / ادا شدہ</td>
                    <td class="value">Rs. {{ number_format((float) $invoice->paid_amount, 2) }}</td>
                </tr>
                @if ((float) $invoice->balance_due > 0)
                    <tr class="due">
                        <td class="label">Balance Due / بقایا</td>
                        <td class="value">Rs. {{ number_format((float) $invoice->balance_due, 2) }}</td>
                    </tr>
                @endif
            </table>

            @if (! empty($invoice->amount_in_words_en))
                <p class="words">{{ $invoice->amount_in_words_en }}</p>
            @endif

            @if (! empty($fbrInvoice) && ! empty($fbrInvoice->fiscal_number))
                <div class="fbr-box">
                    <span class="fbr-title">FBR Tax Invoice / ایف بی آر ٹیکس انوائس</span><br>
                    Fiscal Number (FBR Invoice No.): <strong>{{ $fbrInvoice->fiscal_number }}</strong><br>
                    یہ بل FBR کے ساتھ رجسٹرڈ ہے — Fiscal Number سے تصدیق کی جا سکتی ہے۔
                </div>
            @endif

            <div class="attach-note">
                📎 A PDF copy of this bill is attached to this email for your records.<br>
                آپ کے ریکارڈ کے لیے اس بل کی PDF کاپی اس ای میل کے ساتھ منسلک ہے۔
            </div>
        </div>

        <div class="footer">
            Thank you for refuelling with us! / ہمارے پاس ایندھن بھروانے کا شکریہ!<br>
            Verify this bill online: <a href="{{ $invoice->verification_url }}">{{ $invoice->verification_url }}</a><br>
            {{ $station['station_name_en'] ?? 'Mehar Filling Station' }} — {{ $station['phone'] ?? '' }}
        </div>
    </div>
</body>
</html>
