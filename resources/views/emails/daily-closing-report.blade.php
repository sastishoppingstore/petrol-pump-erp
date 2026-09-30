@component('mail::message')
# 📊 Daily Closing Report

## {{ $summary['branch'] }}

**Report Period:** {{ $summary['period'] }}

---

## 💰 Sales Summary

| Metric | Amount |
|--------|--------|
| **Total Sales** | Rs {{ number_format($summary['sales']['total'], 0) }} |
| **Total Liters** | {{ number_format($summary['sales']['litres'], 0) }} L |
| **Transactions** | {{ $summary['sales']['transactions'] }} |

### Payment Breakdown
- 💵 **Cash:** Rs {{ number_format($summary['sales']['cash'], 0) }}
- 🏧 **Card:** Rs {{ number_format($summary['sales']['card'], 0) }}
- 📝 **Credit:** Rs {{ number_format($summary['sales']['credit'], 0) }}

---

## 📈 Profitability

| Metric | Amount |
|--------|--------|
| **Revenue** | Rs {{ $summary['profitability']['revenue'] }} |
| **COGS** | Rs {{ $summary['profitability']['cogs'] }} |
| **Gross Margin** | Rs {{ $summary['profitability']['gross_margin'] }} ({{ $summary['profitability']['gross_margin_pct'] }}%) |
| **Expenses** | Rs {{ $summary['profitability']['expenses'] }} |
| **Net Profit** | **Rs {{ $summary['profitability']['net_profit'] }}** ({{ $summary['profitability']['net_profit_pct'] }}%) |

---

## 📦 Stock Movement

| Type | Quantity |
|------|----------|
| **Purchases** | {{ $summary['stock']['purchases'] }} L |
| **Sales** | {{ $summary['stock']['sales'] }} L |
| **Variance** | {{ $summary['stock']['variance'] }} L |

---

## ⛽ Fuel Breakdown

@foreach ($summary['fuel_breakdown'] as $fuel)
- **{{ $fuel['fuel'] }}**: {{ number_format($fuel['litres'], 0) }} L @ Rs {{ number_format($fuel['revenue'], 0) }}
@endforeach

---

## 📋 Next Steps

1. Review this report for any anomalies
2. Check variance alerts if any
3. Verify cash count matches expected amount
4. Contact manager if discrepancies found

---

**Generated:** {{ now()->format('M d, Y H:i:s') }}  
**System:** Vital Petroleum ERP - Mehar Filling Station

@component('mail::button', ['url' => route('dashboard')])
View Dashboard
@endcomponent

@endcomponent
