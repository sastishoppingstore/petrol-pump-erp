@extends('layouts.app')

@section('title', __('forecourt.tanks.add'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('tanks.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.tanks') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('forecourt.common.add') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('forecourt.tanks.create_heading') }}</h1>
        <p>{{ __('forecourt.tanks.create_sub') }}</p>
    </div>

    <form method="POST" action="{{ route('tanks.store') }}" novalidate class="mx-auto w-full max-w-3xl">
        @csrf
        @include('tanks._form', ['submitLabel' => __('forecourt.tanks.create_submit')])
    </form>
@endsection
