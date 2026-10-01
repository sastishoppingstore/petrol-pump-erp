@extends('layouts.app')

@section('title', 'Add Fuel Product')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('fuels.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Fuel Products</a></li>
    <li class="text-slate-500">Add</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>➕ Add Fuel Product</h1>
        <p>Naya fuel product banayein — code, price aur minimum stock ke saath</p>
    </div>
    <form method="POST" action="{{ route('fuels.store') }}" novalidate>
        @csrf
        @include('fuels._form', ['submitLabel' => 'Create Fuel Product'])
    </form>
@endsection
