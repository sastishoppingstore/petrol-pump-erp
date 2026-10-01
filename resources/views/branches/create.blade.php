@extends('layouts.app')

@section('title', __('admin.branches.add_branch'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('branches.index') }}" class="hover:text-vital-primary">{{ __('admin.branches.title') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.common.add') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>➕ {{ __('admin.branches.add_branch') }}</h1>
        <p>{{ __('admin.branches.create_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('branches.store') }}" novalidate>
        @csrf
        @include('branches._form', ['submitLabel' => __('admin.branches.create_branch')])
    </form>
@endsection
