@extends('layouts.app')

@section('title', 'Add Dispenser')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dispensers.index') }}">Dispensers</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Add Dispenser</h1>
    <form method="POST" action="{{ route('dispensers.store') }}" novalidate>
        @csrf
        @include('dispensers._form', ['submitLabel' => 'Create Dispenser'])
    </form>
@endsection
