@if ($report)
    @php $summary = $report->summary_json; @endphp
    
    <div class="row mb-4">
        <div class="col-md-6 col-lg-3">
            <div class="report-metric revenue">
                <div class="metric-label">💰 Total Revenue</div>
                <div class="metric-value">Rs {{ number_format($summary['total_sales'], 0) }}</div>
                <div class="metric-sub">{{ $summary['transaction_count'] }} transactions</div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="report-metric fuel">
                <div class="metric-label">⛽ Total Liters</div>
                <div class="metric-value">{{ number_format($summary['total_litres'], 0) }} L</div>
                <div class="metric-sub">@ Rs {{ $summary['avg_price_per_liter'] }}/L</div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="report-metric profit">
                <div class="metric-label">📈 Gross Margin</div>
                <div class="metric-value">Rs {{ number_format($summary['gross_margin'], 0) }}</div>
                <div class="metric-sub">{{ $summary['gross_margin_percentage'] }}% margin</div>
            </div>
        </div>
        
        <div class="col-md-6 col-lg-3">
            <div class="report-metric expense">
                <div class="metric-label">💸 Expenses</div>
                <div class="metric-value">Rs {{ number_format($summary['expenses'], 0) }}</div>
                <div class="metric-sub">
                    Net: Rs {{ number_format($summary['net_profit'], 0) }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payment Breakdown -->
    <div class="row mb-4">
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">💳 Payment Breakdown</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>💵 Cash:</span>
                        <strong>Rs {{ number_format($summary['cash_sales'], 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>🏧 Card:</span>
                        <strong>Rs {{ number_format($summary['card_sales'], 0) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>📝 Credit:</span>
                        <strong>Rs {{ number_format($summary['credit_sales'], 0) }}</strong>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Stock Movement -->
        <div class="col-lg-6">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title">📦 Stock Movement</h6>
                    <div class="d-flex justify-content-between mb-2">
                        <span>📥 Purchases:</span>
                        <strong>{{ number_format($summary['purchases'], 0) }} L</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>📤 Sales:</span>
                        <strong>{{ number_format($summary['sales'], 0) }} L</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>⚠️ Variance:</span>
                        <strong class="text-warning">{{ number_format($summary['variance'], 0) }} L</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Fuel Breakdown -->
    @if ($summary['fuel_breakdown'])
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="card-title">⛽ Fuel Breakdown</h6>
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>Fuel</th>
                                    <th>Liters</th>
                                    <th>Revenue</th>
                                    <th>% of Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($summary['fuel_breakdown'] as $fuel)
                                    <tr>
                                        <td><strong>{{ $fuel['fuel'] }}</strong></td>
                                        <td>{{ number_format($fuel['litres'], 0) }} L</td>
                                        <td>Rs {{ number_format($fuel['revenue'], 0) }}</td>
                                        <td>
                                            @php
                                                $pct = $summary['total_sales'] > 0 
                                                    ? round(($fuel['revenue'] / $summary['total_sales']) * 100, 1)
                                                    : 0;
                                            @endphp
                                            {{ $pct }}%
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endif
    
    <!-- Report Info -->
    <div class="mt-4 p-3 bg-light rounded">
        <small class="text-muted">
            <strong>Period:</strong> {{ \Carbon\Carbon::parse($summary['period_start'])->format('M d, H:i') }} 
            to {{ \Carbon\Carbon::parse($summary['period_end'])->format('M d, H:i') }} <br>
            <strong>Generated:</strong> {{ \Carbon\Carbon::parse($summary['generated_at'])->format('M d H:i:s') }} <br>
            <strong>Status:</strong> <span class="badge bg-success">{{ $report->status }}</span>
            @if ($report->sent_at)
                <strong>Sent:</strong> {{ $report->sent_at->format('M d H:i') }}
            @endif
        </small>
    </div>

@else
    <div class="alert alert-info">
        📊 No report data available yet for this period. Reports are generated hourly.
    </div>
@endif
