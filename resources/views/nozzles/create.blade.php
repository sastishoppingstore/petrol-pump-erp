@extends('layouts.app')

@section('title', 'Add Nozzle')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('nozzles.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Nozzles</a></li>
    <li class="text-slate-500">Add</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>➕ Add Nozzle</h1>
        <p>Naya nozzle — dispenser, tank aur fuel ke saath link karein</p>
    </div>
    <form method="POST" action="{{ route('nozzles.store') }}" novalidate>
        @csrf
        @include('nozzles._form', ['submitLabel' => 'Create Nozzle'])
    </form>
@endsection
