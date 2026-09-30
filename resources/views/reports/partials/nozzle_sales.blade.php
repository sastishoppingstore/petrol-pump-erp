<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Total Dispensed Volume</small>
            <div class="fs-4 fw-bold">{{ number_format((float) $data['grand_litres'], 3) }} Litres</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-bold">Total Sales Amount</small>
            <div class="fs-4 fw-bold">{{ \App\Support\PakistaniCurrency::format($data['grand_amount']) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
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
                    <td class="text-right font-monospace">{{ number_format((float) $n['opening_meter'], 3) }}</td>
                    <td class="text-right font-monospace">{{ number_format((float) $n['closing_meter'], 3) }}</td>
                    <td class="text-right fw-bold">{{ number_format((float) $n['litres'], 3) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($n['amount']) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No nozzle sales records found.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="5">TOTAL</td>
                <td class="text-right">{{ number_format((float) $data['grand_litres'], 3) }} L</td>
                <td class="text-right">{{ \App\Support\PakistaniCurrency::format($data['grand_amount']) }}</td>
            </tr>
        </tfoot>
    </table>
</div>
