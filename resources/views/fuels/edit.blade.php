@extends('layouts.app')

@section('title', __('ui.actions.edit') . ' ' . $fuel->name)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('fuels.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.fuel_products') }}</a></li>
    <li class="text-slate-500">{{ __('ui.actions.edit') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('forecourt.fuels.edit_heading') }} {{ $fuel->name }}</h1>
        <p>{{ __('forecourt.fuels.edit_sub') }}</p>
    </div>
    <form method="POST" action="{{ route('fuels.update', $fuel) }}" novalidate>
        @csrf
        @method('PUT')
        @include('fuels._form', ['submitLabel' => __('forecourt.common.save_changes')])
    </form>
@endsection
