@extends('layouts.app')

@section('title', 'Add Role')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('roles.index') }}">Roles</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Add Role</h1>

    <form method="POST" action="{{ route('roles.store') }}" novalidate>
        @csrf
        @include('roles._form', [
            'submitLabel' => 'Create Role',
            'isBuiltIn' => false,
        ])
    </form>
@endsection
