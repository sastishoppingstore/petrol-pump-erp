<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="stat-tile-3d tilt-3d stat-navy">
        <div class="stat-label">Total Debits (کل نام)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_debit']) }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-red">
        <div class="stat-label">Total Credits (کل جمع)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_credit']) }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d {{ $data['is_balanced'] ? 'stat-green' : 'stat-red' }}">
        <div class="stat-label">Status (حالت)</div>
        <div class="stat-value">{{ $data['is_balanced'] ? '✓ Balanced (برابر)' : '⚠ Discrepancy' }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
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
                    <td class="text-right fw-bold text-vital-primary tabular">
                        {{ ! \App\Support\Money::isZero($a['debit_balance']) ? \App\Support\PakistaniCurrency::format($a['debit_balance']) : '-' }}
                    </td>
                    <td class="text-right fw-bold text-danger tabular">
                        {{ ! \App\Support\Money::isZero($a['credit_balance']) ? \App\Support\PakistaniCurrency::format($a['credit_balance']) : '-' }}
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No active general ledger accounts with balances.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-900 text-white fw-bold">
            <tr>
                <td colspan="4">TOTALS (میزان)</td>
                <td class="text-right text-sky-300 tabular">{{ \App\Support\PakistaniCurrency::format($data['total_debit']) }}</td>
                <td class="text-right text-amber-300 tabular">{{ \App\Support\PakistaniCurrency::format($data['total_credit']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
