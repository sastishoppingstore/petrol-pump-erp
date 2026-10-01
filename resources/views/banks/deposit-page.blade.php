@extends('layouts.app')

@section('title', 'Bank Deposits')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Record deposit</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>Bank Deposit</h1>
        <p>Select the bank and account, then record the cash leaving the till.</p>
    </div>

    <livewire:bank-deposit-manager />
@endsection
