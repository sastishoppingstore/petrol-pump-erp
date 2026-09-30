@extends('layouts.app')

@section('title', 'Trial Balance (میزان نامہ) — General Ledger')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <a href="{{ route('journals.index') }}" class="btn btn-sm btn-outline-secondary">&larr; Back to Journals</a>
                <span class="badge bg-danger">General Ledger</span>
            </div>
            <h2 class="h3 fw-bold mb-1">Trial Balance (میزان نامہ)</h2>
            <div class="text-muted" style="font-family: 'Jameel Noori Nastaleeq', Tahoma; font-size: 1.15rem;">
                تمام فعال کھاتوں کے اختتامی نام و جمع بیلنس کی میزان پڑتال
            </div>
        </div>
        <div class="btn-group">
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'print', 'to' => $asOfDate]) }}" target="_blank" class="btn btn-outline-dark">
                🖨️ Print
            </a>
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'pdf', 'to' => $asOfDate]) }}" class="btn btn-outline-danger">
                📄 PDF
            </a>
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'csv', 'to' => $asOfDate]) }}" class="btn btn-outline-success">
                📊 Export CSV
            </a>
        </div>
    </div>

    <!-- Date Filter Card -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body p-3">
            <form method="GET" action="{{ route('journals.trial-balance') }}" class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold text-muted mb-1">As of Date (آج تک کی تاریخ)</label>
                    <input type="date" name="as_of_date" class="form-control form-control-sm" value="{{ $asOfDate }}" onchange="this.form.submit()">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-sm btn-danger w-100">Apply Date</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Trial Balance Content Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            @include('reports.partials.trial_balance', ['data' => $data, 'isPrint' => false])
        </div>
    </div>
</div>
@endsection
