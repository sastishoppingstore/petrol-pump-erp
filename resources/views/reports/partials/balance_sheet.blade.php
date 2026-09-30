<div class="row g-4">
    <!-- Assets Column -->
    <div class="col-md-6">
        <div class="card border h-100 shadow-sm">
            <div class="card-header bg-dark text-white fw-bold d-flex justify-content-between">
                <span>ASSETS (اثاثہ جات)</span>
                <span>As of {{ $data['as_of_date'] }}</span>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Account</th>
                            <th class="text-right pe-3">Balance (Rs.)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data['assets'] as $a)
                            <tr>
                                <td class="ps-3">{{ $a['account']->name }} ({{ $a['account']->urdu_name }})</td>
                                <td class="text-right pe-3 fw-bold">{{ \App\Support\PakistaniCurrency::format($a['balance']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-muted ps-3 py-3">No asset balances.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot class="table-dark fw-bold fs-6">
                        <tr>
                            <td class="ps-3">TOTAL ASSETS</td>
                            <td class="text-right pe-3">{{ \App\Support\PakistaniCurrency::format($data['total_assets']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Liabilities & Equity Column -->
    <div class="col-md-6">
        <div class="card border h-100 shadow-sm">
            <div class="card-header bg-danger text-white fw-bold d-flex justify-content-between">
                <span>LIABILITIES & EQUITY (واجبات اور سرمایہ)</span>
                <span>As of {{ $data['as_of_date'] }}</span>
            </div>
            <div class="card-body p-0">
                <div class="p-2 bg-light fw-bold text-muted small ps-3">LIABILITIES (واجب الادا رقوم)</div>
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($data['liabilities'] as $l)
                            <tr>
                                <td class="ps-3">{{ $l['account']->name }}</td>
                                <td class="text-right pe-3 fw-bold">{{ \App\Support\PakistaniCurrency::format($l['balance']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-muted ps-3 py-1">No liabilities recorded.</td></tr>
                        @endforelse
                        <tr class="fw-bold table-light">
                            <td class="ps-3">Total Liabilities</td>
                            <td class="text-right pe-3">{{ \App\Support\PakistaniCurrency::format($data['total_liabilities']) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="p-2 bg-light fw-bold text-muted small ps-3 mt-2">OWNER'S EQUITY (مالک کا سرمایہ)</div>
                <table class="table table-sm mb-0">
                    <tbody>
                        @forelse($data['equity'] as $eq)
                            <tr>
                                <td class="ps-3">{{ $eq['account']->name }}</td>
                                <td class="text-right pe-3 fw-bold">{{ \App\Support\PakistaniCurrency::format($eq['balance']) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="text-muted ps-3 py-1">No equity accounts.</td></tr>
                        @endforelse
                        <tr>
                            <td class="ps-3">Retained Earnings / Cumulative Profit</td>
                            <td class="text-right pe-3 fw-bold {{ (float) $data['retained_earnings'] >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ \App\Support\PakistaniCurrency::format($data['retained_earnings']) }}
                            </td>
                        </tr>
                        <tr class="fw-bold table-light">
                            <td class="ps-3">Total Equity</td>
                            <td class="text-right pe-3">{{ \App\Support\PakistaniCurrency::format($data['total_equity']) }}</td>
                        </tr>
                    </tbody>
                    <tfoot class="table-danger text-white fw-bold fs-6">
                        <tr>
                            <td class="ps-3">TOTAL LIABILITIES & EQUITY</td>
                            <td class="text-right pe-3">{{ \App\Support\PakistaniCurrency::format($data['total_liabilities_and_equity']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="mt-4 p-3 rounded {{ $data['is_balanced'] ? 'bg-success bg-opacity-10 border border-success text-success' : 'bg-danger bg-opacity-10 border border-danger text-danger' }} text-center fw-bold">
    @if($data['is_balanced'])
        ✓ Balance Sheet is in Perfect Equilibrium: Total Assets ({{ \App\Support\PakistaniCurrency::format($data['total_assets']) }}) == Total Liabilities & Equity ({{ \App\Support\PakistaniCurrency::format($data['total_liabilities_and_equity']) }})
    @else
        ⚠ Balance Sheet Out of Balance by {{ \App\Support\PakistaniCurrency::format(abs((float)$data['total_assets'] - (float)$data['total_liabilities_and_equity'])) }}
    @endif
</div>
