@extends('layouts.app')

@section('title', __('admin.users.add_user'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('users.index') }}" class="hover:text-vital-primary">{{ __('admin.users.title') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.common.add') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>➕ {{ __('admin.users.add_user') }}</h1>
        <p>{{ __('admin.users.create_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('users.store') }}" novalidate>
        @csrf
        @include('users._form', ['submitLabel' => __('admin.users.create_user')])
    </form>
@endsection
