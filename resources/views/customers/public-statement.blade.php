<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('sales.customer_statement.title_prefix') }} — {{ $customer->name }} ({{ $customer->code }})</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { font-size: 11pt; background: #ffffff !important; color: #1B1B1B !important; }
            .statement-container { border: none !important; box-shadow: none !important; padding: 0 !important; width: 100% !important; max-width: 100% !important; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased p-4 sm:p-8">

{{-- Controls (hidden on print) --}}
<div class="no-print max-w-4xl mx-auto mb-6 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <span class="inline-flex items-center gap-2 rounded-full bg-red-50 px-3 py-1 text-xs font-black text-red-700 border border-red-200">
        🔗 {{ __('sales.collection.pub_badge') }} · {{ __('sales.collection.pub_period') }}
    </span>
    <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-1.5 text-xs font-bold text-white hover:bg-red-700 shadow-sm transition">
        {{ __('sales.collection.pub_print') }}
    </button>
</div>

{{-- Statement Document --}}
<div class="statement-container max-w-4xl mx-auto bg-white border border-slate-200 shadow-md rounded-xl p-6 sm:p-10">
    {{-- Station Header (identity from admin settings) --}}
    <div class="flex justify-between items-start border-b-4 border-red-600 pb-6 mb-6">
        <div>
            <div class="flex items-center gap-2">
                <div class="h-10 w-10 bg-red-600 rounded flex items-center justify-center text-white font-extrabold text-xl shadow">⛽</div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-black tracking-tight text-slate-900 uppercase">
                        {{ $station['station_name_en'] ?? 'Mehar Filling Station' }}
                    </h1>
                    <div class="text-xs font-bold text-red-600 uppercase tracking-widest">
                        {{ $station['omc_brand_name'] ?? 'Vital Petroleum' }}
                    </div>
                </div>
            </div>
            <div class="mt-2 text-xs text-slate-600">
                {{ $station['address'] ?? '' }}<br>
                Tel: {{ $station['phone'] ?? '' }}
            </div>
        </div>

        <div class="text-right">
            <div class="inline-block bg-red-50 border border-red-200 rounded-lg px-4 py-2">
                <div class="text-[11px] font-bold uppercase tracking-wider text-red-700">{{ __('sales.customer_statement.statement_of_account') }}</div>
                <div class="text-xs font-mono font-bold text-slate-900 mt-0.5">
                    {{ \Carbon\Carbon::parse($start_date)->format('d M Y') }} — {{ \Carbon\Carbon::parse($end_date)->format('d M Y') }}
                </div>
            </div>
            <div class="mt-2 text-[11px] text-slate-400">
                {{ __('sales.customer_statement.generated') }} {{ now()->format('d/m/Y h:i A') }}
            </div>
        </div>
    </div>

    {{-- Customer Meta --}}
    <div class="grid grid-cols-2 gap-6 bg-slate-50 rounded-lg p-4 mb-6 border border-slate-100 text-xs">
        <div>
            <div class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">{{ __('sales.customer_statement.account_details') }}</div>
            <div class="mt-1 font-bold text-base text-slate-900">{{ $customer->name }}</div>
            <div class="text-slate-600 font-mono mt-0.5">{{ __('sales.customer_statement.code') }} <strong class="text-red-600">{{ $customer->code }}</strong></div>
            <div class="text-slate-600 mt-0.5">{{ __('sales.customer_statement.phone') }} {{ $customer->phone ?? 'N/A' }}</div>
        </div>
        <div class="text-right flex flex-col justify-between">
            <div>
                <div class="font-bold text-slate-500 uppercase tracking-wider text-[10px]">{{ __('sales.customer_statement.credit_status') }}</div>
                <div class="mt-1 text-slate-600">
                    {{ __('sales.customer_statement.credit_limit') }} <strong>{{ $customer->creditLimitIsUnlimited() ? __('sales.customer_statement.unlimited') : \App\Support\PakistaniCurrency::format($customer->credit_limit, true, 0) }}</strong>
                </div>
            </div>
            <div class="pt-2 border-t border-slate-200">
                <span class="text-xs font-medium text-slate-500">{{ __('sales.customer_statement.period_opening_balance') }}</span>
                <span class="text-sm font-bold text-slate-900 ml-2">{{ \App\Support\PakistaniCurrency::format($opening_balance) }}</span>
            </div>
        </div>
    </div>

    {{-- Transactions --}}
    <div class="overflow-x-auto mb-6">
        <table class="w-full text-left text-xs border border-slate-200">
            <thead class="bg-red-600 text-white font-bold uppercase tracking-wider text-[11px]">
                <tr>
                    <th class="px-3 py-2.5">{{ __('sales.customer_statement.th_date') }}</th>
                    <th class="px-3 py-2.5">{{ __('sales.customer_statement.th_description') }}</th>
                    <th class="px-3 py-2.5 text-right">{{ __('sales.customer_statement.th_debit') }}</th>
                    <th class="px-3 py-2.5 text-right">{{ __('sales.customer_statement.th_credit') }}</th>
                    <th class="px-3 py-2.5 text-right">{{ __('sales.customer_statement.th_balance') }}</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200 text-slate-700">
                <tr class="bg-slate-100/70 font-semibold">
                    <td class="px-3 py-2 font-mono">{{ \Carbon\Carbon::parse($start_date)->format('d/m/Y') }}</td>
                    <td class="px-3 py-2" colspan="3">{{ __('sales.customer_statement.opening_brought_forward') }}</td>
                    <td class="px-3 py-2 text-right font-bold font-mono">{{ \App\Support\PakistaniCurrency::format($opening_balance, false) }}</td>
                </tr>
                @forelse($items as $row)
                    <tr class="hover:bg-slate-50">
                        <td class="px-3 py-2 font-mono whitespace-nowrap">{{ $row['date'] }}</td>
                        <td class="px-3 py-2">
                            <div class="font-medium text-slate-900">{{ $row['description'] }}</div>
                            @if($row['reference_type'])
                                <div class="text-[10px] text-slate-400">{{ __('sales.customer_statement.ref') }} {{ class_basename($row['reference_type']) }} #{{ $row['reference_id'] }}</div>
                            @endif
                        </td>
                        <td class="px-3 py-2 text-right font-mono text-red-600 font-medium whitespace-nowrap">
                            {{ \App\Support\Money::compare($row['debit'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($row['debit'], false) : '-' }}
                        </td>
                        <td class="px-3 py-2 text-right font-mono text-emerald-600 font-medium whitespace-nowrap">
                            {{ \App\Support\Money::compare($row['credit'], '0.00') > 0 ? \App\Support\PakistaniCurrency::format($row['credit'], false) : '-' }}
                        </td>
                        <td class="px-3 py-2 text-right font-mono font-bold text-slate-900 whitespace-nowrap">
                            {{ \App\Support\PakistaniCurrency::format($row['balance'], false) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-3 py-4 text-center text-slate-400">
                            {{ __('sales.customer_statement.no_transactions') }}
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-slate-50 border-t-2 border-slate-300 font-bold text-slate-900">
                <tr>
                    <td class="px-3 py-2.5 uppercase" colspan="2">{{ __('sales.customer_statement.period_totals') }}</td>
                    <td class="px-3 py-2.5 text-right font-mono text-red-600">{{ \App\Support\PakistaniCurrency::format($total_debits, false) }}</td>
                    <td class="px-3 py-2.5 text-right font-mono text-emerald-600">{{ \App\Support\PakistaniCurrency::format($total_credits, false) }}</td>
                    <td class="px-3 py-2.5 text-right font-mono text-slate-900">{{ \App\Support\PakistaniCurrency::format($closing_balance, false) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    {{-- Closing Balance Callout --}}
    <div class="rounded-xl border-2 border-red-600 bg-red-50/50 p-5 mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="text-xs font-bold uppercase tracking-wider text-red-700">{{ __('sales.customer_statement.total_net_payable') }}</div>
                <div class="text-2xl font-black text-red-600 mt-1">{{ $formatted_closing }}</div>
            </div>
            <div class="text-right sm:border-l sm:border-red-200 sm:pl-6">
                <div class="text-[11px] font-semibold text-slate-500">{{ __('sales.customer_statement.amount_in_words') }}</div>
                <div class="text-sm font-bold text-slate-900 mt-0.5">{{ $in_words_urdu }}</div>
            </div>
        </div>
    </div>

    <p class="text-center text-[11px] text-slate-400">{{ __('sales.collection.pub_note') }}</p>
</div>
</body>
</html>
