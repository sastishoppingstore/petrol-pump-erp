@extends('layouts.app')

@section('title', 'Add Tank')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('tanks.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Tanks</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Add</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>🛢️ Add Tank</h1>
        <p>Register a new underground storage tank</p>
    </div>

    <form method="POST" action="{{ route('tanks.store') }}" novalidate class="mx-auto w-full max-w-3xl">
        @csrf
        @include('tanks._form', ['submitLabel' => 'Create Tank'])
    </form>
@endsection
