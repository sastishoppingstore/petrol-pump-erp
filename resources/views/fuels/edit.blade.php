@extends('layouts.app')

@section('title', 'Edit ' . $fuel->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('fuels.index') }}">Fuel Products</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Edit — {{ $fuel->name }}</h1>
    <form method="POST" action="{{ route('fuels.update', $fuel) }}" novalidate>
        @csrf
        @method('PUT')
        @include('fuels._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
