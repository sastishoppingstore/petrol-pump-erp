@extends('layouts.app')

@section('title', 'Edit ' . $supplier->name)

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('suppliers.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Suppliers</a></li>
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('suppliers.show', $supplier) }}" class="hover:text-slate-700 dark:hover:text-slate-200">{{ $supplier->code }}</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">Edit</span></li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1>✏️ Edit Supplier Details</h1>
        <p>Update supplier contact info, depot address or tax registration for {{ $supplier->name }}.</p>
        <div class="page-actions">
            <a href="{{ route('suppliers.show', $supplier) }}" class="btn-3d btn-3d-ghost btn-3d-sm">← Back to Supplier</a>
        </div>
    </div>

    @if($errors->any())
        <div class="glass-card mx-auto max-w-3xl border-red-200 bg-red-50/80 p-4 text-center text-sm text-red-800 dark:border-red-900/50 dark:bg-red-950/40 dark:text-red-400">
            <ul class="space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="glass-card mx-auto max-w-3xl space-y-6 p-6">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <div class="field-3d">
                <label for="code" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Supplier Code *</label>
                <input type="text" id="code" name="code" value="{{ old('code', $supplier->code) }}" required
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="name" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Supplier Name *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $supplier->name) }}" required
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="contact_person" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Contact Person</label>
                <input type="text" id="contact_person" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}"
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="phone" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Phone</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $supplier->phone) }}"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="ntn_number" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">NTN Number</label>
                <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number', $supplier->ntn_number) }}"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="strn_number" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">STRN Number</label>
                <input type="text" id="strn_number" name="strn_number" value="{{ old('strn_number', $supplier->strn_number) }}"
                       class="input-3d text-center font-mono">
            </div>

            <div class="field-3d">
                <label for="email" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Email Address</label>
                <input type="email" id="email" name="email" value="{{ old('email', $supplier->email) }}"
                       class="input-3d text-center">
            </div>

            <div class="field-3d">
                <label for="status" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Status *</label>
                <select id="status" name="status" class="input-3d text-center">
                    <option value="ACTIVE" @selected(old('status', $supplier->status) === 'ACTIVE')>Active</option>
                    <option value="INACTIVE" @selected(old('status', $supplier->status) === 'INACTIVE')>Inactive</option>
                </select>
            </div>

            <div class="field-3d sm:col-span-2">
                <label for="address" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Depot / Terminal Address</label>
                <textarea id="address" name="address" rows="2"
                          class="input-3d text-center">{{ old('address', $supplier->address) }}</textarea>
            </div>

            <div class="field-3d sm:col-span-2">
                <label for="notes" class="mb-1.5 block text-center text-sm font-semibold text-slate-700 dark:text-slate-300">Notes &amp; Commercial Terms</label>
                <textarea id="notes" name="notes" rows="2"
                          class="input-3d text-center">{{ old('notes', $supplier->notes) }}</textarea>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3 border-t border-slate-200/70 pt-5 dark:border-slate-700/60">
            <a href="{{ route('suppliers.show', $supplier) }}" class="btn-3d btn-3d-ghost">
                Cancel
            </a>
            <button type="submit" class="btn-3d btn-3d-primary">
                Update Supplier
            </button>
        </div>
    </form>
</div>
@endsection
