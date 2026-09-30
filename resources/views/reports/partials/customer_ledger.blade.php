@if(!empty($data['customer']))
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="p-3 bg-light rounded border-start border-4 border-primary">
                <small class="text-muted text-uppercase fw-bold">Customer</small>
                <div class="fs-5 fw-bold">{{ $data['customer']->name }}</div>
                <small class="text-muted">{{ $data['customer']->phone ?? 'No phone' }} &bull; Limit: {{ \App\Support\PakistaniCurrency::format($data['customer']->credit_limit) }}</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-light rounded border-start border-4 border-info">
                <small class="text-muted text-uppercase fw-bold">Opening Balance</small>
                <div class="fs-4 fw-bold">{{ \App\Support\PakistaniCurrency::format($data['opening_balance']) }}</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="p-3 bg-light rounded border-start border-4 border-danger">
                <small class="text-muted text-uppercase fw-bold">Closing Outstanding Balance</small>
                <div class="fs-4 fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($data['closing_balance']) }}</div>
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
            <thead class="table-light">
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-right">Debit / Sale (Rs.)</th>
                    <th class="text-right">Credit / Payment (Rs.)</th>
                    <th class="text-right">Balance Outstanding (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-secondary">
                    <td colspan="2" class="fw-bold">Opening Balance B/F</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($data['opening_balance']) }}</td>
                </tr>
                @forelse($data['lines'] as $line)
                    <tr>
                        <td>{{ $line['date'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td class="text-right text-danger fw-bold">{{ ! \App\Support\Money::isZero($line['debit']) ? \App\Support\PakistaniCurrency::format($line['debit']) : '-' }}</td>
                        <td class="text-right text-success fw-bold">{{ ! \App\Support\Money::isZero($line['credit']) ? \App\Support\PakistaniCurrency::format($line['credit']) : '-' }}</td>
                        <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($line['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No transactions in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="2">PERIOD TOTALS / CLOSING BALANCE</td>
                    <td class="text-right text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_debit']) }}</td>
                    <td class="text-right text-success">{{ \App\Support\PakistaniCurrency::format($data['total_credit']) }}</td>
                    <td class="text-right text-danger">{{ \App\Support\PakistaniCurrency::format($data['closing_balance']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <p class="text-muted text-center py-4">Please select a customer to view ledger statement.</p>
@endif
