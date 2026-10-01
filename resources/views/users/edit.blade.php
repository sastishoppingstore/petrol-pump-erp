@extends('layouts.app')

@section('title', 'Edit ' . $user->name)
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('users.index') }}" class="hover:text-vital-primary">Users</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Edit</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ Edit User — {{ $user->name }}</h1>
        <p>Account details, roles aur branch access update karo</p>
    </div>

    <form method="POST" action="{{ route('users.update', $user) }}" novalidate>
        @csrf
        @method('PUT')
        @include('users._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
