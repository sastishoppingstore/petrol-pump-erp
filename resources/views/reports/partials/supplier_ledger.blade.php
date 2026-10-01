@if(!empty($data['supplier']))
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Supplier (سپلائر)</div>
            <div class="stat-value">{{ $data['supplier']->name }}</div>
            <div class="stat-sub">{{ $data['supplier']->contact_person ?? 'Oil Marketing Co.' }}</div>
        </div>
        <div class="stat-tile-3d stat-slate">
            <div class="stat-label">Opening Balance</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['opening_balance']) }}</div>
        </div>
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Closing Payable Balance</div>
            <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['closing_balance']) }}</div>
        </div>
    </div>

    <div class="table-3d">
        <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Description</th>
                    <th class="text-right">Debit / Payment (Rs.)</th>
                    <th class="text-right">Credit / Purchase (Rs.)</th>
                    <th class="text-right">Running Balance (Rs.)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="bg-slate-100 dark:bg-slate-800">
                    <td colspan="2" class="fw-bold">Opening Balance B/F</td>
                    <td class="text-right">-</td>
                    <td class="text-right">-</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($data['opening_balance']) }}</td>
                </tr>
                @forelse($data['lines'] as $line)
                    <tr>
                        <td>{{ $line['date'] }}</td>
                        <td>{{ $line['description'] }}</td>
                        <td class="text-right text-success fw-bold tabular">{{ ! \App\Support\Money::isZero($line['debit']) ? \App\Support\PakistaniCurrency::format($line['debit']) : '-' }}</td>
                        <td class="text-right text-danger fw-bold tabular">{{ ! \App\Support\Money::isZero($line['credit']) ? \App\Support\PakistaniCurrency::format($line['credit']) : '-' }}</td>
                        <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($line['balance']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-3">No supplier transactions in this period.</td></tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
                <tr>
                    <td colspan="2">PERIOD TOTALS</td>
                    <td class="text-right text-success tabular">{{ \App\Support\PakistaniCurrency::format($data['total_debit']) }}</td>
                    <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($data['total_credit']) }}</td>
                    <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($data['closing_balance']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
@else
    <div class="glass-card p-8 text-center">
        <p class="font-semibold text-slate-500 dark:text-slate-400">Please select a supplier to view ledger statement.</p>
    </div>
@endif
