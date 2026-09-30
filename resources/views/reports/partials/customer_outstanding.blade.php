<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Udhaar Outstanding (کل ادھار بقایا)</small>
            <div class="fs-4 fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_outstanding']) }}</div>
            <small class="text-danger">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_outstanding']) }}</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Active Credit Customers Count</small>
            <div class="fs-4 fw-bold">{{ count($data['customers']) }} Customers</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
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
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($row['credit_limit']) }}</td>
                    <td class="text-right text-danger fw-bold">{{ \App\Support\PakistaniCurrency::format($row['balance']) }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($row['available_credit']) }}</td>
                    <td class="text-center">
                        <a href="{{ route('reports.show', ['report' => 'customer-ledger', 'customer_id' => $row['customer']->id]) }}" class="btn btn-sm btn-outline-danger py-0 px-2">
                            Statement &rarr;
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No outstanding customer balances found.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="4">TOTAL OUTSTANDING</td>
                <td class="text-right text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_outstanding']) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
