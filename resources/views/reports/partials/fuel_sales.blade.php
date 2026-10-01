<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="stat-tile-3d tilt-3d stat-red">
        <div class="stat-label">Total Sales Amount</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-navy">
        <div class="stat-label">Total Volume Dispensed</div>
        <div class="stat-value">{{ number_format((float) $data['total_litres'], 3) }} Litres</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-green">
        <div class="stat-label">Gross Margin (نفع خام)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['gross_margin']) }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
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
                    <td class="text-right tabular">{{ number_format((float) $p['litres'], 3) }}</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($p['average_rate']) }}</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($p['amount']) }}</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($p['cost']) }}</td>
                    <td class="text-right text-success fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($p['margin']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No fuel sales recorded in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="2">GRAND TOTAL</td>
                <td class="text-right tabular">{{ number_format((float) $data['total_litres'], 3) }} L</td>
                <td class="text-right">-</td>
                <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</td>
                <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['total_cost']) }}</td>
                <td class="text-right text-success tabular">{{ \App\Support\PakistaniCurrency::format($data['gross_margin']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
