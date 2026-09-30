<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Operating Expenses (کل اخراجات)</small>
            <div class="fs-4 fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</div>
            <small class="text-danger">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_amount']) }}</small>
        </div>
    </div>
</div>

<h5 class="fw-bold mb-3">Expenses by Category (بلحاظ مدات)</h5>
<div class="row g-3 mb-4">
    @foreach($data['by_category'] as $cat => $amount)
        <div class="col-md-4">
            <div class="card border p-2">
                <small class="text-muted text-uppercase">{{ $cat }}</small>
                <div class="fs-5 fw-bold text-dark">{{ \App\Support\PakistaniCurrency::format($amount) }}</div>
            </div>
        </div>
    @endforeach
</div>

<h5 class="fw-bold mb-3">Detailed Expense Vouchers</h5>
<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Date</th>
                <th>Voucher #</th>
                <th>Category</th>
                <th>Title / Description</th>
                <th>Payee</th>
                <th>Payment Mode</th>
                <th class="text-right">Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['expenses'] as $e)
                <tr>
                    <td>{{ $e->date->format('d M Y') }}</td>
                    <td class="font-monospace"><code>{{ $e->expense_number }}</code></td>
                    <td><span class="badge bg-secondary">{{ $e->category?->name }}</span></td>
                    <td class="fw-bold">{{ $e->title }}</td>
                    <td>{{ $e->payee ?? '-' }}</td>
                    <td><span class="badge bg-info text-dark">{{ $e->payment_method }}</span></td>
                    <td class="text-right fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($e->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No expenses recorded in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="6">TOTAL</td>
                <td class="text-right text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
