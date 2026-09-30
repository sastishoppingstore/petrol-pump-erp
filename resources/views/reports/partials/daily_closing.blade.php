<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Closing Date</th>
                <th>Closing #</th>
                <th class="text-right">Total Litres</th>
                <th class="text-right">Total Sales (Rs.)</th>
                <th class="text-right">Cash Counted</th>
                <th class="text-right">Cash Short/Over</th>
                <th class="text-right">Dip Variance (L)</th>
                <th class="text-center">Status</th>
                <th>Closed By</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['closings'] as $cl)
                <tr>
                    <td class="fw-bold">{{ $cl->closing_date->format('d M Y') }}</td>
                    <td class="font-monospace">{{ $cl->closing_number }}</td>
                    <td class="text-right">{{ number_format((float) $cl->total_fuel_litres, 3) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($cl->total_sales_amount) }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($cl->actual_cash_counted) }}</td>
                    <td class="text-right">
                        @if((float) $cl->cash_variance == 0)
                            <span class="badge bg-success">Balanced</span>
                        @elseif((float) $cl->cash_variance > 0)
                            <span class="badge bg-success">+{{ \App\Support\PakistaniCurrency::format($cl->cash_variance) }}</span>
                        @else
                            <span class="badge bg-danger">{{ \App\Support\PakistaniCurrency::format($cl->cash_variance) }}</span>
                        @endif
                    </td>
                    <td class="text-right">
                        @if((float) $cl->dip_variance_litres >= 0)
                            <span class="badge bg-success">+{{ number_format((float) $cl->dip_variance_litres, 3) }} L</span>
                        @else
                            <span class="badge bg-danger">{{ number_format((float) $cl->dip_variance_litres, 3) }} L</span>
                        @endif
                    </td>
                    <td class="text-center"><span class="badge bg-dark">{{ $cl->status }}</span></td>
                    <td>{{ $cl->closedByUser?->name }}</td>
                </tr>
            @empty
                <tr><td colspan="9" class="text-center text-muted py-4">No daily closing records found in this range.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
