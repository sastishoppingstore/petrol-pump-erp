@extends('layouts.app')

@section('title', 'Edit ' . $user->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Users</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Edit User — {{ $user->name }}</h1>

    <form method="POST" action="{{ route('users.update', $user) }}" novalidate>
        @csrf
        @method('PUT')
        @include('users._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
