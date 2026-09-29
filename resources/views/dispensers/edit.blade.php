@extends('layouts.app')

@section('title', 'Edit Dispenser')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dispensers.index') }}">Dispensers</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Edit Dispenser — {{ $dispenser->dispenser_number }}</h1>
    <form method="POST" action="{{ route('dispensers.update', $dispenser) }}" novalidate>
        @csrf
        @method('PUT')
        @include('dispensers._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
