<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Auto Report {{ strtoupper((string) $report->period) }} — {{ $summary['branch'] ?? $report->branch?->name ?? '' }}</title>
    <style>
        @page { size: A4 portrait; margin: 12mm 15mm; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 12px; color: #1B1B1B; margin: 0; padding: 0; }
        .header-box { border-bottom: 2px solid #D71920; padding-bottom: 10px; margin-bottom: 15px; }
        .station-name { font-size: 18px; font-weight: bold; color: #D71920; text-transform: uppercase; margin: 0; }
        .report-title { font-size: 15px; font-weight: bold; margin-top: 8px; }
        .meta-info { font-size: 11px; color: #666; margin-top: 3px; }
        table.report-table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        table.report-table th { background-color: #F6F6F6; border: 1px solid #DDD; padding: 6px 8px; text-align: left; font-weight: bold; }
        table.report-table td { border: 1px solid #DDD; padding: 6px 8px; }
        .text-right { text-align: right; }
        .fw-bold { font-weight: bold; }
        h3.section { font-size: 13px; margin: 18px 0 4px; color: #A30F15; }
        .kpi-table td { background: #F8F9FA; border-left: 3px solid #D71920; width: 25%; }
        .kpi-value { font-size: 15px; font-weight: bold; color: #1B1B1B; }
        .kpi-label { font-size: 10px; color: #666; text-transform: uppercase; }
        .footer-note { margin-top: 25px; border-top: 1px solid #EEE; padding-top: 8px; font-size: 10px; color: #777; }
    </style>
</head>
<body>
    <div class="header-box">
        <p class="station-name">{{ $summary['branch'] ?? $report->branch?->name ?? 'Fuel Station' }}</p>
        <div class="report-title">Auto Report — {{ strtoupper((string) $report->period) }}</div>
        <div class="meta-info">
            Period: {{ $summary['period_start'] ?? '—' }} → {{ $summary['period_end'] ?? '—' }}
            &nbsp;•&nbsp; Report Date: {{ $report->report_date?->format('d M Y') }}
            &nbsp;•&nbsp; Generated: {{ $summary['generated_at'] ?? $report->updated_at?->format('Y-m-d H:i') }}
        </div>
    </div>

    <table class="report-table kpi-table">
        <tr>
            <td><div class="kpi-label">Total Sales</div><div class="kpi-value">Rs {{ number_format((float) ($summary['total_sales'] ?? 0), 2) }}</div></td>
            <td><div class="kpi-label">Total Litres</div><div class="kpi-value">{{ number_format((float) ($summary['total_litres'] ?? 0), 1) }} L</div></td>
            <td><div class="kpi-label">Transactions</div><div class="kpi-value">{{ number_format((int) ($summary['transaction_count'] ?? 0)) }}</div></td>
            <td><div class="kpi-label">Net Profit</div><div class="kpi-value">Rs {{ number_format((float) ($summary['net_profit'] ?? 0), 2) }}</div></td>
        </tr>
    </table>

    <h3 class="section">Payment Breakdown</h3>
    <table class="report-table">
        <tr><th>Method</th><th class="text-right">Amount (Rs)</th></tr>
        <tr><td>Cash</td><td class="text-right">{{ number_format((float) ($summary['cash_sales'] ?? 0), 2) }}</td></tr>
        <tr><td>Card</td><td class="text-right">{{ number_format((float) ($summary['card_sales'] ?? 0), 2) }}</td></tr>
        <tr><td>Credit (Udhaar)</td><td class="text-right">{{ number_format((float) ($summary['credit_sales'] ?? 0), 2) }}</td></tr>
        <tr><td>Other</td><td class="text-right">{{ number_format((float) ($summary['other_sales'] ?? 0), 2) }}</td></tr>
        <tr><td class="fw-bold">Total</td><td class="text-right fw-bold">{{ number_format((float) ($summary['total_sales'] ?? 0), 2) }}</td></tr>
    </table>

    <h3 class="section">Profitability</h3>
    <table class="report-table">
        <tr><td>Total Revenue</td><td class="text-right">Rs {{ number_format((float) ($summary['total_revenue'] ?? 0), 2) }}</td></tr>
        <tr><td>COGS</td><td class="text-right">Rs {{ number_format((float) ($summary['cogs'] ?? 0), 2) }}</td></tr>
        <tr><td>Gross Margin</td><td class="text-right">Rs {{ number_format((float) ($summary['gross_margin'] ?? 0), 2) }} ({{ number_format((float) ($summary['gross_margin_percentage'] ?? 0), 2) }}%)</td></tr>
        <tr><td>Expenses</td><td class="text-right">Rs {{ number_format((float) ($summary['expenses'] ?? 0), 2) }}</td></tr>
        <tr><td class="fw-bold">Net Profit</td><td class="text-right fw-bold">Rs {{ number_format((float) ($summary['net_profit'] ?? 0), 2) }} ({{ number_format((float) ($summary['net_profit_percentage'] ?? 0), 2) }}%)</td></tr>
    </table>

    <h3 class="section">Stock Movement</h3>
    <table class="report-table">
        <tr><th>Type</th><th class="text-right">Litres</th></tr>
        <tr><td>Purchases</td><td class="text-right">{{ number_format((float) ($summary['purchases'] ?? 0), 1) }}</td></tr>
        <tr><td>Sales</td><td class="text-right">{{ number_format((float) ($summary['sales'] ?? 0), 1) }}</td></tr>
        <tr><td>Variance</td><td class="text-right">{{ number_format((float) ($summary['variance'] ?? 0), 1) }}</td></tr>
    </table>

    @if (($fbr['count'] ?? 0) > 0)
        <h3 class="section">FBR Fiscalised Sales</h3>
        <table class="report-table">
            <tr><td>FBR Invoices (is period me)</td><td class="text-right">{{ number_format((int) $fbr['count']) }}</td></tr>
            <tr><td>FBR Invoices Total</td><td class="text-right">Rs {{ number_format((float) $fbr['total'], 2) }}</td></tr>
        </table>
    @endif

    <h3 class="section">Fuel Breakdown</h3>
    <table class="report-table">
        <tr><th>Fuel</th><th class="text-right">Litres</th><th class="text-right">Revenue (Rs)</th></tr>
        @forelse (($summary['fuel_breakdown'] ?? []) as $fuel)
            <tr>
                <td>{{ $fuel['fuel'] ?? 'Unknown' }}</td>
                <td class="text-right">{{ number_format((float) ($fuel['litres'] ?? 0), 1) }}</td>
                <td class="text-right">{{ number_format((float) ($fuel['revenue'] ?? 0), 2) }}</td>
            </tr>
        @empty
            <tr><td colspan="3">Is period me koi fuel sale record nahi hui.</td></tr>
        @endforelse
    </table>

    <div class="footer-note">
        Ye report system ne auto-generate ki hai — figures tamam asal sales/stock data se hain.
    </div>
</body>
</html>
