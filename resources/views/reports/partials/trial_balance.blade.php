<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Total Debits (کل نام)</small>
            <div class="fs-4 fw-bold text-primary">{{ \App\Support\PakistaniCurrency::format($data['total_debit']) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Credits (کل جمع)</small>
            <div class="fs-4 fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_credit']) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 {{ $data['is_balanced'] ? 'border-success' : 'border-danger' }}">
            <small class="text-muted text-uppercase fw-bold">Status (حالت)</small>
            <div class="fs-4 fw-bold {{ $data['is_balanced'] ? 'text-success' : 'text-danger' }}">
                {{ $data['is_balanced'] ? '✓ Balanced (برابر)' : '⚠ Discrepancy' }}
            </div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Code</th>
                <th>Account Title (کھاتہ)</th>
                <th>Urdu Title</th>
                <th>Type</th>
                <th class="text-right">Debit Balance (Rs.)</th>
                <th class="text-right">Credit Balance (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['accounts'] as $a)
                <tr>
                    <td><code>{{ $a['code'] }}</code></td>
                    <td class="fw-bold">{{ $a['name'] }}</td>
                    <td style="font-family: 'Jameel Noori Nastaleeq', Tahoma;">{{ $a['urdu_name'] }}</td>
                    <td><span class="badge bg-secondary">{{ $a['type'] }}</span></td>
                    <td class="text-right fw-bold text-primary">
                        {{ ! \App\Support\Money::isZero($a['debit_balance']) ? \App\Support\PakistaniCurrency::format($a['debit_balance']) : '-' }}
                    </td>
                    <td class="text-right fw-bold text-danger">
                        {{ ! \App\Support\Money::isZero($a['credit_balance']) ? \App\Support\PakistaniCurrency::format($a['credit_balance']) : '-' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No active general ledger accounts with balances.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-dark fw-bold fs-6">
            <tr>
                <td colspan="4">TOTALS (میزان)</td>
                <td class="text-right text-info">{{ \App\Support\PakistaniCurrency::format($data['total_debit']) }}</td>
                <td class="text-right text-warning">{{ \App\Support\PakistaniCurrency::format($data['total_credit']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
