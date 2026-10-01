@extends('layouts.app')

@section('title', __('forecourt.tanks.edit_title'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('tanks.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.tanks') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.actions.edit') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('forecourt.tanks.edit_heading') }}</h1>
        <p>{{ $tank->displayName() }}</p>
    </div>

    <form method="POST" action="{{ route('tanks.update', $tank) }}" novalidate class="mx-auto w-full max-w-3xl">
        @csrf
        @method('PUT')
        @include('tanks._form', ['submitLabel' => __('forecourt.common.save_changes')])
    </form>
@endsection
