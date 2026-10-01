<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-tile-3d {{ (float) $data['total_gain_loss'] >= 0 ? 'stat-green' : 'stat-red' }}">
        <div class="stat-label">Net Inventory Price-Change Windfall / (Loss)</div>
        <div class="stat-value">
            @if((float) $data['total_gain_loss'] >= 0)
                +{{ \App\Support\PakistaniCurrency::format($data['total_gain_loss']) }}
            @else
                {{ \App\Support\PakistaniCurrency::format($data['total_gain_loss']) }}
            @endif
        </div>
        <div class="stat-sub">Calculated on underground tank inventory at moment of price revision</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
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
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($c['old_rate']) }}</td>
                    <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($c['new_rate']) }}</td>
                    <td class="text-right">
                        @if((float) $c['rate_diff'] >= 0)
                            <span class="badge bg-success">+{{ \App\Support\PakistaniCurrency::format($c['rate_diff']) }}</span>
                        @else
                            <span class="badge bg-danger">{{ \App\Support\PakistaniCurrency::format($c['rate_diff']) }}</span>
                        @endif
                    </td>
                    <td class="text-right tabular">{{ number_format((float) $c['stock_litres'], 3) }}</td>
                    <td class="text-right fw-bold tabular">
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
        <tfoot class="bg-slate-100 dark:bg-slate-800 fw-bold">
            <tr>
                <td colspan="6">NET PRICE CHANGE INVENTORY IMPACT</td>
                <td class="text-right tabular">
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
