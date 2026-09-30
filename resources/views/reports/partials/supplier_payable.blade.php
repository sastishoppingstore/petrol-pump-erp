<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Supplier Payables (سپلائرز واجب الادا)</small>
            <div class="fs-4 fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_payable']) }}</div>
            <small class="text-danger">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_payable']) }}</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Active Suppliers Count</small>
            <div class="fs-4 fw-bold">{{ count($data['suppliers']) }} Suppliers</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Code</th>
                <th>Supplier Name</th>
                <th>Contact Person</th>
                <th>Phone</th>
                <th class="text-right">Balance Payable (Rs.)</th>
                <th class="text-center">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['suppliers'] as $row)
                <tr>
                    <td><code>{{ $row['supplier']->code }}</code></td>
                    <td class="fw-bold">{{ $row['supplier']->name }}</td>
                    <td>{{ $row['supplier']->contact_person ?? '-' }}</td>
                    <td>{{ $row['supplier']->phone ?? '-' }}</td>
                    <td class="text-right text-danger fw-bold">{{ \App\Support\PakistaniCurrency::format($row['balance']) }}</td>
                    <td class="text-center">
                        <a href="{{ route('reports.show', ['report' => 'supplier-ledger', 'supplier_id' => $row['supplier']->id]) }}" class="btn btn-sm btn-outline-danger py-0 px-2">
                            Statement &rarr;
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No outstanding supplier balances.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="4">TOTAL PAYABLE</td>
                <td class="text-right text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_payable']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
