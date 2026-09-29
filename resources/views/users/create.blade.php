@extends('layouts.app')

@section('title', 'Add User')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Add User</h1>

    <form method="POST" action="{{ route('users.store') }}" novalidate>
        @csrf
        @include('users._form', ['submitLabel' => 'Create User'])
    </form>
@endsection
