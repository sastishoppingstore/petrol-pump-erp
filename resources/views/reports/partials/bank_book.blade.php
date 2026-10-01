<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-tile-3d stat-navy">
        <div class="stat-label">Selected Bank Account</div>
        <div class="stat-value">{{ $data['selected_account']?->account_title ?? 'All Accounts' }}</div>
        <div class="stat-sub">{{ $data['selected_account']?->bank?->name }} ({{ $data['selected_account']?->account_number }})</div>
    </div>
    <div class="stat-tile-3d stat-green">
        <div class="stat-label">Total Period Deposits</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_deposits']) }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
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
                    <td class="font-mono fw-bold">{{ $d->reference_number }}</td>
                    <td>{{ $d->shift ? '#' . $d->shift->shift_number : '-' }}</td>
                    <td><span class="badge bg-info text-dark">{{ $d->deposit_type }}</span></td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($d->balance_before) }}</td>
                    <td class="text-right text-success fw-bold tabular">+{{ \App\Support\PakistaniCurrency::format($d->amount) }}</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($d->balance_after) }}</td>
                    <td>{{ $d->depositor?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center text-muted py-4">No deposits recorded for this bank account in this range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="5">TOTAL DEPOSITS</td>
                <td class="text-right text-success tabular">+{{ \App\Support\PakistaniCurrency::format($data['total_deposits']) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
