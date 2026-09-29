@extends('layouts.app')

@section('title', 'Add Nozzle')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('nozzles.index') }}">Nozzles</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Add Nozzle</h1>
    <form method="POST" action="{{ route('nozzles.store') }}" novalidate>
        @csrf
        @include('nozzles._form', ['submitLabel' => 'Create Nozzle'])
    </form>
@endsection
