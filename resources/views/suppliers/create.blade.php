@extends('layouts.app')

@section('title', 'Register Fuel Supplier / OMC')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('suppliers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Suppliers</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">New Supplier</span></li>
@endsection

@section('content')
<div class="max-w-4xl space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Register Supplier / OMC</h1>
            <p class="text-sm text-slate-500">Add an Oil Marketing Company, lubricant distributor or local vendor.</p>
        </div>
        <a href="{{ route('suppliers.index') }}" class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
            Cancel
        </a>
    </div>

    @if($errors->any())
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800">
            <ul class="list-disc pl-5 space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('suppliers.store') }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 space-y-6">
        @csrf

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div>
                <label for="code" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Supplier Code *</label>
                <input type="text" id="code" name="code" value="{{ old('code', $suggestedCode) }}" required
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Company / Supplier Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required placeholder="e.g. Vital Petroleum OMC (Pvt) Ltd"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="contact_person" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Contact Person / Supply Manager</label>
                <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person') }}" placeholder="e.g. Tariq Mehmood"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="phone" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Telephone / Mobile</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone') }}" placeholder="042-35789012 or 0300-1234567"
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="ntn_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300">NTN Number</label>
                <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number') }}" placeholder="e.g. 4210987-6"
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="strn_number" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Sales Tax Registration (STRN)</label>
                <input type="text" id="strn_number" name="strn_number" value="{{ old('strn_number') }}" placeholder="e.g. 03-05-2710-001-82"
                       class="mt-1 w-full rounded-lg border-slate-300 font-mono text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="opening_balance" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Opening Payable Balance (Rs.)</label>
                <input type="number" step="0.01" id="opening_balance" name="opening_balance" value="{{ old('opening_balance', '0.00') }}"
                       class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>

            <div>
                <label for="status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Status *</label>
                <select id="status" name="status" class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                    <option value="ACTIVE" @selected(old('status') === 'ACTIVE')>Active</option>
                    <option value="INACTIVE" @selected(old('status') === 'INACTIVE')>Inactive</option>
                </select>
            </div>

            <div class="sm:col-span-2">
                <label for="address" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Depot / Terminal Address</label>
                <textarea id="address" name="address" rows="2" placeholder="e.g. Machike Bulk Oil Installation, Sheikhupura"
                          class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ old('address') }}</textarea>
            </div>

            <div class="sm:col-span-2">
                <label for="notes" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Terms & Notes</label>
                <textarea id="notes" name="notes" rows="2" placeholder="e.g. Delivery via freight carrier, IFEM rebate included in invoice"
                          class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-red-500 focus:ring-red-500 dark:border-slate-700 dark:bg-slate-800 dark:text-white">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 pt-5 dark:border-slate-800">
            <a href="{{ route('suppliers.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                Cancel
            </a>
            <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-medium text-white hover:bg-red-700 shadow-sm transition">
                Save Supplier
            </button>
        </div>
    </form>
</div>
@endsection
