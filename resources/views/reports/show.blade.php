@extends('layouts.app')

@section('title', $meta['title'] . ' — ' . $meta['urdu'])

@section('content')
<div class="space-y-6">

    {{-- Report Header (centered) --}}
    <div class="page-head">
        <div class="mb-3">
            <span class="badge bg-danger">{{ $meta['group'] }}</span>
        </div>
        <h1>{{ $meta['title'] }}</h1>
        <p style="font-family: 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma; font-size: 1.2rem; color: rgb(var(--brand-primary-rgb));">{{ $meta['urdu'] }}</p>
        <p><strong>{{ __('finance.reports.station_label') }}:</strong> {{ $branch->name }} (Sheikhupura) &bull; <strong>{{ __('finance.reports.filter_range') }}:</strong> {{ $range['label'] }}</p>
        <div class="page-actions">
            <a href="{{ route('reports.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                {{ __('finance.reports.back_hub') }}
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'print']) }}" target="_blank" class="btn-3d btn-3d-navy btn-3d-sm">
                {{ __('finance.journals.print_btn') }}
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                {{ __('finance.reports.export_pdf') }}
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn-3d btn-3d-success btn-3d-sm">
                {{ __('finance.reports.export_excel_csv') }}
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="glass-card">
        <form method="GET" action="{{ route('reports.show', $report) }}" class="filter-bar-3d">
            <div class="field-3d w-full sm:w-64">
                <label>{{ __('finance.reports.date_preset') }}</label>
                <select name="preset" class="input-3d" onchange="this.form.submit()">
                    <option value="today" {{ $range['preset'] === 'today' ? 'selected' : '' }}>{{ __('finance.reports.preset_today') }}</option>
                    <option value="yesterday" {{ $range['preset'] === 'yesterday' ? 'selected' : '' }}>{{ __('finance.reports.preset_yesterday') }}</option>
                    <option value="week" {{ $range['preset'] === 'week' ? 'selected' : '' }}>{{ __('finance.reports.preset_week') }}</option>
                    <option value="15days" {{ $range['preset'] === '15days' ? 'selected' : '' }}>{{ __('finance.reports.preset_15') }}</option>
                    <option value="month" {{ $range['preset'] === 'month' ? 'selected' : '' }}>{{ __('finance.reports.preset_month') }}</option>
                    <option value="year" {{ $range['preset'] === 'year' ? 'selected' : '' }}>{{ __('finance.reports.preset_year') }}</option>
                    <option value="shift" {{ $range['preset'] === 'shift' ? 'selected' : '' }}>{{ __('finance.reports.preset_shift') }}</option>
                    <option value="custom" {{ $range['preset'] === 'custom' ? 'selected' : '' }}>{{ __('finance.reports.preset_custom') }}</option>
                </select>
            </div>

            @if($range['preset'] === 'shift')
                <div class="field-3d w-full sm:w-72">
                    <label>{{ __('finance.reports.select_shift') }}</label>
                    <select name="shift_id" class="input-3d" onchange="this.form.submit()">
                        @foreach($shifts as $s)
                            <option value="{{ $s->id }}" {{ request('shift_id') == $s->id ? 'selected' : '' }}>
                                #{{ $s->shift_number }} ({{ $s->start_time->format('d M H:i') }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @elseif($range['preset'] === 'custom')
                <div class="field-3d w-full sm:w-44">
                    <label>{{ __('finance.common.from_date') }}</label>
                    <input type="date" name="from" class="input-3d" value="{{ $range['from'] }}">
                </div>
                <div class="field-3d w-full sm:w-44">
                    <label>{{ __('finance.common.to_date') }}</label>
                    <input type="date" name="to" class="input-3d" value="{{ $range['to'] }}">
                </div>
            @endif

            @if(in_array($report, ['customer-ledger'], true))
                <div class="field-3d w-full sm:w-72">
                    <label>{{ __('finance.reports.select_customer_label') }}</label>
                    <select name="customer_id" class="input-3d" onchange="this.form.submit()">
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $selectedCustomerId == $c->id ? 'selected' : '' }}>
                                {{ $c->name }} ({{ $c->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(in_array($report, ['supplier-ledger'], true))
                <div class="field-3d w-full sm:w-72">
                    <label>{{ __('finance.reports.select_supplier_label') }}</label>
                    <select name="supplier_id" class="input-3d" onchange="this.form.submit()">
                        @foreach($suppliers as $sp)
                            <option value="{{ $sp->id }}" {{ $selectedSupplierId == $sp->id ? 'selected' : '' }}>
                                {{ $sp->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(in_array($report, ['bank-book'], true))
                <div class="field-3d w-full sm:w-80">
                    <label>{{ __('finance.reports.select_bank_account_label') }}</label>
                    <select name="bank_account_id" class="input-3d" onchange="this.form.submit()">
                        @foreach($bankAccounts as $ba)
                            <option value="{{ $ba->id }}" {{ $selectedBankAccountId == $ba->id ? 'selected' : '' }}>
                                {{ $ba->account_title }} - {{ $ba->bank?->name }} ({{ $ba->account_number }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div>
                <button type="submit" class="btn-3d btn-3d-primary">
                    {{ __('finance.reports.filter_report') }}
                </button>
            </div>
        </form>
    </div>

    {{-- Report Content Rendering --}}
    <div class="glass-card p-4 sm:p-6">
        @include('reports.partials.' . str_replace('-', '_', $report), ['data' => $data, 'range' => $range])
    </div>
</div>
@endsection
