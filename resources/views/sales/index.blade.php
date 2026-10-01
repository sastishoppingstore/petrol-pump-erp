@extends('layouts.app')

@section('title', 'Sales History')
@section('breadcrumb')
    <li class="text-slate-500">Sales History</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>📜 Sales History</h1>
        <p>Har sale ka record — invoice, customer, cashier aur payment ki tafseel</p>
        <div class="page-actions">
            <a href="{{ route('pos.index') }}" class="btn-3d btn-3d-primary">⛽ New Sale (POS)</a>
        </div>
    </div>

    <form method="GET" action="{{ route('sales.index') }}" class="glass-card filter-bar-3d mb-6">
        <div class="field-3d w-full sm:w-auto">
            <label for="f-invoice">Invoice no.</label>
            <input id="f-invoice" type="text" name="invoice" value="{{ request('invoice') }}" class="input-3d sm:w-40">
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-customer">Customer</label>
            <input id="f-customer" type="text" name="customer" value="{{ request('customer') }}" class="input-3d sm:w-40">
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-employee">Cashier</label>
            <select id="f-employee" name="employee" class="input-3d sm:w-44">
                <option value="">All</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((string) request('employee') === (string) $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-status">Status</label>
            <select id="f-status" name="status" class="input-3d sm:w-36">
                <option value="">All</option>
                @foreach (['COMPLETED', 'VOIDED', 'REFUNDED'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-from">From</label>
            <input id="f-from" type="date" name="from" value="{{ request('from') }}" class="input-3d sm:w-40">
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-to">To</label>
            <input id="f-to" type="date" name="to" value="{{ request('to') }}" class="input-3d sm:w-40">
        </div>
        <button type="submit" class="btn-3d btn-3d-navy">🔍 Filter</button>
    </form>

    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>When</th>
                        <th>Customer</th>
                        <th>Cashier</th>
                        <th>Litres</th>
                        <th>Total (Rs.)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sales as $sale)
                        <tr>
                            <td class="font-mono text-xs font-bold text-slate-800 dark:text-white">{{ $sale->invoice_number }}</td>
                            <td class="whitespace-nowrap text-xs">{{ $sale->sale_date?->format('d M Y H:i') }}</td>
                            <td class="font-semibold">{{ $sale->customer?->name ?? 'Walk-in' }}</td>
                            <td class="text-xs">{{ $sale->employee?->name }}</td>
                            <td class="tabular font-semibold">{{ number_format((float) ($sale->litres ?? 0), 3) }}</td>
                            <td class="tabular font-black text-slate-900 dark:text-white">{{ number_format((float) $sale->total, 2) }}</td>
                            <td>
                                <span @class([
                                    'pill-status',
                                    'pill-active' => $sale->status === 'COMPLETED',
                                    'pill-danger' => $sale->status === 'VOIDED',
                                    'pill-pending' => $sale->status === 'REFUNDED',
                                ])><span class="dot"></span>{{ $sale->status }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('sales.show', $sale) }}" class="btn-3d btn-3d-ghost btn-3d-sm">View</a>
                                @if ($sale->isCompleted())
                                    @can('sales.void')
                                        <a href="{{ route('sales.void', $sale) }}" class="btn-3d btn-3d-sm ml-1 !bg-gradient-to-b !from-rose-500 !to-rose-700 text-white">Void</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-500">
                                <div class="text-4xl">🧾</div>
                                <div class="mt-2 font-semibold">No sales in this range.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 px-5 py-3 dark:border-slate-700/50">{{ $sales->links() }}</div>
    </div>

    <a href="{{ route('pos.index') }}" class="fab-3d" title="New Sale">
        <span class="text-2xl leading-none">＋</span>
    </a>
@endsection
