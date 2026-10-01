<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Auto Report — {{ $report->branch?->name ?? $summary['branch'] ?? '' }}</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 14px; color: #1B1B1B; margin: 0; padding: 24px; background: #F6F6F6; }
        .card { max-width: 640px; margin: 0 auto; background: #FFFFFF; border-radius: 10px; overflow: hidden; border: 1px solid #E5E5E5; }
        .header { background: #1B1B1B; color: #FFFFFF; padding: 18px 24px; }
        .header .brand { color: #D71920; font-weight: bold; font-size: 18px; text-transform: uppercase; }
        .header .title { font-size: 15px; margin-top: 4px; }
        .body { padding: 20px 24px; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0 20px; font-size: 13px; }
        th { background: #F6F6F6; border: 1px solid #DDD; padding: 7px 9px; text-align: left; }
        td { border: 1px solid #DDD; padding: 7px 9px; }
        td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .kpi { font-size: 20px; font-weight: bold; color: #A30F15; }
        .muted { color: #777; font-size: 12px; }
        .footer { padding: 14px 24px; border-top: 1px solid #EEE; color: #777; font-size: 12px; }
        h3 { margin: 18px 0 6px; font-size: 14px; color: #1B1B1B; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <div class="brand">{{ $summary['branch'] ?? $report->branch?->name ?? 'Fuel Station' }}</div>
            <div class="title">📊 Auto Report — {{ strtoupper((string) $report->period) }} &nbsp;•&nbsp; {{ $report->report_date?->format('d M Y') }}</div>
        </div>

        <div class="body">
            <p>Assalam-o-Alaikum,</p>
            <p>
                Aap ki scheduled report tayyar hai.
                <strong>Period:</strong> {{ $summary['period_start'] ?? '—' }} → {{ $summary['period_end'] ?? '—' }}<br>
                <span class="muted">PDF aur Excel copies is email ke saath attached hain.</span>
            </p>

            <table>
                <tr>
                    <td style="width:50%">Total Sales<br><span class="kpi">Rs {{ number_format((float) ($summary['total_sales'] ?? 0), 0) }}</span></td>
                    <td>Total Litres<br><span class="kpi">{{ number_format((float) ($summary['total_litres'] ?? 0), 0) }} L</span></td>
                </tr>
                <tr>
                    <td>Transactions<br><strong>{{ number_format((int) ($summary['transaction_count'] ?? 0)) }}</strong></td>
                    <td>Net Profit<br><strong>Rs {{ number_format((float) ($summary['net_profit'] ?? 0), 0) }}</strong> ({{ number_format((float) ($summary['net_profit_percentage'] ?? 0), 1) }}%)</td>
                </tr>
            </table>

            <h3>💳 Payment Breakdown</h3>
            <table>
                <tr><th>Method</th><th style="text-align:right">Amount (Rs)</th></tr>
                <tr><td>Cash</td><td class="num">{{ number_format((float) ($summary['cash_sales'] ?? 0), 2) }}</td></tr>
                <tr><td>Card</td><td class="num">{{ number_format((float) ($summary['card_sales'] ?? 0), 2) }}</td></tr>
                <tr><td>Credit (Udhaar)</td><td class="num">{{ number_format((float) ($summary['credit_sales'] ?? 0), 2) }}</td></tr>
                <tr><td>Other</td><td class="num">{{ number_format((float) ($summary['other_sales'] ?? 0), 2) }}</td></tr>
            </table>

            <h3>📈 Profitability</h3>
            <table>
                <tr><td>COGS</td><td class="num">Rs {{ number_format((float) ($summary['cogs'] ?? 0), 2) }}</td></tr>
                <tr><td>Gross Margin</td><td class="num">Rs {{ number_format((float) ($summary['gross_margin'] ?? 0), 2) }} ({{ number_format((float) ($summary['gross_margin_percentage'] ?? 0), 1) }}%)</td></tr>
                <tr><td>Expenses</td><td class="num">Rs {{ number_format((float) ($summary['expenses'] ?? 0), 2) }}</td></tr>
            </table>

            @if (($fbr['count'] ?? 0) > 0)
                <h3>🧾 FBR Fiscalised Sales</h3>
                <table>
                    <tr><td>FBR Invoices (is period me)</td><td class="num">{{ number_format((int) $fbr['count']) }}</td></tr>
                    <tr><td>FBR Invoices Total</td><td class="num">Rs {{ number_format((float) $fbr['total'], 2) }}</td></tr>
                </table>
            @endif

            <h3>⛽ Fuel Breakdown</h3>
            <table>
                <tr><th>Fuel</th><th style="text-align:right">Litres</th><th style="text-align:right">Revenue (Rs)</th></tr>
                @forelse (($summary['fuel_breakdown'] ?? []) as $fuel)
                    <tr>
                        <td>{{ $fuel['fuel'] ?? 'Unknown' }}</td>
                        <td class="num">{{ number_format((float) ($fuel['litres'] ?? 0), 1) }}</td>
                        <td class="num">{{ number_format((float) ($fuel['revenue'] ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="muted">Is period me koi fuel sale record nahi hui.</td></tr>
                @endforelse
            </table>

            <p class="muted">
                📦 Stock — Purchases: {{ number_format((float) ($summary['purchases'] ?? 0), 1) }} L •
                Sales: {{ number_format((float) ($summary['sales'] ?? 0), 1) }} L •
                Variance: {{ number_format((float) ($summary['variance'] ?? 0), 1) }} L
            </p>
        </div>

        <div class="footer">
            Ye email system ne khud-ba-khud bheji hai (Auto Reports). Band karne ya recipients badalne ke liye
            Admin → Reports → Auto Reports me jayen.
        </div>
    </div>
</body>
</html>
