@extends('layouts.app')

@section('title', 'Add Dispenser')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('dispensers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Dispensers</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Add</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>⛽ Add Dispenser</h1>
        <p>Register a new forecourt dispenser</p>
    </div>

    <form method="POST" action="{{ route('dispensers.store') }}" novalidate class="mx-auto w-full max-w-3xl">
        @csrf
        @include('dispensers._form', ['submitLabel' => 'Create Dispenser'])
    </form>
@endsection
