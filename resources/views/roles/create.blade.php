@extends('layouts.app')

@section('title', __('admin.roles.add_role'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('roles.index') }}" class="hover:text-vital-primary">{{ __('admin.roles.title') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.common.add') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>➕ {{ __('admin.roles.add_role') }}</h1>
        <p>{{ __('admin.roles.create_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('roles.store') }}" novalidate>
        @csrf
        @include('roles._form', [
            'submitLabel' => __('admin.roles.create_role'),
            'isBuiltIn' => false,
        ])
    </form>
@endsection
