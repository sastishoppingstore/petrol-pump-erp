<div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
    <div class="stat-tile-3d stat-navy">
        <div class="stat-label">Total Operating Revenue</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_revenue']) }}</div>
    </div>
    <div class="stat-tile-3d stat-slate">
        <div class="stat-label">Gross Margin (نفع خام)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['gross_profit']) }}</div>
    </div>
    <div class="stat-tile-3d {{ (float) $data['net_profit'] >= 0 ? 'stat-green' : 'stat-red' }}">
        <div class="stat-label">Net Profit / (Loss) (خالص نفع)</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['net_profit']) }}</div>
        <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toWordsUrdu($data['net_profit']) }}</div>
    </div>
</div>

<div class="glass-card overflow-hidden mb-6">
    <div class="bg-slate-100 dark:bg-slate-800 px-4 py-3 text-center text-sm font-extrabold uppercase tracking-wide text-slate-700 dark:text-slate-200">1. Operating Revenue (آمدن)</div>
    <div class="table-3d">
        <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
            <tbody>
                @forelse($data['revenue_items'] as $item)
                    <tr>
                        <td>{{ $item['account']->name }} ({{ $item['account']->urdu_name }})</td>
                        <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($item['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted py-2">No revenue posted in period.</td></tr>
                @endforelse
                <tr class="bg-slate-100 dark:bg-slate-800 fw-bold">
                    <td>Total Operating Revenue</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['total_revenue']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="glass-card overflow-hidden mb-6">
    <div class="bg-slate-100 dark:bg-slate-800 px-4 py-3 text-center text-sm font-extrabold uppercase tracking-wide text-slate-700 dark:text-slate-200">2. Cost of Goods Sold (فروخت شدہ مال کی لاگت)</div>
    <div class="table-3d">
        <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
            <tbody>
                @forelse($data['cogs_items'] as $item)
                    <tr>
                        <td>{{ $item['account']->name }}</td>
                        <td class="text-right text-danger fw-bold tabular">-{{ \App\Support\PakistaniCurrency::format($item['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted py-2">No COGS recorded in period.</td></tr>
                @endforelse
                <tr class="bg-slate-100 dark:bg-slate-800 fw-bold">
                    <td>Total Cost of Goods Sold</td>
                    <td class="text-right text-danger tabular">-{{ \App\Support\PakistaniCurrency::format($data['total_cogs']) }}</td>
                </tr>
                <tr class="bg-vital-primary/10 dark:bg-slate-800 fw-bold">
                    <td>GROSS PROFIT / MARGIN (نفع خام)</td>
                    <td class="text-right text-vital-primary tabular">{{ \App\Support\PakistaniCurrency::format($data['gross_profit']) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<div class="glass-card overflow-hidden">
    <div class="bg-slate-100 dark:bg-slate-800 px-4 py-3 text-center text-sm font-extrabold uppercase tracking-wide text-slate-700 dark:text-slate-200">3. Operating Expenses & Overheads (کاروباری اخراجات)</div>
    <div class="table-3d">
        <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
            <tbody>
                @forelse($data['expense_items'] as $item)
                    <tr>
                        <td>{{ $item['account']->name }}</td>
                        <td class="text-right text-danger tabular">{{ \App\Support\PakistaniCurrency::format($item['amount']) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2" class="text-muted py-2">No operating expenses recorded.</td></tr>
                @endforelse
                <tr class="bg-slate-100 dark:bg-slate-800 fw-bold">
                    <td>Total Operating Expenses</td>
                    <td class="text-right text-danger tabular">-{{ \App\Support\PakistaniCurrency::format($data['total_expenses']) }}</td>
                </tr>
                @if(count($data['other_items']) > 0)
                    @foreach($data['other_items'] as $item)
                        <tr>
                            <td>{{ $item['account']->name }} (Variance)</td>
                            <td class="text-right text-danger tabular">-{{ \App\Support\PakistaniCurrency::format($item['amount']) }}</td>
                        </tr>
                    @endforeach
                @endif
                <tr class="bg-emerald-100/70 dark:bg-emerald-900/30 fw-bold">
                    <td>NET PROFIT / (LOSS) FOR THE PERIOD (خالص نفع)</td>
                    <td class="text-right tabular {{ (float) $data['net_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ \App\Support\PakistaniCurrency::format($data['net_profit']) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
