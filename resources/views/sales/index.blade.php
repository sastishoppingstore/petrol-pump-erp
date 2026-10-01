@extends('layouts.app')

@section('title', {{ __('sales.sales.title') }})
@section('breadcrumb')
    <li class="text-slate-500">{{ __('sales.sales.title') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>📜 {{ __('sales.sales.title') }}</h1>
        <p>{{ __('sales.sales.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('pos.index') }}" class="btn-3d btn-3d-primary">⛽ {{ __('sales.sales.new_sale_pos') }}</a>
        </div>
    </div>

    <form method="GET" action="{{ route('sales.index') }}" class="glass-card filter-bar-3d mb-6">
        <div class="field-3d w-full sm:w-auto">
            <label for="f-invoice">{{ __('sales.sales.invoice_no') }}</label>
            <input id="f-invoice" type="text" name="invoice" value="{{ request('invoice') }}" class="input-3d sm:w-40">
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-customer">{{ __('sales.sales.customer') }}</label>
            <input id="f-customer" type="text" name="customer" value="{{ request('customer') }}" class="input-3d sm:w-40">
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-employee">{{ __('sales.sales.cashier') }}</label>
            <select id="f-employee" name="employee" class="input-3d sm:w-44">
                <option value="">{{ __('sales.sales.all') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected((string) request('employee') === (string) $employee->id)>
                        {{ $employee->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-status">{{ __('sales.sales.status') }}</label>
            <select id="f-status" name="status" class="input-3d sm:w-36">
                <option value="">{{ __('sales.sales.all') }}</option>
                @foreach (['COMPLETED', 'VOIDED', 'REFUNDED'] as $s)
                    <option value="{{ $s }}" @selected(request('status') === $s)>{{ $s }}</option>
                @endforeach
            </select>
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-from">{{ __('sales.sales.from') }}</label>
            <input id="f-from" type="date" name="from" value="{{ request('from') }}" class="input-3d sm:w-40">
        </div>
        <div class="field-3d w-full sm:w-auto">
            <label for="f-to">{{ __('sales.sales.to') }}</label>
            <input id="f-to" type="date" name="to" value="{{ request('to') }}" class="input-3d sm:w-40">
        </div>
        <button type="submit" class="btn-3d btn-3d-navy">🔍 {{ __('ui.actions.filter') }}</button>
    </form>

    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('sales.sales.invoice') }}</th>
                        <th>{{ __('sales.sales.when') }}</th>
                        <th>{{ __('sales.sales.customer') }}</th>
                        <th>{{ __('sales.sales.cashier') }}</th>
                        <th>{{ __('sales.sales.litres') }}</th>
                        <th>{{ __('sales.sales.total_rs') }}</th>
                        <th>{{ __('sales.sales.status') }}</th>
                        <th>{{ __('sales.sales.actions') }}</th>
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
                                <a href="{{ route('sales.show', $sale) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.view') }}</a>
                                @if ($sale->isCompleted())
                                    @can('sales.void')
                                        <a href="{{ route('sales.void', $sale) }}" class="btn-3d btn-3d-sm ml-1 !bg-gradient-to-b !from-rose-500 !to-rose-700 text-white">{{ __('sales.sales.void') }}</a>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-10 text-center text-slate-500">
                                <div class="text-4xl">🧾</div>
                                <div class="mt-2 font-semibold">{{ __('sales.sales.empty') }}</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 px-5 py-3 dark:border-slate-700/50">{{ $sales->links() }}</div>
    </div>

    <a href="{{ route('pos.index') }}" class="fab-3d" title="{{ __('sales.sales.new_sale') }}">
        <span class="text-2xl leading-none">＋</span>
    </a>
@endsection
