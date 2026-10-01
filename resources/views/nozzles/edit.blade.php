@extends('layouts.app')

@section('title', 'Edit Nozzle')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('nozzles.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Nozzles</a></li>
    <li class="text-slate-500">Edit</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>✏️ Edit Nozzle — {{ $nozzle->label() }}</h1>
        <p>Nozzle ki tafseel tabdeel karein — opening meter history hai, tabdeel nahi hota</p>
    </div>
    <form method="POST" action="{{ route('nozzles.update', $nozzle) }}" novalidate>
        @csrf
        @method('PUT')
        @include('nozzles._form', ['submitLabel' => 'Save Changes'])
    </form>
@endsection
