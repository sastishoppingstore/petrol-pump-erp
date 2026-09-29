@extends('layouts.app')

@section('title', 'Edit Tank')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tanks.index') }}">Tanks</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Edit Tank — {{ $tank->displayName() }}</h1>
    <form method="POST" action="{{ route('tanks.update', $tank) }}" novalidate>
        @csrf
        @method('PUT')
        @include('tanks._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
