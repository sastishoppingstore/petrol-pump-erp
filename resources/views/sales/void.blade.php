@extends('layouts.app')

@section('title', 'Void ' . $sale->invoice_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('sales.index') }}">{{ __('sales.sale_void.breadcrumb_sales') }}</a></li>
    <li class="text-slate-500">{{ $sale->invoice_number }}</li>
    <li class="text-slate-500">{{ __('sales.sale_void.breadcrumb_void') }}</li>
@endsection

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="page-head">
            <h1>↩️ {{ __('sales.sale_void.void_refund') }} {{ $sale->invoice_number }}</h1>
            <p>
                {{ __('sales.sale_void.intro_1') }} {{ number_format((float) $sale->total_litres, 3) }} L {{ __('sales.sale_void.intro_2') }}
                {{ __('sales.sale_void.intro_3') }}
                {{ __('sales.sale_void.intro_4') }}
            </p>
        </div>

        <form method="POST" action="{{ route('sales.void.update', $sale) }}" class="glass-card p-6 sm:p-8">
            @csrf
            @method('PUT')

            <div class="mb-5 rounded-2xl bg-slate-100/80 p-4 text-center shadow-inner dark:bg-slate-800/70">
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-slate-500">{{ __('sales.sale_void.invoice') }}</span>
                    <span class="tabular font-bold">{{ $sale->invoice_number }}</span>
                </div>
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-slate-500">{{ __('sales.sale_void.total') }}</span>
                    <span class="tabular font-black text-vital-primary">Rs. {{ number_format((float) $sale->total, 2) }}</span>
                </div>
                <div class="flex justify-between py-1 text-sm">
                    <span class="text-slate-500">{{ __('sales.sale_void.cashier') }}</span>
                    <span class="font-semibold">{{ $sale->employee?->name }}</span>
                </div>
            </div>

            <div class="field-3d mb-4">
                <label for="reason" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('sales.sale_void.reason') }} <span class="text-red-600">*</span></label>
                <textarea id="reason" name="reason" rows="3" required minlength="3" maxlength="500"
                          class="input-3d"
                          placeholder="{{ __('sales.sale_void.reason_placeholder') }}">{{ old('reason') }}</textarea>
                @error('reason') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d mb-4">
                <label for="manager_pin" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">
                    {{ session('locale') === 'ur' ? 'مینیجر پن (Manager PIN)' : 'Manager PIN' }}
                    @if ((float) $sale->total > 5000) <span class="text-red-600">*</span> @endif
                </label>
                <input type="password" id="manager_pin" name="manager_pin" inputmode="numeric" autocomplete="off" maxlength="20"
                       class="input-3d text-center font-mono tracking-[0.4em]"
                       placeholder="••••">
                <p class="mt-1 text-center text-xs text-slate-400">
                    {{ session('locale') === 'ur'
                        ? 'Rs. 5,000 سے بڑی سیل کے void کے لیے کسی مینیجر/مجاز صارف کا پن لازم ہے۔'
                        : 'A manager / authorised user PIN is required to void a sale over Rs. 5,000.' }}
                </p>
                @error('manager_pin') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <label class="mb-6 flex cursor-pointer items-center justify-center gap-2 text-sm font-semibold">
                <input type="checkbox" name="as_refund" value="1" @checked(old('as_refund'))
                       class="h-4 w-4 rounded border-slate-300 text-vital-primary focus:ring-vital-primary">
                {{ __('sales.sale_void.treat_as') }} <strong>{{ __('sales.sale_void.refund') }}</strong> {{ __('sales.sale_void.rather_than_void') }}
            </label>

            <div class="flex flex-wrap justify-center gap-3">
                <button type="submit"
                        onclick="return confirm('This reverses stock, meter and cash. Continue?')"
                        class="btn-3d !bg-gradient-to-b !from-rose-500 !to-rose-700 text-white">
                    {{ __('sales.sale_void.confirm_void') }}
                </button>
                <a href="{{ route('sales.show', $sale) }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
