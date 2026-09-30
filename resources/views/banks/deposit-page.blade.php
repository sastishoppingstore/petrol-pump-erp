@extends('layouts.app')

@section('title', 'Bank Deposits')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Record deposit</li>
@endsection

@section('content')
    <h1 class="mb-1 text-xl font-bold">Bank Deposit</h1>
    <p class="mb-4 text-sm text-slate-500">
        Select the bank and account, then record the cash leaving the till.
    </p>

    <livewire:bank-deposit-manager />
@endsection
