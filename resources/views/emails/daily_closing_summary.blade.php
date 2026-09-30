<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Closing Summary</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #F6F6F6; color: #1B1B1B; }
        .wrapper { max-width: 680px; margin: 20px auto; background-color: #FFFFFF; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #D71920 0%, #A30F15 100%); color: #FFFFFF; padding: 25px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; text-transform: uppercase; letter-spacing: 1px; }
        .header p { margin: 5px 0 0 0; font-size: 14px; opacity: 0.9; }
        .urdu-title { font-family: 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma, sans-serif; font-size: 18px; margin-top: 4px; }
        .content { padding: 25px; }
        .metric-grid { display: table; width: 100%; margin-bottom: 20px; }
        .metric-cell { display: table-cell; width: 50%; padding: 10px; }
        .card { background-color: #FBFBFB; border: 1px solid #EBEBEB; border-left: 4px solid #D71920; padding: 15px; border-radius: 4px; }
        .card-title { font-size: 12px; text-transform: uppercase; color: #666; margin-bottom: 5px; font-weight: bold; }
        .card-value { font-size: 20px; font-weight: bold; color: #1B1B1B; }
        .card-urdu { font-size: 13px; color: #777; margin-top: 3px; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; }
        table.data-table th { background-color: #F0F0F0; color: #333; text-align: left; padding: 10px; border-bottom: 2px solid #D71920; }
        table.data-table td { padding: 10px; border-bottom: 1px solid #EBEBEB; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; }
        .badge-success { background-color: #E6F4EA; color: #137333; }
        .badge-danger { background-color: #FCE8E6; color: #C5221F; }
        .footer { background-color: #F6F6F6; padding: 15px 25px; text-align: center; font-size: 12px; color: #777; border-top: 1px solid #EBEBEB; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>Vital Petroleum — Mehar Filling Station</h1>
            <p>Gujranwala Road, Sheikhupura | Contact: 0300-1234567</p>
            <div class="urdu-title">روزانہ اختتامی رپورٹ — مہر فلنگ اسٹیشن شیخوپورہ</div>
        </div>

        <div class="content">
            <p><strong>Closing Date:</strong> {{ $closing->closing_date->format('d F Y (l)') }} &nbsp;|&nbsp; <strong>Closing #:</strong> {{ $closing->closing_number }}</p>

            <div class="metric-grid">
                <div class="metric-cell" style="padding-left: 0;">
                    <div class="card">
                        <div class="card-title">Total Fuel Volume</div>
                        <div class="card-value">{{ number_format((float) $closing->total_fuel_litres, 3) }} Litres</div>
                        <div class="card-urdu">کل ایندھن فروخت (لیٹر)</div>
                    </div>
                </div>
                <div class="metric-cell" style="padding-right: 0;">
                    <div class="card">
                        <div class="card-title">Total Sales Revenue</div>
                        <div class="card-value">{{ \App\Support\PakistaniCurrency::format($closing->total_sales_amount) }}</div>
                        <div class="card-urdu">{{ \App\Support\PakistaniCurrency::toWordsUrdu($closing->total_sales_amount) }}</div>
                    </div>
                </div>
            </div>

            <h3 style="color: #D71920; border-bottom: 2px solid #D71920; padding-bottom: 5px; margin-top: 20px;">
                Financial Summary & Payment Breakdown (مالی خلاصہ)
            </h3>
            <table class="data-table">
                <tr>
                    <td><strong>Cash Sales (نقد فروخت)</strong></td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($closing->total_cash_sales) }}</td>
                </tr>
                <tr>
                    <td><strong>Credit / Udhaar Sales (ادھار فروخت)</strong></td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($closing->total_credit_sales) }}</td>
                </tr>
                <tr>
                    <td><strong>Card / OMC Fleet Sales (کارڈ فروخت)</strong></td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($closing->total_card_sales) }}</td>
                </tr>
                <tr>
                    <td><strong>Customer Udhaar Receipts (ادھار وصولیاں)</strong></td>
                    <td class="text-right" style="color: #137333;">+ {{ \App\Support\PakistaniCurrency::format($closing->total_customer_receipts) }}</td>
                </tr>
                <tr>
                    <td><strong>Operating Expenses Paid (کاروباری اخراجات)</strong></td>
                    <td class="text-right" style="color: #C5221F;">- {{ \App\Support\PakistaniCurrency::format($closing->total_expenses) }}</td>
                </tr>
                <tr>
                    <td><strong>Bank Deposits Made (بینک میں جمع رقوم)</strong></td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($closing->total_bank_deposits) }}</td>
                </tr>
            </table>

            <h3 style="color: #D71920; border-bottom: 2px solid #D71920; padding-bottom: 5px; margin-top: 25px;">
                Cash Drawer Reconciliation (کیش مفاہمت)
            </h3>
            <table class="data-table">
                <tr>
                    <td>Opening Float (ابتدائی کیش)</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($closing->opening_cash) }}</td>
                </tr>
                <tr>
                    <td>Expected Cash (متوقع کیش)</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($closing->expected_cash) }}</td>
                </tr>
                <tr>
                    <td>Actual Cash Counted (حقیقی گنتی کیش)</td>
                    <td class="text-right"><strong>{{ \App\Support\PakistaniCurrency::format($closing->actual_cash_counted) }}</strong></td>
                </tr>
                <tr>
                    <td>Cash Short / Over (کمی / بیشی)</td>
                    <td class="text-right">
                        @if((float) $closing->cash_variance == 0)
                            <span class="badge badge-success">Balanced (Rs. 0.00)</span>
                        @elseif((float) $closing->cash_variance > 0)
                            <span class="badge badge-success">+ {{ \App\Support\PakistaniCurrency::format($closing->cash_variance) }} (Surplus)</span>
                        @else
                            <span class="badge badge-danger">{{ \App\Support\PakistaniCurrency::format($closing->cash_variance) }} (Short)</span>
                        @endif
                    </td>
                </tr>
            </table>

            <h3 style="color: #D71920; border-bottom: 2px solid #D71920; padding-bottom: 5px; margin-top: 25px;">
                Physical Dip & Stock Reconciliations (اسٹاک ڈِپ پیمائش)
            </h3>
            <table class="data-table">
                <tr>
                    <td>Expected Book Stock</td>
                    <td class="text-right">{{ number_format((float) $closing->expected_dip_stock, 3) }} L</td>
                </tr>
                <tr>
                    <td>Physical Dip Measured</td>
                    <td class="text-right"><strong>{{ number_format((float) $closing->actual_dip_stock, 3) }} L</strong></td>
                </tr>
                <tr>
                    <td>Dip Gain / (Loss) Variance</td>
                    <td class="text-right">
                        @if((float) $closing->dip_variance_litres >= 0)
                            <span class="badge badge-success">+{{ number_format((float) $closing->dip_variance_litres, 3) }} L</span>
                        @else
                            <span class="badge badge-danger">{{ number_format((float) $closing->dip_variance_litres, 3) }} L</span>
                        @endif
                    </td>
                </tr>
            </table>

            @if($closing->notes)
                <p style="margin-top: 20px; font-size: 13px; color: #555;"><strong>Manager Remarks:</strong> {{ $closing->notes }}</p>
            @endif
        </div>

        <div class="footer">
            Generated automatically by Vital Petroleum ERP &copy; {{ date('Y') }} Mehar Filling Station. All rights reserved.
        </div>
    </div>
</body>
</html>
