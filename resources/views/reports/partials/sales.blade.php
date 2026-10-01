<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="stat-tile-3d stat-red">
        <div class="stat-label">Total Sales (کل فروخت)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_sales']) }}</div>
        <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_sales']) }}</div>
    </div>
    <div class="stat-tile-3d stat-navy">
        <div class="stat-label">Total Fuel Volume</div>
        <div class="stat-value">{{ number_format((float) $data['total_litres'], 3) }} L</div>
        <div class="stat-sub">Litres Dispensed</div>
    </div>
    <div class="stat-tile-3d stat-green">
        <div class="stat-label">Cash Sales (نقد)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['cash_total']) }}</div>
    </div>
    <div class="stat-tile-3d stat-amber">
        <div class="stat-label">Udhaar Sales (ادھار)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['credit_total']) }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
            <tr>
                <th>Invoice #</th>
                <th>Date / Time</th>
                <th>Cashier</th>
                <th>Customer / Vehicle</th>
                <th class="text-right">Litres</th>
                <th class="text-right">Total (Rs.)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['sales'] as $sale)
                <tr>
                    <td class="font-mono fw-bold">{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $sale->user?->name }}</td>
                    <td>{{ $sale->customer?->name ?? 'Walk-in Forecourt' }}</td>
                    <td class="text-right tabular">{{ number_format((float) $sale->total_litres, 3) }}</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($sale->total) }}</td>
                    <td class="text-center"><span class="badge bg-success">{{ $sale->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No sales recorded for this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="4">TOTAL</td>
                <td class="text-right tabular">{{ number_format((float) $data['total_litres'], 3) }} L</td>
                <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['total_sales']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
