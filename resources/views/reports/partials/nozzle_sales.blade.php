<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-tile-3d stat-red">
        <div class="stat-label">Total Dispensed Volume</div>
        <div class="stat-value">{{ number_format((float) $data['grand_litres'], 3) }} Litres</div>
    </div>
    <div class="stat-tile-3d stat-green">
        <div class="stat-label">Total Sales Amount</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['grand_amount']) }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
            <tr>
                <th>Dispenser</th>
                <th>Nozzle #</th>
                <th>Product</th>
                <th class="text-right">Opening Meter</th>
                <th class="text-right">Closing Meter</th>
                <th class="text-right">Litres Sold</th>
                <th class="text-right">Total Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['nozzles'] as $n)
                <tr>
                    <td>{{ $n['dispenser'] }}</td>
                    <td class="fw-bold">Nozzle #{{ $n['nozzle']->nozzle_number }}</td>
                    <td>{{ $n['product'] }}</td>
                    <td class="text-right font-mono tabular">{{ number_format((float) $n['opening_meter'], 3) }}</td>
                    <td class="text-right font-mono tabular">{{ number_format((float) $n['closing_meter'], 3) }}</td>
                    <td class="text-right fw-bold tabular">{{ number_format((float) $n['litres'], 3) }}</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($n['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No nozzle sales records found.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="5">TOTAL</td>
                <td class="text-right tabular">{{ number_format((float) $data['grand_litres'], 3) }} L</td>
                <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['grand_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
