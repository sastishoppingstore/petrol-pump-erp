<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="stat-tile-3d stat-green">
        <div class="stat-label">Current (0 - 30 Days)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['totals']['current']) }}</div>
    </div>
    <div class="stat-tile-3d stat-navy">
        <div class="stat-label">31 - 60 Days Overdue</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['totals']['30_days']) }}</div>
    </div>
    <div class="stat-tile-3d stat-amber">
        <div class="stat-label">61 - 90 Days Overdue</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['totals']['60_days']) }}</div>
    </div>
    <div class="stat-tile-3d stat-red">
        <div class="stat-label">90+ Days (High Risk)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['totals']['90_plus']) }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
            <tr>
                <th>Customer Code & Name</th>
                <th class="text-right">Current (0-30d)</th>
                <th class="text-right">31-60 Days</th>
                <th class="text-right">61-90 Days</th>
                <th class="text-right">90+ Days</th>
                <th class="text-right">Total Balance</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['rows'] as $r)
                <tr>
                    <td class="fw-bold">{{ $r['customer']->name }} <code>({{ $r['customer']->code }})</code></td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($r['current']) }}</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($r['days_30']) }}</td>
                    <td class="text-right text-warning fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($r['days_60']) }}</td>
                    <td class="text-right text-danger fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($r['days_90_plus']) }}</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($r['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No ageing balances recorded.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td>TOTALS</td>
                <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['totals']['current']) }}</td>
                <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['totals']['30_days']) }}</td>
                <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['totals']['60_days']) }}</td>
                <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($data['totals']['90_plus']) }}</td>
                <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($data['totals']['total']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
