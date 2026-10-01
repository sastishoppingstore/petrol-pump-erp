@extends('layouts.app')

@section('title', 'Register New Customer')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Customers</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">New Customer</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>🧑 Register New Customer</h1>
        <p>Add an individual, corporate fleet, or agricultural credit customer.</p>
    </div>

    @if($errors->any())
        <div class="glass-card mx-auto max-w-3xl border-red-200 bg-red-50/80 p-4 text-center text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-400">
            <div class="mb-1 font-bold">Please correct the following errors:</div>
            <ul class="space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('customers.store') }}" class="glass-card mx-auto max-w-3xl space-y-6 p-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="field-3d">
                <label for="code" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Customer Code *</label>
                <input type="text" id="code" name="code" value="{{ old('code', $suggestedCode) }}" required
                       class="input-3d text-center font-mono">
                <p class="mt-1 text-center text-xs text-slate-400">Unique identifier for billing and POS search.</p>
            </div>

            <div class="field-3d">
                <label for="name" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Customer / Business Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. M/s Sheikhupura Goods Transport"
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="phone" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Pakistani Mobile Phone *</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required placeholder="0300-1234567"
                       class="input-3d text-center font-mono">
                <p class="mt-1 text-center text-xs text-slate-400">Required for WhatsApp payment reminder links (03XX-XXXXXXX).</p>
            </div>

            <div class="field-3d">
                <label for="cnic" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">CNIC Number</label>
                <input type="text" id="cnic" name="cnic" value="{{ old('cnic') }}" placeholder="35201-1234567-1"
                       class="input-3d text-center font-mono">
                <p class="mt-1 text-center text-xs text-slate-400">13-digit Pakistani National Identity Card number.</p>
            </div>

            <div class="field-3d">
                <label for="credit_limit" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Credit Limit (Rs.) *</label>
                <input type="number" step="0.01" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', '0.00') }}" required
                       class="input-3d text-center">
                <p class="mt-1 text-center text-xs text-slate-400">Enter 0 for unlimited credit line. Enforced at POS.</p>
            </div>

            <div class="field-3d">
                <label for="opening_balance" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Opening Balance (Rs.)</label>
                <input type="number" step="0.01" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0.00') }}"
                       class="input-3d text-center">
                <p class="mt-1 text-center text-xs text-slate-400">Positive figure represents initial amount owed by customer.</p>
            </div>

            <div class="field-3d">
                <label for="ntn_number" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">National Tax Number (NTN)</label>
                <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number') }}" placeholder="e.g. 1234567-8"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="email" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="accounts@client.com"
                       class="input-3d text-center">
            </div>

            <div class="field-3d sm:col-span-2">
                <label for="address" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Postal / Station Address</label>
                <textarea id="address" name="address" rows="2" placeholder="e.g. Lahore-Sargodha Road, Sheikhupura"
                          class="input-3d text-center">{{ old('address') }}</textarea>
            </div>

            <div class="flex items-center justify-center gap-2">
                <input type="checkbox" id="is_tax_liable" name="is_tax_liable" value="1" @checked(old('is_tax_liable'))
                       class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                <label for="is_tax_liable" class="text-sm font-medium text-slate-700 dark:text-slate-300">FBR Tax Liable (Issue formal tax invoices)</label>
            </div>

            <div class="field-3d">
                <label for="status" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Account Status *</label>
                <select id="status" name="status" class="input-3d text-center">
                    <option value="ACTIVE" @selected(old('status') === 'ACTIVE')>Active (Can purchase on credit)</option>
                    <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>Inactive / Suspended</option>
                </select>
            </div>

            <div class="field-3d sm:col-span-2">
                <label for="notes" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Notes &amp; Payment Terms</label>
                <textarea id="notes" name="notes" rows="2" placeholder="e.g. 15-day billing cycle, payment by crossed cheque"
                          class="input-3d text-center">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 border-t border-slate-200/70 pt-5 dark:border-slate-700/60">
            <a href="{{ route('customers.index') }}" class="btn-3d btn-3d-ghost">
                Cancel
            </a>
            <button type="submit" class="btn-3d btn-3d-primary">
                Save &amp; Open Customer Account
            </button>
        </div>
    </form>
</div>
@endsection
