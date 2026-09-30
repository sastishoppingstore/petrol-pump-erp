<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Sales Amount</small>
            <div class="fs-4 fw-bold">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Total Volume Dispensed</small>
            <div class="fs-4 fw-bold">{{ number_format((float) $data['total_litres'], 3) }} Litres</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-bold">Gross Margin (نفع خام)</small>
            <div class="fs-4 fw-bold text-success">{{ \App\Support\PakistaniCurrency::format($data['gross_margin']) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Fuel Product (مصنوعات)</th>
                <th>Code</th>
                <th class="text-right">Volume (Litres)</th>
                <th class="text-right">Avg Rate (Rs./L)</th>
                <th class="text-right">Sales Amount (Rs.)</th>
                <th class="text-right">Cost (Rs.)</th>
                <th class="text-right">Gross Margin (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['products'] as $p)
                <tr>
                    <td class="fw-bold">{{ $p['name'] }}</td>
                    <td><code>{{ $p['code'] }}</code></td>
                    <td class="text-right">{{ number_format((float) $p['litres'], 3) }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($p['average_rate']) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($p['amount']) }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($p['cost']) }}</td>
                    <td class="text-right text-success fw-bold">{{ \App\Support\PakistaniCurrency::format($p['margin']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No fuel sales recorded in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="2">GRAND TOTAL</td>
                <td class="text-right">{{ number_format((float) $data['total_litres'], 3) }} L</td>
                <td class="text-right">-</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($data['total_cost']) }}</td>
                <td class="text-right text-success">{{ \App\Support\PakistaniCurrency::format($data['gross_margin']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
