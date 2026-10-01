<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-tile-3d tilt-3d stat-navy">
        <div class="stat-label">Total Volume Received</div>
        <div class="stat-value">{{ number_format((float) $data['total_litres'], 3) }} Litres</div>
        <div class="stat-sub">Tanker Decantations</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-red">
        <div class="stat-label">Total Purchase Value</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</div>
        <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_amount']) }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
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
                    <td class="font-mono fw-bold">{{ $p->purchase_number }}</td>
                    <td>{{ $p->supplier?->name }}</td>
                    <td class="fw-bold">{{ $p->fuelProduct?->name }}</td>
                    <td>{{ $p->tank?->name }}</td>
                    <td>{{ $p->tanker_number ?? '-' }}</td>
                    <td class="text-right fw-bold tabular">{{ number_format((float) $p->volume_received, 3) }}</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($p->purchase_rate) }}</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($p->total_amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No purchases recorded in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="6">TOTAL</td>
                <td class="text-right tabular">{{ number_format((float) $data['total_litres'], 3) }} L</td>
                <td>-</td>
                <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
