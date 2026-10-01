<div class="grid grid-cols-1 xl:grid-cols-2 gap-6">
    {{-- Assets Column --}}
    <div class="glass-card overflow-hidden">
        <div class="bg-slate-900 px-4 py-3 text-center text-white">
            <div class="font-extrabold tracking-wide">ASSETS (اثاثہ جات)</div>
            <div class="text-xs font-semibold opacity-80">As of {{ $data['as_of_date'] }}</div>
        </div>
        <div class="table-3d">
            <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
                <thead>
                    <tr>
                        <th>Account</th>
                        <th class="text-right">Balance (Rs.)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data['assets'] as $a)
                        <tr>
                            <td>{{ $a['account']->name }} ({{ $a['account']->urdu_name }})</td>
                            <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($a['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted py-3">No asset balances.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-900 text-white fw-bold">
                    <tr>
                        <td>TOTAL ASSETS</td>
                        <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['total_assets']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    {{-- Liabilities & Equity Column --}}
    <div class="glass-card overflow-hidden">
        <div class="bg-gradient-to-br from-vital-primary to-vital-darkred px-4 py-3 text-center text-white">
            <div class="font-extrabold tracking-wide">LIABILITIES & EQUITY (واجبات اور سرمایہ)</div>
            <div class="text-xs font-semibold opacity-85">As of {{ $data['as_of_date'] }}</div>
        </div>
        <div class="bg-slate-100 dark:bg-slate-800 px-4 py-2.5 text-center text-sm font-extrabold uppercase tracking-wide text-slate-600 dark:text-slate-300">LIABILITIES (واجب الادا رقوم)</div>
        <div class="table-3d">
            <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
                <tbody>
                    @forelse($data['liabilities'] as $l)
                        <tr>
                            <td>{{ $l['account']->name }}</td>
                            <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($l['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted py-1">No liabilities recorded.</td></tr>
                    @endforelse
                    <tr class="bg-slate-100 dark:bg-slate-800 fw-bold">
                        <td>Total Liabilities</td>
                        <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['total_liabilities']) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="bg-slate-100 dark:bg-slate-800 px-4 py-2.5 text-center text-sm font-extrabold uppercase tracking-wide text-slate-600 dark:text-slate-300">OWNER'S EQUITY (مالک کا سرمایہ)</div>
        <div class="table-3d">
            <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
                <tbody>
                    @forelse($data['equity'] as $eq)
                        <tr>
                            <td>{{ $eq['account']->name }}</td>
                            <td class="text-right fw-bold tabular">{{ \App\Support\PakistaniCurrency::format($eq['balance']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="2" class="text-muted py-1">No equity accounts.</td></tr>
                    @endforelse
                    <tr>
                        <td>Retained Earnings / Cumulative Profit</td>
                        <td class="text-right fw-bold tabular {{ (float) $data['retained_earnings'] >= 0 ? 'text-success' : 'text-danger' }}">
                            {{ \App\Support\PakistaniCurrency::format($data['retained_earnings']) }}
                        </td>
                    </tr>
                    <tr class="bg-slate-100 dark:bg-slate-800 fw-bold">
                        <td>Total Equity</td>
                        <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['total_equity']) }}</td>
                    </tr>
                </tbody>
                <tfoot class="bg-vital-primary text-white fw-bold">
                    <tr>
                        <td>TOTAL LIABILITIES & EQUITY</td>
                        <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($data['total_liabilities_and_equity']) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<div class="mt-6 rounded-2xl border px-4 py-4 text-center fw-bold shadow-3d {{ $data['is_balanced'] ? 'bg-emerald-100/80 border-emerald-300 text-emerald-800 dark:bg-emerald-900/30 dark:border-emerald-700 dark:text-emerald-200' : 'bg-red-100/80 border-red-300 text-red-800 dark:bg-red-900/30 dark:border-red-700 dark:text-red-200' }}">
    @if($data['is_balanced'])
        ✓ Balance Sheet is in Perfect Equilibrium: Total Assets ({{ \App\Support\PakistaniCurrency::format($data['total_assets']) }}) == Total Liabilities & Equity ({{ \App\Support\PakistaniCurrency::format($data['total_liabilities_and_equity']) }})
    @else
        ⚠ Balance Sheet Out of Balance by {{ \App\Support\PakistaniCurrency::format(abs((float)$data['total_assets'] - (float)$data['total_liabilities_and_equity'])) }}
    @endif
</div>
