@extends('layouts.app')

@section('title', 'Add User')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('users.index') }}" class="hover:text-vital-primary">Users</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Add</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>➕ Add User</h1>
        <p>New login account banao — role aur branch access neeche select karo</p>
    </div>

    <form method="POST" action="{{ route('users.store') }}" novalidate>
        @csrf
        @include('users._form', ['submitLabel' => 'Create User'])
    </form>
@endsection
