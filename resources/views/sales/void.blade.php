@extends('layouts.app')

@section('title', 'Void ' . $sale->invoice_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('sales.index') }}">Sales</a></li>
    <li class="text-slate-500">{{ $sale->invoice_number }}</li>
    <li class="text-slate-500">Void</li>
@endsection

@section('content')
    <div class="mx-auto max-w-2xl">
        <h1 class="mb-1 text-xl font-bold">Void / Refund {{ $sale->invoice_number }}</h1>
        <p class="mb-4 text-sm text-slate-600 dark:text-slate-400">
            This returns {{ number_format((float) $sale->total_litres, 3) }} L to the tank,
            rolls the meter back and reverses the cash. The original sale is kept for audit —
            it is never deleted.
        </p>

        <form method="POST" action="{{ route('sales.void.update', $sale) }}"
              class="rounded-lg border border-slate-200 bg-white p-5 shadow-card dark:border-slate-800 dark:bg-slate-900">
            @csrf
            @method('PUT')

            <div class="mb-3 rounded-md bg-slate-50 p-3 text-sm dark:bg-slate-800">
                <div class="flex justify-between">
                    <span class="text-slate-500">Invoice</span>
                    <span class="tabular font-semibold">{{ $sale->invoice_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Total</span>
                    <span class="tabular">Rs. {{ number_format((float) $sale->total, 2) }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-500">Cashier</span>
                    <span>{{ $sale->employee?->name }}</span>
                </div>
            </div>

            <div class="mb-3">
                <label for="reason" class="mb-1 block text-sm font-medium">Reason <span class="text-red-600">*</span></label>
                <textarea id="reason" name="reason" rows="3" required minlength="3" maxlength="500"
                          class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                          placeholder="Wrong nozzle selected / customer cancelled">{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="mb-4 flex cursor-pointer items-center gap-2 text-sm">
                <input type="checkbox" name="as_refund" value="1" @checked(old('as_refund'))
                       class="rounded border-slate-300 text-navy-700 focus:ring-navy-600">
                Treat as a <strong>refund</strong> rather than a void
            </label>

            <div class="flex gap-2">
                <button type="submit"
                        onclick="return confirm('This reverses stock, meter and cash. Continue?')"
                        class="rounded-md bg-red-600 px-4 py-2 font-semibold text-white hover:bg-red-700">
                    Confirm void
                </button>
                <a href="{{ route('sales.show', $sale) }}"
                   class="rounded-md border border-slate-300 px-4 py-2 dark:border-slate-700">Cancel</a>
            </div>
        </form>
    </div>
@endsection
