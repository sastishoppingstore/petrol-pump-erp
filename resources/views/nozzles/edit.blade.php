@extends('layouts.app')

@section('title', __('forecourt.nozzles.edit_title'))
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('nozzles.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.nozzles') }}</a></li>
    <li class="text-slate-500">{{ __('ui.actions.edit') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('forecourt.nozzles.edit_heading') }} {{ $nozzle->label() }}</h1>
        <p>{{ __('forecourt.nozzles.edit_sub') }}</p>
    </div>
    <form method="POST" action="{{ route('nozzles.update', $nozzle) }}" novalidate>
        @csrf
        @method('PUT')
        @include('nozzles._form', ['submitLabel' => __('forecourt.common.save_changes')])
    </form>
@endsection
