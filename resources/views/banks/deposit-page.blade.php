@extends('layouts.app')

@section('title', __('finance.bank_deposits.page_title'))
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">{{ __('finance.common.banks_word') }}</a></li>
    <li class="text-slate-500">{{ __('finance.bank_deposits.record_deposit') }}</li>
@endsection

@section('content')
    <div class="page-head">
        <h1>{{ __('finance.bank_deposits.page_heading') }}</h1>
        <p>{{ __('finance.bank_deposits.page_sub') }}</p>
    </div>

    <livewire:bank-deposit-manager />
@endsection
