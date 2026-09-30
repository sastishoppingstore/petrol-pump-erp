<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Selected Bank Account</small>
            <div class="fs-5 fw-bold">{{ $data['selected_account']?->account_title ?? 'All Accounts' }}</div>
            <small class="text-muted">{{ $data['selected_account']?->bank?->name }} ({{ $data['selected_account']?->account_number }})</small>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-bold">Total Period Deposits</small>
            <div class="fs-4 fw-bold text-success">{{ \App\Support\PakistaniCurrency::format($data['total_deposits']) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Date / Time</th>
                <th>Deposit Reference</th>
                <th>Shift</th>
                <th>Deposit Type</th>
                <th class="text-right">Balance Before</th>
                <th class="text-right">Amount (Rs.)</th>
                <th class="text-right">Balance After</th>
                <th>Deposited By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['deposits'] as $d)
                <tr>
                    <td>{{ $d->deposited_at->format('d M Y, h:i A') }}</td>
                    <td class="font-monospace fw-bold">{{ $d->reference_number }}</td>
                    <td>{{ $d->shift ? '#' . $d->shift->shift_number : '-' }}</td>
                    <td><span class="badge bg-info text-dark">{{ $d->deposit_type }}</span></td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($d->balance_before) }}</td>
                    <td class="text-right text-success fw-bold">+{{ \App\Support\PakistaniCurrency::format($d->amount) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($d->balance_after) }}</td>
                    <td>{{ $d->depositor?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No deposits recorded for this bank account in this range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="5">TOTAL DEPOSITS</td>
                <td class="text-right text-success">+{{ \App\Support\PakistaniCurrency::format($data['total_deposits']) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
