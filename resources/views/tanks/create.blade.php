@extends('layouts.app')

@section('title', 'Add Tank')
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('tanks.index') }}">Tanks</a></li>
    <li class="breadcrumb-item active">Add</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Add Tank</h1>
    <form method="POST" action="{{ route('tanks.store') }}" novalidate>
        @csrf
        @include('tanks._form', ['submitLabel' => 'Create Tank'])
    </form>
@endsection
