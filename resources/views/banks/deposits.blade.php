@extends('layouts.app')

@section('title', __('finance.bank_deposits.title'))
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">{{ __('finance.common.banks_word') }}</a></li>
    <li class="text-slate-500">{{ __('finance.bank_deposits.deposits_crumb') }}</li>
@endsection

@section('content')
<div x-data="{ openModal: false }" class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>{{ __('finance.bank_deposits.heading') }}</h1>
        <p>{{ __('finance.bank_deposits.subheading') }}</p>
        <div class="page-actions">
            @can('cash.create')
                <button @click="openModal = true" class="btn-3d btn-3d-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    {{ __('finance.bank_deposits.deposit_cash_btn') }}
                </button>
            @endcan
            <a href="{{ route('banks.index') }}" class="btn-3d btn-3d-ghost">
                {{ __('finance.bank_deposits.all_accounts') }}
            </a>
        </div>
    </div>

    {{-- Filter Bar --}}
    <form method="GET" action="{{ route('bank-deposits.index') }}" class="glass-card p-4">
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="field-3d">
                <label for="bank_account_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.bank_deposits.bank_account') }}</label>
                <select id="bank_account_id" name="bank_account_id" class="input-3d w-full text-center text-sm">
                    <option value="">{{ __('finance.bank_deposits.all_bank_accounts') }}</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) request('bank_account_id') === (string) $account->id)>
                            {{ $account->bank?->short_name ?? $account->bank?->name }} — {{ $account->account_title }} ({{ '••••' . substr((string) $account->account_number, -4) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="field-3d">
                <label for="from" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.common.from_date') }}</label>
                <input type="date" id="from" name="from" value="{{ request('from') }}"
                       class="input-3d w-full text-center text-sm">
            </div>
            <div class="field-3d">
                <label for="to" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('finance.common.to_date') }}</label>
                <input type="date" id="to" name="to" value="{{ request('to') }}"
                       class="input-3d w-full text-center text-sm">
            </div>
            <div class="flex items-end justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-navy w-full">{{ __('ui.actions.filter') }}</button>
                <a href="{{ route('bank-deposits.index') }}" class="btn-3d btn-3d-ghost">{{ __('finance.common.reset') }}</a>
            </div>
        </div>
    </form>

    {{-- Deposit History Table --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('finance.bank_deposits.date_time') }}</th>
                        <th>{{ __('finance.bank_deposits.bank_and_account') }}</th>
                        <th>{{ __('finance.bank_deposits.deposit_ref') }}</th>
                        <th>{{ __('finance.bank_deposits.amount_col') }}</th>
                        <th>{{ __('finance.bank_deposits.balance_after') }}</th>
                        <th>{{ __('finance.bank_deposits.shift') }}</th>
                        <th>{{ __('finance.bank_deposits.depositor') }}</th>
                        <th>{{ __('finance.bank_deposits.slip_proof') }}</th>
                        <th>{{ __('finance.common.status') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deposits as $d)
                        <tr>
                            <td class="whitespace-nowrap text-xs text-slate-600 dark:text-slate-300">
                                {{ $d->deposited_at?->format('d M Y, h:i A') }}
                            </td>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $d->bank_name }}</div>
                                <div class="text-xs text-slate-400">{{ $d->bankAccount?->account_title }}</div>
                            </td>
                            <td class="font-mono text-xs text-slate-600 dark:text-slate-300">
                                {{ $d->reference_number }}
                            </td>
                            <td class="tabular font-mono font-bold text-emerald-600">
                                Rs. {{ number_format((float) $d->amount, 2) }}
                            </td>
                            <td class="tabular font-mono text-xs text-slate-500">
                                Rs. {{ number_format((float) $d->balance_after, 2) }}
                            </td>
                            <td class="text-xs">
                                @if ($d->shift)
                                    <span class="rounded bg-slate-100 px-2 py-0.5 font-medium text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                        {{ $d->shift->shift_number }}
                                    </span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-xs text-slate-600 dark:text-slate-400">
                                {{ $d->depositor?->name ?? '—' }}
                            </td>
                            <td>
                                @if ($d->slip_path)
                                    <a href="{{ asset('storage/' . $d->slip_path) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm">
                                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                        {{ __('finance.bank_deposits.view_slip') }}
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400">{{ __('finance.bank_deposits.no_slip') }}</span>
                                @endif
                            </td>
                            <td>
                                <span class="pill-status {{ $d->status === 'COMPLETED' ? 'pill-active' : 'pill-inactive' }}"><span class="dot"></span>{{ $d->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12 text-center text-slate-500">
                                {{ __('finance.bank_deposits.empty') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $deposits->links() }}</div>
    </div>

    @can('cash.create')
        <button @click="openModal = true" class="fab-3d" title="{{ __('finance.bank_deposits.fab_title') }}">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
        </button>
    @endcan

    {{-- Modal: Record Cash Deposit --}}
    <div x-show="openModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="openModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity"></div>

            <div class="glass-card modal-bounce relative w-full max-w-lg p-6">
                <div class="border-b border-slate-200 pb-3 text-center dark:border-slate-700">
                    <h2 class="text-lg font-bold text-slate-900 dark:text-white">{{ __('finance.bank_deposits.modal_heading') }}</h2>
                    <button @click="openModal = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('bank-deposits.store') }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label for="modal_bank_account_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.bank_deposits.destination_account') }} *</label>
                        <select id="modal_bank_account_id" name="bank_account_id" required class="input-3d w-full text-center text-sm">
                            <option value="">{{ __('finance.bank_deposits.select_bank_account') }}</option>
                            @foreach ($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->bank?->name }} — {{ $acc->account_title }} ({{ __('finance.banks.bal') }}: Rs. {{ number_format((float) ($balances[$acc->id] ?? $acc->opening_balance), 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-3d">
                        <label for="modal_amount" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.banks.amount_rs') }} *</label>
                        <input type="number" step="0.01" min="1" id="modal_amount" name="amount" required placeholder="{{ __('finance.bank_deposits.ph_250000') }}"
                               class="input-3d tabular w-full text-center font-mono text-base font-bold text-slate-900 dark:text-white">
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div class="field-3d">
                            <label for="modal_reference" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.bank_deposits.slip_ref') }}</label>
                            <input type="text" id="modal_reference" name="reference" placeholder="{{ __('finance.bank_deposits.ph_slip_ref') }}"
                                   class="input-3d w-full text-center text-sm">
                        </div>
                        <div class="field-3d">
                            <label for="modal_shift_id" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.bank_deposits.source_shift') }}</label>
                            <select id="modal_shift_id" name="shift_id" class="input-3d w-full text-center text-sm">
                                <option value="">{{ __('finance.bank_deposits.outside_shift') }}</option>
                                @foreach ($shifts as $s)
                                    <option value="{{ $s->id }}">{{ $s->shift_number }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="field-3d">
                        <label for="modal_slip" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.bank_deposits.slip_photo') }}</label>
                        <input type="file" id="modal_slip" name="slip" accept="image/*,.pdf"
                               class="input-3d w-full text-xs text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-red-50 file:px-3 file:py-2 file:text-xs file:font-semibold file:text-vital-primary hover:file:bg-red-100 dark:file:bg-slate-800 dark:file:text-red-300">
                        <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('finance.bank_deposits.slip_hint') }}</span>
                    </div>

                    <div class="field-3d">
                        <label for="modal_notes" class="mb-1 block text-center text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">{{ __('finance.common.notes') }}</label>
                        <textarea id="modal_notes" name="notes" rows="2" placeholder="{{ __('finance.bank_deposits.ph_notes') }}"
                                  class="input-3d w-full text-center text-sm"></textarea>
                    </div>

                    <div class="mt-6 flex justify-center gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <button type="button" @click="openModal = false" class="btn-3d btn-3d-ghost">
                            {{ __('ui.actions.cancel') }}
                        </button>
                        <button type="submit" class="btn-3d btn-3d-primary">
                            {{ __('finance.bank_deposits.save_deposit') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
