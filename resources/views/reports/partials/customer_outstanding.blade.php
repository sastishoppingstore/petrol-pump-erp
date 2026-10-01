<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-tile-3d tilt-3d stat-red">
        <div class="stat-label">Total Udhaar Outstanding (کل ادھار بقایا)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_outstanding']) }}</div>
        <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_outstanding']) }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-navy">
        <div class="stat-label">Active Credit Customers Count</div>
        <div class="stat-value">{{ count($data['customers']) }} Customers</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
            <tr>
                <th>Customer Code</th>
                <th>Customer Name</th>
                <th>Phone</th>
                <th class="text-right">Credit Limit (Rs.)</th>
                <th class="text-right">Current Outstanding (Rs.)</th>
                <th class="text-right">Available Credit (Rs.)</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['customers'] as $row)
                <tr>
                    <td><code>{{ $row['customer']->code }}</code></td>
                    <td class="fw-bold">{{ $row['customer']->name }}</td>
                    <td>{{ $row['customer']->phone ?? '-' }}</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($row['credit_limit']) }}</td>
                    <td class="text-right text-danger fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($row['balance']) }}</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($row['available_credit']) }}</td>
                    <td class="text-center">
                        <a href="{{ route('reports.show', ['report' => 'customer-ledger', 'customer_id' => $row['customer']->id]) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                            Statement &rarr;
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No outstanding customer balances found.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="4">TOTAL OUTSTANDING</td>
                <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($data['total_outstanding']) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
