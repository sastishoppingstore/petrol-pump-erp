@extends('layouts.app')

@section('title', __('forecourt.fuels.add'))
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('fuels.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ __('ui.nav.fuel_products') }}</a></li>
    <li class="text-slate-500">{{ __('forecourt.common.add') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('forecourt.fuels.create_heading') }}</h1>
        <p>{{ __('forecourt.fuels.create_sub') }}</p>
    </div>
    <form method="POST" action="{{ route('fuels.store') }}" novalidate>
        @csrf
        @include('fuels._form', ['submitLabel' => __('forecourt.fuels.create_submit')])
    </form>
@endsection
