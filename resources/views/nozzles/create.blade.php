@extends('layouts.app')

@section('title', __('forecourt.nozzles.add'))
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('nozzles.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.nozzles') }}</a></li>
    <li class="text-slate-500">{{ __('forecourt.common.add') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('forecourt.nozzles.create_heading') }}</h1>
        <p>{{ __('forecourt.nozzles.create_sub') }}</p>
    </div>
    <form method="POST" action="{{ route('nozzles.store') }}" novalidate>
        @csrf
        @include('nozzles._form', ['submitLabel' => __('forecourt.nozzles.create_submit')])
    </form>
@endsection
