<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Sales (کل فروخت)</small>
            <div class="fs-4 fw-bold">{{ \App\Support\PakistaniCurrency::format($data['total_sales']) }}</div>
            <small class="text-danger">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_sales']) }}</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Total Fuel Volume</small>
            <div class="fs-4 fw-bold">{{ number_format((float) $data['total_litres'], 3) }} L</div>
            <small class="text-muted">Litres Dispensed</small>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-bold">Cash Sales (نقد)</small>
            <div class="fs-4 fw-bold">{{ \App\Support\PakistaniCurrency::format($data['cash_total']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-warning">
            <small class="text-muted text-uppercase fw-bold">Udhaar Sales (ادھار)</small>
            <div class="fs-4 fw-bold">{{ \App\Support\PakistaniCurrency::format($data['credit_total']) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
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
                    <td class="font-monospace fw-bold">{{ $sale->invoice_number }}</td>
                    <td>{{ $sale->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $sale->user?->name }}</td>
                    <td>{{ $sale->customer?->name ?? 'Walk-in Forecourt' }}</td>
                    <td class="text-right">{{ number_format((float) $sale->total_litres, 3) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($sale->total) }}</td>
                    <td class="text-center"><span class="badge bg-success">{{ $sale->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No sales recorded for this period.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="4">TOTAL</td>
                <td class="text-right">{{ number_format((float) $data['total_litres'], 3) }} L</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($data['total_sales']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
