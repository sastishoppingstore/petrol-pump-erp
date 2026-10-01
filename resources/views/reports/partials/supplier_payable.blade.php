<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-tile-3d tilt-3d stat-red">
        <div class="stat-label">Total Supplier Payables (سپلائرز واجب الادا)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_payable']) }}</div>
        <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_payable']) }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-navy">
        <div class="stat-label">Active Suppliers Count</div>
        <div class="stat-value">{{ count($data['suppliers']) }} Suppliers</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
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
                    <td class="text-right text-danger fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($row['balance']) }}</td>
                    <td class="text-center">
                        <a href="{{ route('reports.show', ['report' => 'supplier-ledger', 'supplier_id' => $row['supplier']->id]) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                            Statement &rarr;
                        </a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No outstanding supplier balances.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="4">TOTAL PAYABLE</td>
                <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($data['total_payable']) }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</div>
