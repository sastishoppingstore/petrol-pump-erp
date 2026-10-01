@extends('layouts.app')

@section('title', 'Edit ' . $role->label)
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('roles.index') }}" class="hover:text-vital-primary">{{ __('admin.roles.title') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('ui.actions.edit') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ {{ __('admin.roles.edit_role') }} — {{ $role->label }}</h1>
        <p>{{ __('admin.roles.edit_subtitle') }}</p>
    </div>

    <form method="POST" action="{{ route('roles.update', $role) }}" novalidate>
        @csrf
        @method('PUT')
        @include('roles._form', [
            'submitLabel' => __('admin.common.save_changes'),
            'isBuiltIn' => array_key_exists($role->name, \App\Support\PermissionList::builtInRoles()),
        ])
    </form>
@endsection
