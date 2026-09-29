@extends('layouts.app')

@section('title', 'Add Branch')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('branches.index') }}">Branches</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Add Branch</h1>

    <form method="POST" action="{{ route('branches.store') }}" novalidate>
        @csrf
        @include('branches._form', ['submitLabel' => 'Create Branch'])
    </form>
@endsection
