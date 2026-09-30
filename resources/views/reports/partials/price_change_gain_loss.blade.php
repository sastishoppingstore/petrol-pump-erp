<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Net Inventory Price-Change Windfall / (Loss)</small>
            <div class="fs-4 fw-bold">
                @if((float) $data['total_gain_loss'] >= 0)
                    <span class="text-success">+{{ \App\Support\PakistaniCurrency::format($data['total_gain_loss']) }}</span>
                @else
                    <span class="text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_gain_loss']) }}</span>
                @endif
            </div>
            <small class="text-muted">Calculated on underground tank inventory at moment of price revision</small>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
            <tr>
                <th>Revision Date / Time</th>
                <th>Fuel Product</th>
                <th class="text-right">Old Price (Rs./L)</th>
                <th class="text-right">New Price (Rs./L)</th>
                <th class="text-right">Price Delta (Rs./L)</th>
                <th class="text-right">Tank Stock (L)</th>
                <th class="text-right">Gain / (Loss) Amount (Rs.)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['changes'] as $c)
                <tr>
                    <td>{{ $c['date'] }}</td>
                    <td class="fw-bold">{{ $c['product'] }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($c['old_rate']) }}</td>
                    <td class="text-right fw-bold">{{ \App\Support\PakistaniCurrency::format($c['new_rate']) }}</td>
                    <td class="text-right">
                        @if((float) $c['rate_diff'] >= 0)
                            <span class="badge bg-success">+{{ \App\Support\PakistaniCurrency::format($c['rate_diff']) }}</span>
                        @else
                            <span class="badge bg-danger">{{ \App\Support\PakistaniCurrency::format($c['rate_diff']) }}</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format((float) $c['stock_litres'], 3) }}</td>
                    <td class="text-right fw-bold">
                        @if((float) $c['gain_loss'] >= 0)
                            <span class="text-success">+{{ \App\Support\PakistaniCurrency::format($c['gain_loss']) }}</span>
                        @else
                            <span class="text-danger">{{ \App\Support\PakistaniCurrency::format($c['gain_loss']) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No price changes occurred in this date range.</td></tr>
            @endforelse
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="6">NET PRICE CHANGE INVENTORY IMPACT</td>
                <td class="text-right">
                    @if((float) $data['total_gain_loss'] >= 0)
                        <span class="text-success">+{{ \App\Support\PakistaniCurrency::format($data['total_gain_loss']) }}</span>
                    @else
                        <span class="text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_gain_loss']) }}</span>
                    @endif
                </td>
            </tr>
        </tfoot>
    </table>
</div>
