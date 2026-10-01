@extends('layouts.app')

@section('title', 'Add Branch')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('branches.index') }}" class="hover:text-vital-primary">Branches</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Add</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>➕ Add Branch</h1>
        <p>Nayi branch / station location add karo</p>
    </div>

    <form method="POST" action="{{ route('branches.store') }}" novalidate>
        @csrf
        @include('branches._form', ['submitLabel' => 'Create Branch'])
    </form>
@endsection
