<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Total Operating Revenue</small>
            <div class="fs-4 fw-bold">{{ \App\Support\PakistaniCurrency::format($data['total_revenue']) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 border-info">
            <small class="text-muted text-uppercase fw-bold">Gross Margin (نفع خام)</small>
            <div class="fs-4 fw-bold text-primary">{{ \App\Support\PakistaniCurrency::format($data['gross_profit']) }}</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="p-3 bg-light rounded border-start border-4 border-success">
            <small class="text-muted text-uppercase fw-bold">Net Profit / (Loss) (خالص نفع)</small>
            <div class="fs-4 fw-bold {{ (float) $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                {{ \App\Support\PakistaniCurrency::format($data['net_profit']) }}
            </div>
            <small class="{{ (float) $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                {{ \App\Support\PakistaniCurrency::toWordsUrdu($data['net_profit']) }}
            </small>
        </div>
    </div>
</div>

<div class="card border mb-3">
    <div class="card-header bg-light fw-bold text-uppercase">1. Operating Revenue (آمدن)</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
                @forelse($data['revenue_items'] as $item)
                    <tr>
                        <td class="ps-3">{{ $item['account']->name }} ({{ $item['account']->urdu_name }})</td>
                        <td class="text-right pe-3 fw-bold">{{ \App\Support\PakistaniCurrency::format($item['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted ps-3 py-2">No revenue posted in period.</td></tr>
                @endforelse
                <tr class="table-light fw-bold">
                    <td class="ps-3">Total Operating Revenue</td>
                    <td class="text-right pe-3">{{ \App\Support\PakistaniCurrency::format($data['total_revenue']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card border mb-3">
    <div class="card-header bg-light fw-bold text-uppercase">2. Cost of Goods Sold (فروخت شدہ مال کی لاگت)</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
                @forelse($data['cogs_items'] as $item)
                    <tr>
                        <td class="ps-3">{{ $item['account']->name }}</td>
                        <td class="text-right pe-3 text-danger fw-bold">-{{ \App\Support\PakistaniCurrency::format($item['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted ps-3 py-2">No COGS recorded in period.</td></tr>
                @endforelse
                <tr class="table-light fw-bold">
                    <td class="ps-3">Total Cost of Goods Sold</td>
                    <td class="text-right pe-3 text-danger">-{{ \App\Support\PakistaniCurrency::format($data['total_cogs']) }}</td>
                </tr>
                <tr class="table-primary fw-bold fs-6">
                    <td class="ps-3">GROSS PROFIT / MARGIN (نفع خام)</td>
                    <td class="text-right pe-3 text-primary">{{ \App\Support\PakistaniCurrency::format($data['gross_profit']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="card border mb-3">
    <div class="card-header bg-light fw-bold text-uppercase">3. Operating Expenses & Overheads (کاروباری اخراجات)</div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <tbody>
                @forelse($data['expense_items'] as $item)
                    <tr>
                        <td class="ps-3">{{ $item['account']->name }}</td>
                        <td class="text-right pe-3 text-danger">{{ \App\Support\PakistaniCurrency::format($item['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted ps-3 py-2">No operating expenses recorded.</td></tr>
                @endforelse
                <tr class="table-light fw-bold">
                    <td class="ps-3">Total Operating Expenses</td>
                    <td class="text-right pe-3 text-danger">-{{ \App\Support\PakistaniCurrency::format($data['total_expenses']) }}</td>
                </tr>
                @if(count($data['other_items']) > 0)
                    @foreach($data['other_items'] as $item)
                        <tr>
                            <td class="ps-3">{{ $item['account']->name }} (Variance)</td>
                            <td class="text-right pe-3 text-danger">-{{ \App\Support\PakistaniCurrency::format($item['amount']) }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr class="table-success fw-bold fs-5">
                    <td class="ps-3">NET PROFIT / (LOSS) FOR THE PERIOD (خالص نفع)</td>
                    <td class="text-right pe-3 {{ (float) $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ \App\Support\PakistaniCurrency::format($data['net_profit']) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
