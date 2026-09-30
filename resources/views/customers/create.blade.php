@extends('layouts.app')

@section('title', 'Register New Customer')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Customers</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">New Customer</span></li>
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Register New Customer</h1>
            <p class="text-sm text-slate-500">Add an individual, corporate fleet, or agricultural credit customer.</p>
        </div>
        <a href="{{ route('customers.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
            Cancel
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-400">
            <div class="font-bold mb-1">Please correct the following errors:</div>
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('customers.store') }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Customer Code *</label>
                <input type="text" id="code" name="code" value="{{ old('code', $suggestedCode) }}" required
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                <p class="mt-1 text-xs text-slate-400">Unique identifier for billing and POS search.</p>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Customer / Business Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. M/s Sheikhupura Goods Transport"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Pakistani Mobile Phone *</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" required placeholder="0300-1234567"
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                <p class="mt-1 text-xs text-slate-400">Required for WhatsApp payment reminder links (03XX-XXXXXXX).</p>
            </div>

            <div>
                <label for="cnic" class="block text-sm font-medium text-slate-700 dark:text-slate-300">CNIC Number</label>
                <input type="text" id="cnic" name="cnic" value="{{ old('cnic') }}" placeholder="35201-1234567-1"
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                <p class="mt-1 text-xs text-slate-400">13-digit Pakistani National Identity Card number.</p>
            </div>

            <div>
                <label for="credit_limit" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Credit Limit (Rs.) *</label>
                <input type="number" step="0.01" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', '0.00') }}" required
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                <p class="mt-1 text-xs text-slate-400">Enter 0 for unlimited credit line. Enforced at POS.</p>
            </div>

            <div>
                <label for="opening_balance" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Opening Balance (Rs.)</label>
                <input type="number" step="0.01" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0.00') }}"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                <p class="mt-1 text-xs text-slate-400">Positive figure represents initial amount owed by customer.</p>
            </div>

            <div>
                <label for="ntn_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300">National Tax Number (NTN)</label>
                <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number') }}" placeholder="e.g. 1234567-8"
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="accounts@client.com"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div class="sm:col-span-2">
                <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Postal / Station Address</label>
                <textarea id="address" name="address" rows="2" placeholder="e.g. Lahore-Sargodha Road, Sheikhupura"
                          class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ old('address') }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_tax_liable" name="is_tax_liable" value="1" @checked(old('is_tax_liable'))
                       class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                <label for="is_tax_liable" class="text-sm font-medium text-slate-700 dark:text-slate-300">FBR Tax Liable (Issue formal tax invoices)</label>
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Account Status *</label>
                <select id="status" name="status" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    <option value="ACTIVE" @selected(old('status') === 'ACTIVE')>Active (Can purchase on credit)</option>
                    <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>Inactive / Suspended</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Notes & Payment Terms</label>
                <textarea id="notes" name="notes" rows="2" placeholder="e.g. 15-day billing cycle, payment by crossed cheque"
                          class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
            <a href="{{ route('customers.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white hover:bg-red-700 shadow-sm transition">
                Save & Open Customer Account
            </button>
        </div>
    </form>
</div>
@endsection
