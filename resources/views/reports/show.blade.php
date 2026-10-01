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
        <p><strong>Station:</strong> {{ $branch->name }} (Sheikhupura) &bull; <strong>Filter Range:</strong> {{ $range['label'] }}</p>
        <div class="page-actions">
            <a href="{{ route('reports.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                &larr; Back to Reports Hub
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'print']) }}" target="_blank" class="btn-3d btn-3d-navy btn-3d-sm">
                🖨️ Print
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                📄 Export PDF
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn-3d btn-3d-success btn-3d-sm">
                📊 Export Excel/CSV
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="glass-card">
        <form method="GET" action="{{ route('reports.show', $report) }}" class="filter-bar-3d">
            <div class="field-3d w-full sm:w-64">
                <label>Date Filter Preset</label>
                <select name="preset" class="input-3d" onchange="this.form.submit()">
                    <option value="today" {{ $range['preset'] === 'today' ? 'selected' : '' }}>Today (آج)</option>
                    <option value="yesterday" {{ $range['preset'] === 'yesterday' ? 'selected' : '' }}>Yesterday (گزشتہ کل)</option>
                    <option value="week" {{ $range['preset'] === 'week' ? 'selected' : '' }}>Last 7 Days (گزشتہ ہفتہ)</option>
                    <option value="15days" {{ $range['preset'] === '15days' ? 'selected' : '' }}>Last 15 Days (پندرہ دن)</option>
                    <option value="month" {{ $range['preset'] === 'month' ? 'selected' : '' }}>This Month (موجودہ مہینہ)</option>
                    <option value="year" {{ $range['preset'] === 'year' ? 'selected' : '' }}>This Year (موجودہ سال)</option>
                    <option value="shift" {{ $range['preset'] === 'shift' ? 'selected' : '' }}>By Shift (بلحاظ شفٹ)</option>
                    <option value="custom" {{ $range['preset'] === 'custom' ? 'selected' : '' }}>Custom Date Range (مخصوص تاریخیں)</option>
                </select>
            </div>

            @if($range['preset'] === 'shift')
                <div class="field-3d w-full sm:w-72">
                    <label>Select Shift</label>
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
                    <label>From Date</label>
                    <input type="date" name="from" class="input-3d" value="{{ $range['from'] }}">
                </div>
                <div class="field-3d w-full sm:w-44">
                    <label>To Date</label>
                    <input type="date" name="to" class="input-3d" value="{{ $range['to'] }}">
                </div>
            @endif

            @if(in_array($report, ['customer-ledger'], true))
                <div class="field-3d w-full sm:w-72">
                    <label>Select Customer</label>
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
                    <label>Select Supplier</label>
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
                    <label>Select Bank Account</label>
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
                    Filter Report
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
