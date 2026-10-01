@extends('layouts.app')

@section('title', 'Edit ' . $fuel->name)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('fuels.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Fuel Products</a></li>
    <li class="text-slate-500">Edit</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ Edit — {{ $fuel->name }}</h1>
        <p>Fuel product ki tafseel tabdeel karein — price change history me record hoga</p>
    </div>
    <form method="POST" action="{{ route('fuels.update', $fuel) }}" novalidate>
        @csrf
        @method('PUT')
        @include('fuels._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
