@extends('layouts.app')

@section('title', 'New Amanat Entry / نئی امانت')
@section('breadcrumb')
    <li><a href="{{ route('amanat.index') }}" class="hover:text-navy-700 dark:hover:text-slate-300">Amanat Deposits</a></li>
    <li class="text-slate-500">New Entry</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl space-y-6">
    <div class="page-head">
        <h1>New Amanat Entry / نئی امانت انٹری</h1>
        <p>Deposit jama karein, deduction kaatein ya signed adjustment lagayein. Balance system khud hisaab karega.</p>
    </div>

    <form method="POST" action="{{ route('amanat.store') }}" class="glass-card space-y-5 p-6">
        @csrf

        <div class="field-3d">
            <label for="customer_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Customer / کسٹمر *</label>
            <select id="customer_id" name="customer_id" required class="input-3d w-full text-center text-sm">
                <option value="">— Select customer —</option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer->id }}" @selected((int) old('customer_id', $selectedCustomerId) === $customer->id)>
                        {{ $customer->name }} ({{ $customer->code }})
                    </option>
                @endforeach
            </select>
            @error('customer_id') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="field-3d">
                <label for="type" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Entry Type / قسم *</label>
                <select id="type" name="type" required class="input-3d w-full text-center text-sm">
                    <option value="deposit" @selected(old('type') === 'deposit')>Deposit / جمع</option>
                    <option value="deduction" @selected(old('type') === 'deduction')>Deduction / کٹوتی</option>
                    <option value="adjustment" @selected(old('type') === 'adjustment')>Adjustment / تصحیح (signed)</option>
                </select>
                @error('type') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>

            <div class="field-3d">
                <label for="amount" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Amount (Rs.) / رقم *</label>
                <input id="amount" type="number" step="0.01" name="amount" value="{{ old('amount') }}" required
                       placeholder="0.00" class="input-3d tabular w-full text-center font-mono text-sm">
                <span class="mt-1 block text-center text-[11px] text-slate-400">Adjustment ke liye negative raqam bhi de sakte hain (masalan -500).</span>
                @error('amount') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="field-3d">
            <label for="reference" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Reference / حوالہ</label>
            <input id="reference" type="text" name="reference" value="{{ old('reference') }}" maxlength="100"
                   placeholder="Receipt no, bank slip no, waghera" class="input-3d w-full text-center text-sm">
            @error('reference') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="field-3d">
            <label for="note" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Note / نوٹ</label>
            <textarea id="note" name="note" rows="3" maxlength="500" class="input-3d w-full text-sm"
                      placeholder="Optional tafseel...">{{ old('note') }}</textarea>
            @error('note') <span class="mt-1 block text-center text-xs text-red-600">{{ $message }}</span> @enderror
        </div>

        <div class="flex justify-center gap-2 pt-2">
            <a href="{{ route('amanat.index') }}" class="btn-3d btn-3d-ghost">Cancel</a>
            <button type="submit" class="btn-3d btn-3d-success">Save Entry / محفوظ کریں</button>
        </div>
    </form>
</div>
@endsection
