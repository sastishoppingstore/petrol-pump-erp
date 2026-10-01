@extends('layouts.app')

@section('title', 'Edit ' . $branch->name)
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('branches.index') }}" class="hover:text-vital-primary">{{ __('admin.branches.title') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.actions.edit') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ {{ __('admin.branches.edit_branch') }} — {{ $branch->name }}</h1>
        <p>{{ __('admin.branches.edit_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('branches.update', $branch) }}" novalidate>
        @csrf
        @method('PUT')
        @include('branches._form', ['submitLabel' => __('admin.common.save_changes')])
    </form>
@endsection
