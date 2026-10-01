@extends('layouts.app')

@section('title', 'Void ' . $sale->invoice_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('sales.index') }}">Sales</a></li>
    <li class="text-slate-500">{{ $sale->invoice_number }}</li>
    <li class="text-slate-500">Void</li>
@endsection

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="page-head">
            <h1>↩️ Void / Refund {{ $sale->invoice_number }}</h1>
            <p>
                This returns {{ number_format((float) $sale->total_litres, 3) }} L to the tank,
                rolls the meter back and reverses the cash. The original sale is kept for audit —
                it is never deleted.
            </p>
        </div>

        <form method="POST" action="{{ route('sales.void.update', $sale) }}" class="glass-card p-6 sm:p-8">
            @csrf
            @method('PUT')

            <div class="mb-5 rounded-2xl bg-slate-100/80 p-4 text-center shadow-inner dark:bg-slate-800/70">
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-slate-500">Invoice</span>
                    <span class="tabular font-bold">{{ $sale->invoice_number }}</span>
                </div>
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-slate-500">Total</span>
                    <span class="tabular font-black text-vital-primary">Rs. {{ number_format((float) $sale->total, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-slate-500">Cashier</span>
                    <span class="font-semibold">{{ $sale->employee?->name }}</span>
                </div>
            </div>

            <div class="field-3d mb-4">
                <label for="reason" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">Reason <span class="text-red-600">*</span></label>
                <textarea id="reason" name="reason" rows="3" required minlength="3" maxlength="500"
                          class="input-3d"
                          placeholder="Wrong nozzle selected / customer cancelled">{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="mb-6 flex cursor-pointer items-center justify-center gap-2 text-sm font-semibold">
                <input type="checkbox" name="as_refund" value="1" @checked(old('as_refund'))
                       class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                Treat as a <strong>refund</strong> rather than a void
            </label>

            <div class="flex flex-wrap justify-center gap-3">
                <button type="submit"
                        onclick="return confirm('This reverses stock, meter and cash. Continue?')"
                        class="btn-3d !bg-gradient-to-b !from-rose-500 !to-rose-700 text-white">
                    Confirm Void
                </button>
                <a href="{{ route('sales.show', $sale) }}" class="btn-3d btn-3d-ghost">Cancel</a>
            </div>
        </form>
    </div>
@endsection
