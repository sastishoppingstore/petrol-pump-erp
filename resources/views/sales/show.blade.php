@extends('layouts.app')

@section('title', 'Sale ' . $sale->invoice_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('sales.index') }}">{{ __('sales.sale_show.breadcrumb_sales') }}</a></li>
    <li class="text-slate-500">{{ $sale->invoice_number }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>🧾 {{ $sale->invoice_number }}</h1>
        <p>{{ $sale->sale_date?->format('d M Y, h:i A') }} · {{ $sale->customer?->name ?? 'Walk-in Customer' }} · {{ __('sales.sale_show.cashier') }}: {{ $sale->employee?->name }}</p>
        <div class="page-actions">
            <a href="{{ route('pos.receipt', $sale) }}" class="btn-3d btn-3d-navy">🖨️ {{ __('sales.sale_show.print_receipt') }}</a>
            @if ($sale->isCompleted())
                @can('sales.void')
                    <a href="{{ route('sales.void', $sale) }}" class="btn-3d !bg-gradient-to-b !from-rose-500 !to-rose-700 text-white">{{ __('sales.sale_show.void_refund') }}</a>
                @endcan
            @endif
            <a href="{{ route('sales.index') }}" class="btn-3d btn-3d-ghost">← {{ __('sales.sale_show.sales_history') }}</a>
        </div>
    </div>

    <div class="grid gap-5 lg:grid-cols-3">
        <div class="glass-card overflow-hidden lg:col-span-2">
            <h2 class="border-b border-slate-200/70 px-6 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/50 dark:text-white">⛽ {{ __('sales.sale_show.items_sold') }}</h2>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('sales.sale_show.fuel_nozzle') }}</th>
                            <th>{{ __('sales.sale_show.litres') }}</th>
                            <th>{{ __('sales.sale_show.rate') }}</th>
                            <th>{{ __('sales.sale_show.cost_rate') }}</th>
                            <th>{{ __('sales.sale_show.amount') }}</th>
                            <th>{{ __('sales.sale_show.meter_range') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sale->items as $item)
                            <tr>
                                <td>
                                    <div class="font-bold text-slate-800 dark:text-white">{{ $item->fuelProduct?->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $item->nozzle?->label() }}</div>
                                </td>
                                <td class="tabular font-semibold">{{ number_format((float) $item->litres, 3) }}</td>
                                <td class="tabular">{{ number_format((float) $item->rate, 2) }}</td>
                                <td class="tabular text-slate-500">{{ number_format((float) $item->cost_rate, 2) }}</td>
                                <td class="tabular font-black text-slate-900 dark:text-white">{{ number_format((float) $item->amount, 2) }}</td>
                                <td class="tabular text-xs text-slate-500">
                                    {{ number_format((float) $item->meter_start, 3) }} → {{ number_format((float) $item->meter_end, 3) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-5">
            <div class="glass-card card-3d p-6 text-center">
                <h2 class="mb-3 text-base font-black text-slate-800 dark:text-white">💰 {{ __('sales.sale_show.summary') }}</h2>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('sales.sale_show.subtotal') }}</span><span class="tabular font-semibold">{{ number_format((float) $sale->subtotal, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('sales.sale_show.discount') }}</span><span class="tabular font-semibold">{{ number_format((float) $sale->discount, 2) }}</span></div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-lg font-black dark:border-slate-700"><span>{{ __('sales.sale_show.total') }}</span><span class="tabular text-vital-primary">Rs. {{ number_format((float) $sale->total, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('sales.sale_show.cost_of_goods') }}</span><span class="tabular">{{ number_format((float) $sale->total_cost, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">{{ __('sales.sale_show.gross_margin') }}</span><span class="tabular font-bold text-emerald-600">{{ number_format((float) $sale->grossMargin(), 2) }}</span></div>
                </div>
            </div>

            <div class="glass-card card-3d p-6 text-center">
                <h2 class="mb-3 text-base font-black text-slate-800 dark:text-white">💳 {{ __('sales.sale_show.payments') }}</h2>
                <div class="space-y-1.5">
                    @foreach ($sale->payments as $payment)
                        <div class="flex justify-between text-sm">
                            <span class="font-semibold">{{ \App\Models\SalePayment::methods()[$payment->method] ?? $payment->method }}</span>
                            <span class="tabular font-bold">{{ number_format((float) $payment->amount, 2) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>

            @if (! $sale->isCompleted())
                <div class="rounded-2xl border border-red-300 bg-gradient-to-b from-red-50 to-red-100 p-5 text-center shadow-3d dark:border-red-900 dark:from-red-950 dark:to-red-900">
                    <strong class="text-lg font-black text-red-700 dark:text-red-300">{{ $sale->status }}</strong>
                    <p class="mt-1 text-sm text-red-600 dark:text-red-300">{{ $sale->void_reason }}</p>
                    <p class="text-xs text-slate-500">{{ $sale->voided_at?->format('d M Y H:i') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
