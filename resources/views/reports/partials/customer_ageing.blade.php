<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-bold">Current (0 - 30 Days)</small>
            <div class="fs-5 fw-bold text-success">{{ \App\Support\PakistaniCurrency::format($data['totals']['current']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-info">
            <small class="text-muted text-uppercase fw-bold">31 - 60 Days Overdue</small>
            <div class="fs-5 fw-bold text-info">{{ \App\Support\PakistaniCurrency::format($data['totals']['30_days']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-warning">
            <small class="text-muted text-uppercase fw-bold">61 - 90 Days Overdue</small>
            <div class="fs-5 fw-bold text-warning">{{ \App\Support\PakistaniCurrency::format($data['totals']['60_days']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">90+ Days (High Risk)</small>
            <div class="fs-5 fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($data['totals']['90_plus']) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
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
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($r['current']) }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($r['days_30']) }}</td>
                    <td class="text-right text-warning fw-bold">{{ \App\Support\PakistaniCurrency::format($r['days_60']) }}</td>
                    <td class="text-right text-danger fw-bold">{{ \App\Support\PakistaniCurrency::format($r['days_90_plus']) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($r['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No ageing balances recorded.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td>TOTALS</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($data['totals']['current']) }}</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($data['totals']['30_days']) }}</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($data['totals']['60_days']) }}</td>
                <td class="text-right text-danger">{{ \App\Support\PakistaniCurrency::format($data['totals']['90_plus']) }}</td>
                <td class="text-right text-danger">{{ \App\Support\PakistaniCurrency::format($data['totals']['total']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
