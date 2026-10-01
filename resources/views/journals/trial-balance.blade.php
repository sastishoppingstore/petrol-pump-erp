@extends('layouts.app')

@section('title', 'Trial Balance (میزان نامہ) — General Ledger')

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <span class="mb-2 inline-block rounded bg-vital-primary px-2.5 py-0.5 text-xs font-bold text-white">General Ledger</span>
        <h1>Trial Balance (میزان نامہ)</h1>
        <p style="font-family: 'Jameel Noori Nastaleeq', Tahoma; font-size: 1.15rem;">
            تمام فعال کھاتوں کے اختتامی نام و جمع بیلنس کی میزان پڑتال
        </p>
        <div class="page-actions">
            <a href="{{ route('journals.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">&larr; Back to Journals</a>
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'print', 'to' => $asOfDate]) }}" target="_blank" class="btn-3d btn-3d-navy btn-3d-sm">
                🖨️ Print
            </a>
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'pdf', 'to' => $asOfDate]) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                📄 PDF
            </a>
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'csv', 'to' => $asOfDate]) }}" class="btn-3d btn-3d-success btn-3d-sm">
                📊 Export CSV
            </a>
        </div>
    </div>

    {{-- Date Filter Card --}}
    <div class="glass-card mx-auto max-w-xl p-4">
        <form method="GET" action="{{ route('journals.trial-balance') }}" class="flex flex-wrap items-end justify-center gap-3">
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-bold text-slate-500">As of Date (آج تک کی تاریخ)</label>
                <input type="date" name="as_of_date" class="input-3d w-full text-center text-sm" value="{{ $asOfDate }}" onchange="this.form.submit()">
            </div>
            <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">Apply Date</button>
        </form>
    </div>

    {{-- Trial Balance Content Table (shared report partial — untouched) --}}
    <div class="glass-card p-4">
        @include('reports.partials.trial_balance', ['data' => $data, 'isPrint' => false])
    </div>
</div>
@endsection
