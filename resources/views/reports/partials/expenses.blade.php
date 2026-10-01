<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-tile-3d tilt-3d stat-red">
        <div class="stat-label">Total Operating Expenses (کل اخراجات)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</div>
        <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['total_amount']) }}</div>
    </div>
</div>

<h3 class="text-center text-lg font-extrabold text-slate-800 dark:text-white mb-4">Expenses by Category (بلحاظ مدات)</h3>
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 mb-8">
    @foreach($data['by_category'] as $cat => $amount)
        <div class="glass-card card-3d p-4 text-center">
            <div class="text-xs font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $cat }}</div>
            <div class="mt-1 text-xl font-extrabold text-slate-800 dark:text-white tabular">{{ \App\Support\PakistaniCurrency::format($amount) }}</div>
        </div>
    @endforeach
</div>

<h3 class="text-center text-lg font-extrabold text-slate-800 dark:text-white mb-4">Detailed Expense Vouchers</h3>
<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
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
                    <td class="font-mono"><code>{{ $e->expense_number }}</code></td>
                    <td><span class="badge bg-secondary">{{ $e->category?->name }}</span></td>
                    <td class="fw-bold">{{ $e->title }}</td>
                    <td>{{ $e->payee ?? '-' }}</td>
                    <td><span class="badge bg-info text-dark">{{ $e->payment_method }}</span></td>
                    <td class="text-right fw-bold text-danger tabular">{{ \App\Support\PakistaniCurrency::format($e->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No expenses recorded in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="6">TOTAL</td>
                <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($data['total_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
