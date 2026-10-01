@extends('layouts.app')

@section('title', __('forecourt.dispensers.edit_title'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('dispensers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.dispensers') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.actions.edit') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('forecourt.dispensers.edit_heading') }}</h1>
        <p>{{ $dispenser->dispenser_number }}</p>
    </div>

    <form method="POST" action="{{ route('dispensers.update', $dispenser) }}" novalidate class="mx-auto w-full max-w-3xl">
        @csrf
        @method('PUT')
        @include('dispensers._form', ['submitLabel' => __('forecourt.common.save_changes')])
    </form>
@endsection
