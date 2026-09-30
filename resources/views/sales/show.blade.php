@extends('layouts.app')

@section('title', 'Sale ' . $sale->invoice_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('sales.index') }}">Sales</a></li>
    <li class="text-slate-500">{{ $sale->invoice_number }}</li>
@endsection

@section('content')
    <div class="mb-4 flex gap-2">
        <a href="{{ route('pos.receipt', $sale) }}" class="rounded-md bg-navy-800 px-4 py-2 text-sm font-semibold text-white dark:bg-navy-700">Print receipt</a>
        @if ($sale->isCompleted())
            @can('sales.void')
                <a href="{{ route('sales.void', $sale) }}" class="rounded-md border border-red-300 px-4 py-2 text-sm text-red-700">Void</a>
            @endcan
        @endif
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="lg:col-span-2 rounded-lg border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h2 class="mb-3 text-base font-bold">Items</h2>
            <table class="mb-0 w-full text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase dark:border-slate-700">
                    <tr>
                        <th class="py-2 text-left">Fuel / Nozzle</th>
                        <th class="py-2 text-right">Litres</th>
                        <th class="py-2 text-right">Rate</th>
                        <th class="py-2 text-right">Cost rate</th>
                        <th class="py-2 text-right">Amount</th>
                        <th class="py-2 text-right">Meter start → end</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($sale->items as $item)
                        <tr class="border-b border-slate-100 dark:border-slate-800">
                            <td class="py-2">
                                {{ $item->fuelProduct?->name }}
                                <div class="text-xs text-slate-500">{{ $item->nozzle?->label() }}</div>
                            </td>
                            <td class="tabular py-2 text-right">{{ number_format((float) $item->litres, 3) }}</td>
                            <td class="tabular py-2 text-right">{{ number_format((float) $item->rate, 2) }}</td>
                            <td class="tabular py-2 text-right text-slate-500">{{ number_format((float) $item->cost_rate, 2) }}</td>
                            <td class="tabular py-2 text-right font-semibold">{{ number_format((float) $item->amount, 2) }}</td>
                            <td class="tabular py-2 text-right text-xs text-slate-500">
                                {{ number_format((float) $item->meter_start, 3) }} → {{ number_format((float) $item->meter_end, 3) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="space-y-4">
            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-2 text-base font-bold">Summary</h2>
                <div class="space-y-1 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Subtotal</span><span class="tabular">{{ number_format((float) $sale->subtotal, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Discount</span><span class="tabular">{{ number_format((float) $sale->discount, 2) }}</span></div>
                    <div class="flex justify-between border-t pt-1 text-base font-bold"><span>Total</span><span class="tabular">{{ number_format((float) $sale->total, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Cost of goods</span><span class="tabular">{{ number_format((float) $sale->total_cost, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Gross margin</span><span class="tabular font-semibold text-emerald-600">{{ number_format((float) $sale->grossMargin(), 2) }}</span></div>
                </div>
            </div>

            <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-2 text-base font-bold">Payments</h2>
                @foreach ($sale->payments as $payment)
                    <div class="flex justify-between text-sm">
                        <span>{{ \App\Models\SalePayment::methods()[$payment->method] ?? $payment->method }}</span>
                        <span class="tabular">{{ number_format((float) $payment->amount, 2) }}</span>
                    </div>
                @endforeach
            </div>

            @if (! $sale->isCompleted())
                <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm dark:border-red-900 dark:bg-red-950">
                    <strong>{{ $sale->status }}</strong>
                    <p class="mt-1 text-xs">{{ $sale->void_reason }}</p>
                    <p class="text-xs text-slate-500">{{ $sale->voided_at?->format('d M Y H:i') }}</p>
                </div>
            @endif
        </div>
    </div>
@endsection
