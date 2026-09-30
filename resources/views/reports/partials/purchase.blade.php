<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Total Volume Received</small>
            <div class="fs-4 fw-bold">{{ number_format((float) $data['total_litres'], 3) }} Litres</div>
            <small class="text-muted">Tanker Decantations</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Purchase Value</small>
            <div class="fs-4 fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</div>
            <small class="text-danger">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_amount']) }}</small>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Purchase #</th>
                <th>Supplier</th>
                <th>Product</th>
                <th>Tank</th>
                <th>Tanker #</th>
                <th class="text-right">Volume (L)</th>
                <th class="text-right">Rate (Rs./L)</th>
                <th class="text-right">Total (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['purchases'] as $p)
                <tr>
                    <td>{{ $p->purchase_date->format('d M Y') }}</td>
                    <td class="font-monospace fw-bold">{{ $p->purchase_number }}</td>
                    <td>{{ $p->supplier?->name }}</td>
                    <td class="fw-bold">{{ $p->fuelProduct?->name }}</td>
                    <td>{{ $p->tank?->name }}</td>
                    <td>{{ $p->tanker_number ?? '-' }}</td>
                    <td class="text-right fw-bold">{{ number_format((float) $p->volume_received, 3) }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($p->purchase_rate) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($p->total_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No purchases recorded in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="6">TOTAL</td>
                <td class="text-right">{{ number_format((float) $data['total_litres'], 3) }} L</td>
                <td>-</td>
                <td class="text-right text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
