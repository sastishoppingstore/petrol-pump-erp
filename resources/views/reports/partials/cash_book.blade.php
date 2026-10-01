<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
    <div class="stat-tile-3d tilt-3d stat-slate">
        <div class="stat-label">Opening Balance</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['opening_balance']) }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-green">
        <div class="stat-label">Total Cash Receipts (In)</div>
        <div class="stat-value">+{{ \App\Support\PakistaniCurrency::format($data['total_in']) }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-red">
        <div class="stat-label">Total Cash Paid Out (Out)</div>
        <div class="stat-value">-{{ \App\Support\PakistaniCurrency::format($data['total_out']) }}</div>
    </div>
    <div class="stat-tile-3d tilt-3d stat-navy">
        <div class="stat-label">Closing Till Balance</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['closing_balance']) }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
            <tr>
                <th>Date</th>
                <th>Entry #</th>
                <th>Narration / Memo</th>
                <th class="text-right">Receipt (Dr.)</th>
                <th class="text-right">Payment (Cr.)</th>
                <th class="text-right">Running Balance (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            <tr class="bg-slate-100 dark:bg-slate-800">
                <td colspan="3" class="fw-bold">Opening Balance B/F</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($data['opening_balance']) }}</td>
            </tr>
            @forelse($data['lines'] as $line)
                <tr>
                    <td>{{ $line['date'] }}</td>
                    <td><code>{{ $line['entry_number'] }}</code></td>
                    <td>{{ $line['narration'] }} <small class="text-muted">{{ $line['memo'] }}</small></td>
                    <td class="text-right text-success fw-bold tabular">{{ ! \App\Support\Money::isZero($line['debit']) ? \App\Support\PakistaniCurrency::format($line['debit']) : '-' }}</td>
                    <td class="text-right text-danger fw-bold tabular">{{ ! \App\Support\Money::isZero($line['credit']) ? \App\Support\PakistaniCurrency::format($line['credit']) : '-' }}</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($line['running_balance']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No cash transactions in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="3">CLOSING CASH BALANCE</td>
                <td class="text-right text-success tabular">+{{ \App\Support\PakistaniCurrency::format($data['total_in']) }}</td>
                <td class="text-right text-danger tabular">-{{ \App\Support\PakistaniCurrency::format($data['total_out']) }}</td>
                <td class="text-right text-vital-primary tabular">{{ \App\Support\PakistaniCurrency::format($data['closing_balance']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
