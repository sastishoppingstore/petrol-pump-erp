@extends('layouts.app')

@section('title', 'Receipt ' . $sale->invoice_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('pos.index') }}">POS</a></li>
    <li class="text-slate-500">{{ $sale->invoice_number }}</li>
@endsection

@section('content')
    <div class="no-print mb-4 flex gap-2">
        <button type="button" onclick="window.print()"
                class="rounded-md bg-navy-800 px-4 py-2 font-semibold text-white dark:bg-navy-700">Print / Save PDF</button>
        <a href="{{ route('pos.index') }}" class="rounded-md border border-slate-300 px-4 py-2 dark:border-slate-700">New Sale</a>
        <a href="{{ route('sales.index') }}" class="rounded-md border border-slate-300 px-4 py-2 dark:border-slate-700">Sales History</a>
    </div>

    @php
        $company = app(\App\Services\System\SettingService::class)->company();
        $fee = app(\App\Services\System\SettingService::class)->money('pos_service_fee');
    @endphp

    {{-- ================= Invoice ================= --}}
    <div class="print-area mx-auto rounded-lg border border-slate-200 bg-white p-6 shadow-card dark:border-slate-800 dark:bg-slate-900" style="max-width: 760px;">

        <div class="mb-4 flex items-start justify-between border-b border-slate-200 pb-4 dark:border-slate-700">
            <div>
                <h1 class="text-xl font-bold">{{ $company['company_name'] }}</h1>
                <p class="text-xs text-slate-600 dark:text-slate-400">{{ $company['address'] }}</p>
                <p class="text-xs text-slate-600 dark:text-slate-400">
                    Tel: {{ $company['phone'] }}
                    @if ($company['ntn']) · NTN: {{ $company['ntn'] }} @endif
                    @if ($company['strn']) · STRN: {{ $company['strn'] }} @endif
                </p>
            </div>
            <div class="text-right text-xs">
                <div class="font-bold">INVOICE</div>
                <div class="tabular">{{ $sale->invoice_number }}</div>
                <div class="tabular text-slate-500">{{ $sale->sale_date?->format('d M Y H:i') }}</div>
                @if ($company['tax_authority'])
                    <div class="text-slate-500">{{ $company['tax_authority'] }}</div>
                @endif
            </div>
        </div>

        <div class="mb-4 grid grid-cols-2 gap-4 text-xs">
            <div>
                <div class="font-bold uppercase text-slate-500">Customer</div>
                <div>{{ $sale->customer?->name ?? 'Walk-in Customer' }}</div>
                @if ($sale->customer?->phone)<div>{{ $sale->customer->phone }}</div>@endif
                @if ($sale->vehicle)<div>Vehicle: {{ $sale->vehicle->registration_number }}</div>@endif
            </div>
            <div class="text-right">
                <div class="font-bold uppercase text-slate-500">Cashier</div>
                <div>{{ $sale->employee?->name }}</div>
                @if ($sale->shift)<div>Shift: {{ $sale->shift->shift_number }}</div>@endif
            </div>
        </div>

        <table class="mb-4 w-full text-sm">
            <thead class="border-y border-slate-200 text-xs uppercase dark:border-slate-700">
                <tr>
                    <th class="py-2 text-left">Description</th>
                    <th class="py-2 text-right">Qty (L)</th>
                    <th class="py-2 text-right">Rate</th>
                    <th class="py-2 text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($sale->items as $item)
                    <tr class="border-b border-slate-100 dark:border-slate-800">
                        <td class="py-2">{{ $item->fuelProduct?->name }}</td>
                        <td class="tabular py-2 text-right">{{ number_format((float) $item->litres, 3) }}</td>
                        <td class="tabular py-2 text-right">{{ number_format((float) $item->rate, 2) }}</td>
                        <td class="tabular py-2 text-right">{{ number_format((float) $item->amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="ml-auto max-w-xs space-y-1 text-sm">
            <div class="flex justify-between">
                <span class="text-slate-500">Subtotal</span>
                <span class="tabular">{{ number_format((float) $sale->subtotal, 2) }}</span>
            </div>
            @if (\App\Support\Money::compare($sale->discount, '0') > 0)
                <div class="flex justify-between">
                    <span class="text-slate-500">Discount</span>
                    <span class="tabular">− {{ number_format((float) $sale->discount, 2) }}</span>
                </div>
            @endif
            {{-- SRO 1006(I)/2021: the Rs.1 PoS service fee is a separate line. --}}
            <div class="flex justify-between">
                <span class="text-slate-500">PoS Service Fee</span>
                <span class="tabular">{{ number_format((float) $fee, 2) }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-300 pt-1 text-base font-bold dark:border-slate-600">
                <span>Total</span>
                <span class="tabular">{{ number_format((float) $sale->total, 2) }}</span>
            </div>
            <div class="flex justify-between">
                <span class="text-slate-500">Paid</span>
                <span class="tabular">{{ number_format((float) $sale->payments->sum('amount'), 2) }}</span>
            </div>
        </div>

        <div class="mt-4 border-t border-slate-200 pt-3 text-xs dark:border-slate-700">
            <div class="mb-1 font-bold uppercase text-slate-500">Payment</div>
            @foreach ($sale->payments as $payment)
                <div class="flex justify-between">
                    <span>{{ \App\Models\SalePayment::methods()[$payment->method] ?? $payment->method }}</span>
                    <span class="tabular">{{ number_format((float) $payment->amount, 2) }}</span>
                </div>
            @endforeach
        </div>

        <div class="mt-6 border-t border-slate-200 pt-3 text-center text-xs text-slate-500 dark:border-slate-700">
            <p class="mb-1">{{ app(\App\Services\System\SettingService::class)->get('thank_you_message') }}</p>
            <p>{{ $company['company_name'] }} · {{ $company['phone'] }} · {{ $company['address'] }}</p>
        </div>
    </div>
@endsection
