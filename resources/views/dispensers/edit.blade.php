@extends('layouts.app')

@section('title', 'Edit Dispenser')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('dispensers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Dispensers</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Edit</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ Edit Dispenser</h1>
        <p>{{ $dispenser->dispenser_number }}</p>
    </div>

    <form method="POST" action="{{ route('dispensers.update', $dispenser) }}" novalidate class="mx-auto w-full max-w-3xl">
        @csrf
        @method('PUT')
        @include('dispensers._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
