<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-info">
            <small class="text-muted text-uppercase fw-bold">Opening Balance</small>
            <div class="fs-4 fw-bold">{{ \App\Support\PakistaniCurrency::format($data['opening_balance']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-bold">Total Cash Receipts (In)</small>
            <div class="fs-4 fw-bold text-success">+{{ \App\Support\PakistaniCurrency::format($data['total_in']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Cash Paid Out (Out)</small>
            <div class="fs-4 fw-bold text-danger">-{{ \App\Support\PakistaniCurrency::format($data['total_out']) }}</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Closing Till Balance</small>
            <div class="fs-4 fw-bold text-primary">{{ \App\Support\PakistaniCurrency::format($data['closing_balance']) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
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
            <tr class="table-secondary">
                <td colspan="3" class="fw-bold">Opening Balance B/F</td>
                <td class="text-right">-</td>
                <td class="text-right">-</td>
                <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($data['opening_balance']) }}</td>
            </tr>
            @forelse($data['lines'] as $line)
                <tr>
                    <td>{{ $line['date'] }}</td>
                    <td><code>{{ $line['entry_number'] }}</code></td>
                    <td>{{ $line['narration'] }} <small class="text-muted">{{ $line['memo'] }}</small></td>
                    <td class="text-right text-success fw-bold">{{ ! \App\Support\Money::isZero($line['debit']) ? \App\Support\PakistaniCurrency::format($line['debit']) : '-' }}</td>
                    <td class="text-right text-danger fw-bold">{{ ! \App\Support\Money::isZero($line['credit']) ? \App\Support\PakistaniCurrency::format($line['credit']) : '-' }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($line['running_balance']) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="text-center text-muted py-4">No cash transactions in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="3">CLOSING CASH BALANCE</td>
                <td class="text-right text-success">+{{ \App\Support\PakistaniCurrency::format($data['total_in']) }}</td>
                <td class="text-right text-danger">-{{ \App\Support\PakistaniCurrency::format($data['total_out']) }}</td>
                <td class="text-right text-primary">{{ \App\Support\PakistaniCurrency::format($data['closing_balance']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
