@extends('layouts.app')

@section('title', __('forecourt.dispensers.add'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('dispensers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.dispensers') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('forecourt.common.add') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('forecourt.dispensers.create_heading') }}</h1>
        <p>{{ __('forecourt.dispensers.create_sub') }}</p>
    </div>

    <form method="POST" action="{{ route('dispensers.store') }}" novalidate class="mx-auto w-full max-w-3xl">
        @csrf
        @include('dispensers._form', ['submitLabel' => __('forecourt.dispensers.create_submit')])
    </form>
@endsection
