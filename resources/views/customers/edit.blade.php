@extends('layouts.app')

@section('title', 'Edit ' . $customer->name)

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Customers</a></li>
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('customers.show', $customer) }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ $customer->code }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Edit</span></li>
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Edit Customer Profile</h1>
            <p class="text-sm text-slate-500">Update contact details, credit limits, or billing settings.</p>
        </div>
        <a href="{{ route('customers.show', $customer) }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
            Back to Profile
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

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Customer Code *</label>
                <input type="text" id="code" name="code" value="{{ old('code', $customer->code) }}" required
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Customer / Business Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $customer->name) }}" required
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Pakistani Mobile Phone *</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $customer->phone) }}" required
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="cnic" class="block text-sm font-medium text-slate-700 dark:text-slate-300">CNIC Number</label>
                <input type="text" id="cnic" name="cnic" value="{{ old('cnic', $customer->cnic) }}"
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="credit_limit" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Credit Limit (Rs.) *</label>
                <input type="number" step="0.01" id="credit_limit" name="credit_limit" value="{{ old('credit_limit', $customer->credit_limit) }}" required
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="ntn_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300">National Tax Number (NTN)</label>
                <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number', $customer->ntn_number) }}"
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $customer->email) }}"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Account Status *</label>
                <select id="status" name="status" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    <option value="ACTIVE" @selected(old('status', $customer->status) === 'ACTIVE')>Active</option>
                    <option value="INACTIVE" @selected(old('status', $customer->status) === 'INACTIVE')>Inactive / Suspended</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Postal / Station Address</label>
                <textarea id="address" name="address" rows="2"
                          class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ old('address', $customer->address) }}</textarea>
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" id="is_tax_liable" name="is_tax_liable" value="1" @checked(old('is_tax_liable', $customer->is_tax_liable))
                       class="rounded border-slate-300 text-red-600 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800">
                <label for="is_tax_liable" class="text-sm font-medium text-slate-700 dark:text-slate-300">FBR Tax Liable (Issue formal tax invoices)</label>
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Notes & Payment Terms</label>
                <textarea id="notes" name="notes" rows="2"
                          class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ old('notes', $customer->notes) }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
            <a href="{{ route('customers.show', $customer) }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white hover:bg-red-700 shadow-sm transition">
                Update Customer Account
            </button>
        </div>
    </form>
</div>
@endsection
