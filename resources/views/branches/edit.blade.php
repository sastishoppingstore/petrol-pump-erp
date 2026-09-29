@extends('layouts.app')

@section('title', 'Edit ' . $branch->name)
@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('branches.index') }}">Branches</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Edit Branch — {{ $branch->name }}</h1>

    <form method="POST" action="{{ route('branches.update', $branch) }}" novalidate>
        @csrf
        @method('PUT')
        @include('branches._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
