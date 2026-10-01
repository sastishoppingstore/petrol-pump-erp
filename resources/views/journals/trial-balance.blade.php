@extends('layouts.app')

@section('title', __('finance.journals.tb_heading') . ' — ' . __('finance.journals.gl_badge'))

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <span class="mb-2 inline-block rounded bg-vital-primary px-2.5 py-0.5 text-xs font-bold text-white">{{ __('finance.journals.gl_badge') }}</span>
        <h1>{{ __('finance.journals.tb_heading') }}</h1>
        <p style="font-family: 'Jameel Noori Nastaleeq', Tahoma; font-size: 1.15rem;">
            {{ __('finance.journals.tb_sub') }}
        </p>
        <div class="page-actions">
            <a href="{{ route('journals.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('finance.journals.back_journals') }}</a>
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'print', 'to' => $asOfDate]) }}" target="_blank" class="btn-3d btn-3d-navy btn-3d-sm">
                {{ __('finance.journals.print_btn') }}
            </a>
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'pdf', 'to' => $asOfDate]) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                📄 PDF
            </a>
            <a href="{{ route('reports.show', ['report' => 'trial-balance', 'format' => 'csv', 'to' => $asOfDate]) }}" class="btn-3d btn-3d-success btn-3d-sm">
                {{ __('finance.journals.export_csv') }}
            </a>
        </div>
    </div>

    {{-- Date Filter Card --}}
    <div class="glass-card mx-auto max-w-xl p-4">
        <form method="GET" action="{{ route('journals.trial-balance') }}" class="flex flex-wrap items-end justify-center gap-3">
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-bold text-slate-500">{{ __('finance.journals.as_of_date') }}</label>
                <input type="date" name="as_of_date" class="input-3d w-full text-center text-sm" value="{{ $asOfDate }}" onchange="this.form.submit()">
            </div>
            <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">{{ __('finance.journals.apply_date') }}</button>
        </form>
    </div>

    {{-- Trial Balance Content Table (shared report partial — untouched) --}}
    <div class="glass-card p-4">
        @include('reports.partials.trial_balance', ['data' => $data, 'isPrint' => false])
    </div>
</div>
@endsection
