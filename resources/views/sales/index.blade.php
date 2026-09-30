@extends('layouts.app')

@section('title', 'Sales History')
@section('breadcrumb')
    <li class="text-slate-500">Sales History</li>
@endsection

@section('content')
    <h1 class="mb-4 text-xl font-bold">Sales History</h1>

    <form method="GET" action="{{ route('sales.index') }}" class="mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <div>
                <label class="mb-1 block text-xs font-medium">Invoice no.</label>
                <input type="text" name="invoice" value="{{ request('invoice') }}"
                       class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Customer</label>
                <input type="text" name="customer" value="{{ request('customer') }}"
                       class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Cashier</label>
                <select name="employee" class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">All</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected((string) request('employee') === (string) $employee->id)>
                            {{ $employee->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Status</label>
                <select name="status" class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">All</option>
                    @foreach (['COMPLETED', 'VOIDED', 'REFUNDED'] as $s)
                        <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">From</label>
                <input type="date" name="from" value="{{ request('from') }}"
                       class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">To</label>
                <input type="date" name="to" value="{{ request('to') }}"
                       class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
        </div>
        <button type="submit" class="mt-3 rounded-md border border-slate-300 px-4 py-1.5 text-sm dark:border-slate-700">Filter</button>
    </form>

    <div class="rounded-lg border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="mb-0 w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-2 text-left">Invoice</th>
                        <th class="px-4 py-2 text-left">When</th>
                        <th class="px-4 py-2 text-left">Customer</th>
                        <th class="px-4 py-2 text-left">Cashier</th>
                        <th class="tabular px-4 py-2 text-right">Litres</th>
                        <th class="tabular px-4 py-2 text-right">Total</th>
                        <th class="px-4 py-2 text-center">Status</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr class="border-t border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-2 font-mono text-xs">{{ $sale->invoice_number }}</td>
                            <td class="whitespace-nowrap px-4 py-2 text-xs">{{ $sale->sale_date?->format('d M Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                            <td class="px-4 py-2 text-xs">{{ $sale->employee?->name }}</td>
                            <td class="tabular px-4 py-2 text-right">{{ number_format((float) ($sale->litres ?? 0), 3) }}</td>
                            <td class="tabular px-4 py-2 text-right font-semibold">{{ number_format((float) $sale->total, 2) }}</td>
                            <td class="px-4 py-2 text-center">
                                <span @class([
                                    'rounded px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $sale->status === 'COMPLETED',
                                    'bg-red-100 text-red-800' => $sale->status === 'VOIDED',
                                    'bg-amber-100 text-amber-800' => $sale->status === 'REFUNDED',
                                ])>{{ $sale->status }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-2 text-right">
                                <a href="{{ route('sales.show', $sale) }}" class="text-xs text-navy-700 hover:underline dark:text-slate-300">View</a>
                                @if ($sale->isCompleted())
                                    @can('sales.void')
                                        <a href="{{ route('sales.void', $sale) }}" class="ml-2 text-xs text-red-600 hover:underline">Void</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-slate-500">No sales in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $sales->links() }}</div>
    </div>
@endsection
