<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Cashier Name</th>
                <th>Employee Code</th>
                <th class="text-center">Shifts Worked</th>
                <th class="text-center">Sales Count</th>
                <th class="text-right">Volume (Litres)</th>
                <th class="text-right">Total Sales (Rs.)</th>
                <th class="text-right">Cash Handed Over</th>
                <th class="text-right">Cash Short / Over</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['cashiers'] as $c)
                <tr>
                    <td class="fw-bold">{{ $c['user']->name }}</td>
                    <td><code>{{ $c['user']->employee_code ?? 'EMP-' . $c['user']->id }}</code></td>
                    <td class="text-center">{{ $c['shifts_count'] }}</td>
                    <td class="text-center">{{ $c['sales_count'] }}</td>
                    <td class="text-right">{{ number_format((float) $c['total_litres'], 3) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($c['total_sales']) }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($c['cash_handed']) }}</td>
                    <td class="text-right">
                        @if((float) $c['cash_variance'] == 0)
                            <span class="badge bg-success">Balanced</span>
                        @elseif((float) $c['cash_variance'] > 0)
                            <span class="badge bg-success">+{{ \App\Support\PakistaniCurrency::format($c['cash_variance']) }}</span>
                        @else
                            <span class="badge bg-danger">{{ \App\Support\PakistaniCurrency::format($c['cash_variance']) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No cashier shift records in this period.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
