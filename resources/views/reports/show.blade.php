@extends('layouts.app')

@section('title', $meta['title'] . ' — ' . $meta['urdu'])

@section('content')
<div class="container-fluid py-3">
    <!-- Top Bar with Back and Actions -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <a href="{{ route('reports.index') }}" class="btn btn-sm btn-outline-secondary me-2">
                &larr; Back to Reports Hub
            </a>
            <span class="badge bg-danger">{{ $meta['group'] }}</span>
        </div>
        <div class="btn-group">
            <a href="{{ request()->fullUrlWithQuery(['format' => 'print']) }}" target="_blank" class="btn btn-sm btn-outline-dark">
                🖨️ Print
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'pdf']) }}" class="btn btn-sm btn-outline-danger">
                📄 Export PDF
            </a>
            <a href="{{ request()->fullUrlWithQuery(['format' => 'csv']) }}" class="btn btn-sm btn-outline-success">
                📊 Export Excel/CSV
            </a>
        </div>
    </div>

    <!-- Filter Bar Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('reports.show', $report) }}" class="row g-2 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small fw-bold text-muted mb-1">Date Filter Preset</label>
                    <select name="preset" class="form-select form-select-sm" onchange="this.form.submit()">
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
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Select Shift</label>
                        <select name="shift_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($shifts as $s)
                                <option value="{{ $s->id }}" {{ request('shift_id') == $s->id ? 'selected' : '' }}>
                                    #{{ $s->shift_number }} ({{ $s->start_time->format('d M H:i') }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @elseif($range['preset'] === 'custom')
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">From Date</label>
                        <input type="date" name="from" class="form-control form-control-sm" value="{{ $range['from'] }}">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted mb-1">To Date</label>
                        <input type="date" name="to" class="form-control form-control-sm" value="{{ $range['to'] }}">
                    </div>
                @endif

                @if(in_array($report, ['customer-ledger'], true))
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Select Customer</label>
                        <select name="customer_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ $selectedCustomerId == $c->id ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(in_array($report, ['supplier-ledger'], true))
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Select Supplier</label>
                        <select name="supplier_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($suppliers as $sp)
                                <option value="{{ $sp->id }}" {{ $selectedSupplierId == $sp->id ? 'selected' : '' }}>
                                    {{ $sp->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if(in_array($report, ['bank-book'], true))
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Select Bank Account</label>
                        <select name="bank_account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                            @foreach($bankAccounts as $ba)
                                <option value="{{ $ba->id }}" {{ $selectedBankAccountId == $ba->id ? 'selected' : '' }}>
                                    {{ $ba->account_title }} - {{ $ba->bank?->name }} ({{ $ba->account_number }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-danger w-100">
                        Filter Report
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Report Header Banner -->
    <div class="card mb-4 border-0 shadow-sm" style="border-left: 5px solid #D71920 !important;">
        <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h4 class="fw-bold mb-0 text-dark">{{ $meta['title'] }}</h4>
                <div class="text-danger fw-semibold" style="font-family: 'Jameel Noori Nastaleeq', 'Urdu Typesetting', Tahoma; font-size: 1.15rem;">
                    {{ $meta['urdu'] }}
                </div>
            </div>
            <div class="text-end text-muted small">
                <div><strong>Station:</strong> {{ $branch->name }} (Sheikhupura)</div>
                <div><strong>Filter Range:</strong> {{ $range['label'] }}</div>
            </div>
        </div>
    </div>

    <!-- Report Content Rendering -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @include('reports.partials.' . str_replace('-', '_', $report), ['data' => $data, 'range' => $range])
        </div>
    </div>
</div>
@endsection
