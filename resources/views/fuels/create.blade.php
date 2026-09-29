@extends('layouts.app')

@section('title', 'Add Fuel Product')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('fuels.index') }}">Fuel Products</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Add Fuel Product</h1>
    <form method="POST" action="{{ route('fuels.store') }}" novalidate>
        @csrf
        @include('fuels._form', ['submitLabel' => 'Create Fuel Product'])
    </form>
@endsection
