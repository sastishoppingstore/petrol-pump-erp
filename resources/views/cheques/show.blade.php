@extends('layouts.app')

@section('title', __('finance.cheques.cheque_word') . ' #' . $cheque->cheque_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('cheques.index') }}">{{ __('finance.cheques.cheques_word') }}</a></li>
    <li class="text-slate-500">#{{ $cheque->cheque_number }}</li>
@endsection

@section('content')
<div x-data="{ depositModal: false, bounceModal: false, cancelModal: false }" class="mx-auto max-w-4xl space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>{{ __('finance.cheques.cheque_word') }} #{{ $cheque->cheque_number }}
            <span @class([
                'ml-2 align-middle rounded-full px-3 py-0.5 text-xs font-semibold',
                'bg-amber-100 text-amber-800' => $cheque->status === 'RECEIVED',
                'bg-sky-100 text-sky-800' => $cheque->status === 'DEPOSITED',
                'bg-emerald-100 text-emerald-800' => $cheque->status === 'CLEARED',
                'bg-red-100 text-red-800' => $cheque->status === 'BOUNCED',
                'bg-slate-100 text-slate-600' => $cheque->status === 'CANCELLED',
            ])>{{ $cheque->status }}</span>
            @if ($cheque->isPdc())
                <span class="ml-1 align-middle rounded bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">{{ __('finance.cheques.pdc_full') }}</span>
            @endif
        </h1>
        <p>{{ $cheque->type === 'RECEIVED' ? __('finance.cheques.received_from_customer') : __('finance.cheques.issued_to_supplier') }} &bull; {{ $cheque->partyName() }}</p>
        <div class="page-actions">
            @if ($cheque->type === 'RECEIVED' && $cheque->status === 'RECEIVED')
                <button @click="depositModal = true" class="btn-3d btn-3d-navy">
                    {{ __('finance.cheques.deposit_into_bank') }}
                </button>
            @endif

            @if (in_array($cheque->status, ['RECEIVED', 'DEPOSITED']))
                <form action="{{ route('cheques.clear', $cheque) }}" method="POST" onsubmit="return confirm('Clear cheque #{{ $cheque->cheque_number }} and credit bank balance?');" class="inline">
                    @csrf
                    <button type="submit" class="btn-3d btn-3d-success">
                        {{ __('finance.cheques.mark_cleared') }}
                    </button>
                </form>
            @endif

            @if (! in_array($cheque->status, ['BOUNCED', 'CANCELLED']))
                <button @click="bounceModal = true" class="btn-3d btn-3d-primary">
                    {{ __('finance.cheques.bounce_cheque') }}
                </button>
            @endif

            @if (! in_array($cheque->status, ['CLEARED', 'BOUNCED', 'CANCELLED']))
                <button @click="cancelModal = true" class="btn-3d btn-3d-ghost">
                    {{ __('ui.actions.cancel') }}
                </button>
            @endif

            <button onclick="window.print()" class="btn-3d btn-3d-ghost">
                {{ __('ui.actions.print') }}
            </button>
        </div>
    </div>

    {{-- Cheque Details Card --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="glass-card card-3d p-6">
                <h2 class="mb-4 text-center text-base font-bold text-slate-900 dark:text-white">{{ __('finance.cheques.specifications') }}</h2>
                <div class="grid gap-4 text-center sm:grid-cols-2">
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('finance.cheques.party_label') }}</span>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $cheque->partyName() }}</div>
                        <div class="text-xs text-slate-500">{{ $cheque->type === 'RECEIVED' ? __('finance.cheques.customer_account') : __('finance.cheques.supplier_account') }}</div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('finance.cheques.drawee_bank') }}</span>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $cheque->bank_name }}</div>
                        @if ($cheque->bankAccount)
                            <div class="text-xs text-slate-500">{{ __('finance.cheques.station_account') }}: {{ $cheque->bankAccount->account_title }}</div>
                        @endif
                    </div>

                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('finance.cheques.cheque_amount_plain') }}</span>
                        <div class="tabular mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">
                            Rs. {{ number_format((float) $cheque->amount, 2) }}
                        </div>
                        <div class="text-xs font-medium text-vital-primary">
                            {{ \App\Support\PakistaniCurrency::toUrduWords((string) $cheque->amount) }}
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('finance.cheques.payee_on_face') }}</span>
                        <div class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ $cheque->payee_name ?: '—' }}</div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 border-t border-slate-100 pt-4 text-center sm:grid-cols-3 dark:border-slate-800">
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('finance.cheques.date_on_cheque') }}</span>
                        <div class="mt-1 text-sm font-medium">{{ $cheque->cheque_date?->format('d M Y') }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('finance.cheques.due_maturity_short') }}</span>
                        <div class="mt-1 text-sm font-medium {{ $cheque->due_date?->isFuture() ? 'text-sky-600 font-bold' : '' }}">
                            {{ $cheque->due_date?->format('d M Y') }}
                        </div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('finance.cheques.recorded_by') }}</span>
                        <div class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $cheque->creator?->name ?? __('finance.cash.system') }}</div>
                    </div>
                </div>

                @if ($cheque->notes)
                    <div class="mt-6 border-t border-slate-100 pt-4 text-center dark:border-slate-800">
                        <span class="text-xs font-semibold uppercase text-slate-400">{{ __('finance.common.notes') }}</span>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $cheque->notes }}</p>
                    </div>
                @endif
            </div>

            {{-- Bounce / Settlement Details if applicable --}}
            @if ($cheque->status === 'BOUNCED')
                <div class="glass-card card-3d p-6 text-center">
                    <h3 class="text-base font-bold text-vital-primary dark:text-red-400">{{ __('finance.cheques.bounce_record') }}</h3>
                    <div class="mt-3 grid gap-4 text-center text-sm sm:grid-cols-3">
                        <div>
                            <span class="text-xs font-semibold uppercase text-vital-primary">{{ __('finance.cheques.bounce_date') }}</span>
                            <div class="font-bold text-red-900 dark:text-red-200">{{ $cheque->bounced_date?->format('d M Y') }}</div>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase text-vital-primary">{{ __('finance.cheques.reason_word') }}</span>
                            <div class="font-bold text-red-900 dark:text-red-200">{{ $cheque->bounce_reason }}</div>
                        </div>
                        <div>
                            <span class="text-xs font-semibold uppercase text-vital-primary">{{ __('finance.cheques.penalty_charged') }}</span>
                            <div class="tabular font-mono font-bold text-red-900 dark:text-red-200">Rs. {{ number_format((float) $cheque->bank_charges, 2) }}</div>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-vital-primary">
                        {{ __('finance.cheques.bounce_note') }}
                    </p>
                </div>
            @endif

            @if ($cheque->status === 'CLEARED')
                <div class="glass-card card-3d p-6 text-center">
                    <h3 class="text-base font-bold text-emerald-700 dark:text-emerald-400">{{ __('finance.cheques.cleared_heading') }}</h3>
                    <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                        {{ __('finance.cheques.cleared_on') }} {{ $cheque->cleared_date?->format('d M Y') }}. {{ __('finance.cheques.cleared_suffix') }}
                    </p>
                </div>
            @endif
        </div>

        {{-- Cheque Image Preview --}}
        <div class="lg:col-span-1">
            <div class="glass-card card-3d p-5">
                <h3 class="mb-3 text-center text-sm font-bold text-slate-900 dark:text-white">{{ __('finance.cheques.scan_photo') }}</h3>
                @if ($cheque->image_path)
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <img src="{{ asset('storage/' . $cheque->image_path) }}" alt="{{ __('finance.cheques.cheque_word') }} #{{ $cheque->cheque_number }}" class="w-full object-cover">
                    </div>
                    <div class="mt-3">
                        <a href="{{ asset('storage/' . $cheque->image_path) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm block w-full text-center">
                            {{ __('finance.cheques.open_full') }}
                        </a>
                    </div>
                @else
                    <div class="flex h-48 flex-col items-center justify-center rounded-xl border border-dashed border-slate-300 text-slate-400 dark:border-slate-700">
                        <svg class="mb-2 h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs">{{ __('finance.cheques.no_image') }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Deposit Modal --}}
    <div x-show="depositModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="depositModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-md p-6">
                <h3 class="text-center text-base font-bold text-slate-900 dark:text-white">{{ __('finance.cheques.deposit_bank_heading') }}</h3>
                <form action="{{ route('cheques.deposit', $cheque) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.destination_station') }} *</label>
                        <select name="bank_account_id" required class="input-3d w-full text-center text-sm">
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.deposit_date') }}</label>
                        <input type="date" name="deposit_date" value="{{ date('Y-m-d') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="depositModal = false" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-navy">{{ __('finance.cheques.confirm_deposit') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bounce Modal --}}
    <div x-show="bounceModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="bounceModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-md p-6">
                <h3 class="text-center text-base font-bold text-vital-primary">{{ __('finance.cheques.bounce_heading') }}</h3>
                <form action="{{ route('cheques.bounce', $cheque) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <p class="text-center text-xs text-slate-500">
                        {{ __('finance.cheques.bounce_will') }} <strong>{{ __('finance.cheques.bounce_reverse', ['amount' => number_format((float) $cheque->amount, 2), 'party' => $cheque->partyName()]) }}</strong>{{ __('finance.cheques.bounce_tail') }}
                    </p>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.bounce_reason') }} *</label>
                        <select name="bounce_reason" required class="input-3d w-full text-center text-sm">
                            <option value="Insufficient Funds / فنڈز ناکافی">Insufficient Funds / فنڈز ناکافی</option>
                            <option value="Signature Mismatch / دستخط کا فرق">Signature Mismatch / دستخط کا فرق</option>
                            <option value="Payment Stopped by Drawer / ادائیگی روک دی گئی">Payment Stopped by Drawer / ادائیگی روک دی گئی</option>
                            <option value="Account Closed / اکاؤنٹ بند ہے">Account Closed / اکاؤنٹ بند ہے</option>
                            <option value="Post-Dated Cheque / پیشگی تاریخ">Post-Dated Cheque / پیشگی تاریخ</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.penalty_charges') }}</label>
                        <input type="number" step="0.01" min="0" name="bank_charges" value="500.00" class="input-3d tabular w-full text-center font-mono text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.bounce_date') }}</label>
                        <input type="date" name="bounced_date" value="{{ date('Y-m-d') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="bounceModal = false" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-primary">{{ __('finance.cheques.confirm_bounce') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel Modal --}}
    <div x-show="cancelModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="cancelModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="glass-card modal-bounce relative w-full max-w-md p-6">
                <h3 class="text-center text-base font-bold text-slate-900 dark:text-white">{{ __('finance.cheques.cancel_heading') }}</h3>
                <form action="{{ route('cheques.cancel', $cheque) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.cheques.cancellation_reason') }} *</label>
                        <input type="text" name="reason" required placeholder="{{ __('finance.cheques.ph_cancel_reason') }}" class="input-3d w-full text-center text-sm">
                    </div>
                    <div class="flex justify-center gap-2 pt-2">
                        <button type="button" @click="cancelModal = false" class="btn-3d btn-3d-ghost">{{ __('ui.actions.close') }}</button>
                        <button type="submit" class="btn-3d btn-3d-navy">{{ __('finance.cheques.confirm_cancellation') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
