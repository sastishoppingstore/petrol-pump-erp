@extends('layouts.app')

@section('title', 'Edit ' . $branch->name)
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('branches.index') }}" class="hover:text-vital-primary">Branches</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Edit</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ Edit Branch — {{ $branch->name }}</h1>
        <p>Branch details update karo</p>
    </div>

    <form method="POST" action="{{ route('branches.update', $branch) }}" novalidate>
        @csrf
        @method('PUT')
        @include('branches._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
