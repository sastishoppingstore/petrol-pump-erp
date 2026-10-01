@extends('layouts.app')

@section('title', 'Edit ' . $role->label)
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('roles.index') }}" class="hover:text-vital-primary">Roles</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Edit</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ Edit Role — {{ $role->label }}</h1>
        <p>Role details aur permissions update karo</p>
    </div>

    <form method="POST" action="{{ route('roles.update', $role) }}" novalidate>
        @csrf
        @method('PUT')
        @include('roles._form', [
            'submitLabel' => 'Save Changes',
            'isBuiltIn' => array_key_exists($role->name, \App\Support\PermissionList::builtInRoles()),
        ])
    </form>
@endsection
