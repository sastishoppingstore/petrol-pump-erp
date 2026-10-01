@extends('layouts.app')

@section('title', 'Edit ' . $user->name)
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('users.index') }}" class="hover:text-vital-primary">{{ __('admin.users.title') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.actions.edit') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ {{ __('admin.users.edit_user') }} — {{ $user->name }}</h1>
        <p>{{ __('admin.users.edit_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('users.update', $user) }}" novalidate>
        @csrf
        @method('PUT')
        @include('users._form', ['submitLabel' => __('admin.common.save_changes')])
    </form>
@endsection
