@if ($report)
    @php
        $summary = $report->summary_json;
        // Purani rows double-encoded JSON string ho sakti hain — decode guard
        if (is_string($summary)) {
            $summary = json_decode($summary, true) ?: [];
        }
        $summary = is_array($summary) ? $summary : [];
    @endphp

    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">💰 {{ __('admin.report_widgets.total_revenue') }}</div>
            <div class="stat-value">Rs {{ number_format($summary['total_sales'] ?? 0, 0) }}</div>
            <div class="stat-sub">{{ __('admin.report_widgets.transactions', ['count' => $summary['transaction_count'] ?? 0]) }}</div>
        </div>

        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">⛽ {{ __('admin.report_widgets.total_liters') }}</div>
            <div class="stat-value">{{ number_format($summary['total_litres'] ?? 0, 0) }} L</div>
            <div class="stat-sub">{{ __('admin.report_widgets.avg_price', ['price' => $summary['avg_price_per_liter'] ?? 0]) }}</div>
        </div>

        <div class="stat-tile-3d stat-red">
            <div class="stat-label">📈 {{ __('admin.report_widgets.gross_margin') }}</div>
            <div class="stat-value">Rs {{ number_format($summary['gross_margin'] ?? 0, 0) }}</div>
            <div class="stat-sub">{{ __('admin.report_widgets.margin_pct', ['pct' => $summary['gross_margin_percentage'] ?? 0]) }}</div>
        </div>

        <div class="stat-tile-3d stat-amber">
            <div class="stat-label">💸 {{ __('admin.report_widgets.expenses') }}</div>
            <div class="stat-value">Rs {{ number_format($summary['expenses'] ?? 0, 0) }}</div>
            <div class="stat-sub">
                {{ __('admin.report_widgets.net') }}: Rs {{ number_format($summary['net_profit'] ?? 0, 0) }}
            </div>
        </div>
    </div>

    <div class="mb-5 grid items-start gap-5 lg:grid-cols-2">
        {{-- Payment Breakdown --}}
        <div class="glass-card card-3d p-6">
            <h3 class="mb-4 text-center text-base font-black text-slate-800 dark:text-white">💳 {{ __('admin.report_widgets.payment_breakdown') }}</h3>
            <div class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                    <span>💵 {{ __('admin.report_widgets.cash') }}:</span>
                    <strong class="tabular">Rs {{ number_format($summary['cash_sales'] ?? 0, 0) }}</strong>
                </div>
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                    <span>🏧 {{ __('admin.report_widgets.card') }}:</span>
                    <strong class="tabular">Rs {{ number_format($summary['card_sales'] ?? 0, 0) }}</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span>📝 {{ __('admin.report_widgets.credit') }}:</span>
                    <strong class="tabular">Rs {{ number_format($summary['credit_sales'] ?? 0, 0) }}</strong>
                </div>
            </div>
        </div>

        {{-- Stock Movement --}}
        <div class="glass-card card-3d p-6">
            <h3 class="mb-4 text-center text-base font-black text-slate-800 dark:text-white">📦 {{ __('admin.report_widgets.stock_movement') }}</h3>
            <div class="space-y-2.5 text-sm">
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                    <span>📥 {{ __('admin.report_widgets.purchases') }}:</span>
                    <strong class="tabular">{{ number_format($summary['purchases'] ?? 0, 0) }} L</strong>
                </div>
                <div class="flex items-center justify-between border-b border-slate-200/60 pb-2 dark:border-slate-700/40">
                    <span>📤 {{ __('admin.report_widgets.sales') }}:</span>
                    <strong class="tabular">{{ number_format($summary['sales'] ?? 0, 0) }} L</strong>
                </div>
                <div class="flex items-center justify-between">
                    <span>⚠️ {{ __('admin.report_widgets.variance') }}:</span>
                    <strong class="tabular text-amber-600">{{ number_format($summary['variance'] ?? 0, 0) }} L</strong>
                </div>
            </div>
        </div>
    </div>

    {{-- Fuel Breakdown --}}
    @if (!empty($summary['fuel_breakdown']))
        <div class="glass-card mb-5 overflow-hidden">
            <h3 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">⛽ {{ __('admin.report_widgets.fuel_breakdown') }}</h3>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('admin.report_widgets.th_fuel') }}</th>
                            <th>{{ __('admin.report_widgets.th_liters') }}</th>
                            <th>{{ __('admin.report_widgets.th_revenue') }}</th>
                            <th>{{ __('admin.report_widgets.th_pct') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($summary['fuel_breakdown'] as $fuel)
                            <tr>
                                <td><strong>{{ $fuel['fuel'] ?? __('admin.report_widgets.unknown') }}</strong></td>
                                <td class="tabular">{{ number_format($fuel['litres'] ?? 0, 0) }} L</td>
                                <td class="tabular font-bold">Rs {{ number_format($fuel['revenue'] ?? 0, 0) }}</td>
                                <td class="tabular">
                                    @php
                                        $pct = ($summary['total_sales'] ?? 0) > 0
                                            ? round((($fuel['revenue'] ?? 0) / $summary['total_sales']) * 100, 1)
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
    @endif

    {{-- Report Info --}}
    <div class="glass-card p-4 text-center text-xs text-slate-500">
        <strong>{{ __('admin.report_widgets.period') }}:</strong> {{ \Carbon\Carbon::parse($summary['period_start'] ?? now())->format('M d, H:i') }}
        {{ __('admin.report_widgets.to') }} {{ \Carbon\Carbon::parse($summary['period_end'] ?? now())->format('M d, H:i') }} •
        <strong>{{ __('admin.report_widgets.generated') }}:</strong> {{ \Carbon\Carbon::parse($summary['generated_at'] ?? $report->created_at)->format('M d H:i:s') }} •
        <strong>{{ __('admin.report_widgets.status_label') }}:</strong> <span class="pill-status pill-active"><span class="dot"></span>{{ $report->status }}</span>
        @if ($report->sent_at)
            • <strong>{{ __('admin.report_widgets.sent') }}:</strong> {{ $report->sent_at->format('M d H:i') }}
        @endif
    </div>

@else
    <div class="alert alert-info">
        📊 {{ __('admin.report_widgets.no_data') }}
    </div>
@endif
