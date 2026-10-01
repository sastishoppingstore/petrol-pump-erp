@extends('layouts.app')

@section('title', 'Add Role')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('roles.index') }}" class="hover:text-vital-primary">Roles</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Add</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>➕ Add Role</h1>
        <p>Naya role banao aur uski permissions matrix me select karo</p>
    </div>

    <form method="POST" action="{{ route('roles.store') }}" novalidate>
        @csrf
        @include('roles._form', [
            'submitLabel' => 'Create Role',
            'isBuiltIn' => false,
        ])
    </form>
@endsection
