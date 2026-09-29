@extends('layouts.app')

@section('title', 'Edit Nozzle')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('nozzles.index') }}">Nozzles</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Edit Nozzle — {{ $nozzle->label() }}</h1>
    <form method="POST" action="{{ route('nozzles.update', $nozzle) }}" novalidate>
        @csrf
        @method('PUT')
        @include('nozzles._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
