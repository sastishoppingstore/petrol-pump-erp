@extends('layouts.app')

@section('title', 'Edit ' . $role->label)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Edit Role — {{ $role->label }}</h1>

    <form method="POST" action="{{ route('roles.update', $role) }}" novalidate>
        @csrf
        @method('PUT')
        @include('roles._form', [
            'submitLabel' => 'Save Changes',
            'isBuiltIn' => array_key_exists($role->name, \App\Support\PermissionList::builtInRoles()),
        ])
    </form>
@endsection
