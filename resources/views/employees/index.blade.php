@extends('layouts.app')

@section('title', __('admin.employees.title'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.employees.breadcrumb') }}</li>
@endsection

{{--
    Staff Management — 2026 redesign.
    Page head centered; stats .stat-tile-3d; filter glass-card; table
    .table-3d. Advance / Adjustment modals page ke end par Alpine state se.
    SAKHT NOTE: x-data state, tamam @click handlers, dynamic form actions
    ('/employees/' + selectedEmpId + ...) aur modal field names bilkul
    pehle jaisay hi hain.
--}}
@section('content')
<div x-data="{ advanceModal: false, adjustmentModal: false, selectedEmpId: null, selectedEmpName: '' }" class="space-y-6">
    {{-- Header --}}
    <div class="page-head">
        <h1>👨‍🔧 {{ __('admin.employees.title') }}</h1>
        <p>{{ __('admin.employees.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('employees.attendance') }}" class="btn-3d btn-3d-ghost">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                {{ __('admin.employees.daily_attendance') }}
            </a>
            <a href="{{ route('employees.payroll') }}" class="btn-3d btn-3d-ghost">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                {{ __('admin.employees.monthly_payroll') }}
            </a>
            <a href="{{ route('employees.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.employees.add_employee') }}
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('admin.employees.stat_active_staff') }}</div>
            <div class="stat-value kpi-num">{{ $activeCount }}</div>
            <div class="stat-sub">{{ __('admin.employees.stat_headcount') }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-red">
            <div class="stat-label">{{ __('admin.employees.stat_monthly_payroll') }}</div>
            <div class="stat-value text-2xl">Rs. {{ number_format((float) $totalSalaryBudget, 2) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalSalaryBudget) }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">{{ __('admin.employees.stat_advances') }}</div>
            <div class="stat-value text-2xl">Rs. {{ number_format((float) $totalAdvances, 2) }}</div>
            <div class="stat-sub">{{ __('admin.employees.stat_advances_sub') }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('admin.employees.stat_attendance') }}</div>
            <div class="stat-value text-xl">{{ __('admin.employees.stat_attendance_value') }}</div>
            <div class="stat-sub">{{ __('admin.employees.stat_attendance_sub') }}</div>
        </div>
    </div>

    {{-- Filter Search --}}
    <form method="GET" action="{{ route('employees.index') }}" class="glass-card p-5">
        <div class="grid gap-4 sm:grid-cols-4">
            <div class="field-3d sm:col-span-2">
                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.search_label') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.employees.search_placeholder') }}" class="input-3d text-center text-sm">
            </div>
            <div class="field-3d">
                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.common.status') }}</label>
                <select name="status" class="input-3d text-center text-sm">
                    <option value="">{{ __('admin.employees.all_statuses') }}</option>
                    <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>{{ __('admin.employees.status_active') }}</option>
                    <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>{{ __('admin.employees.status_inactive') }}</option>
                    <option value="TERMINATED" @selected(request('status') === 'TERMINATED')>{{ __('admin.employees.status_terminated') }}</option>
                </select>
            </div>
            <div class="flex items-end justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-navy btn-3d-sm">{{ __('admin.employees.filter_staff') }}</button>
                <a href="{{ route('employees.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('admin.employees.reset') }}</a>
            </div>
        </div>
    </form>

    {{-- Employees Table --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.common.code') }}</th>
                        <th>{{ __('admin.employees.employee_name') }}</th>
                        <th>{{ __('admin.employee_form.designation') }}</th>
                        <th>{{ __('admin.employees.phone_cnic') }}</th>
                        <th>{{ __('admin.employees.basic_salary') }}</th>
                        <th>{{ __('admin.employees.active_loan') }}</th>
                        <th>{{ __('admin.common.status') }}</th>
                        <th>{{ __('admin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($employees as $emp)
                        @php
                            $loanBal = (float) $emp->outstandingAdvances();
                        @endphp
                        <tr>
                            <td class="font-mono text-xs font-bold text-slate-500">{{ $emp->code }}</td>
                            <td>
                                <div class="font-bold text-slate-900 dark:text-white">{{ $emp->name }}</div>
                                <div class="text-xs text-slate-400">{{ __('admin.employees.joined') }}: {{ $emp->joining_date?->format('d M Y') ?? 'N/A' }}</div>
                            </td>
                            <td class="text-xs text-slate-700 dark:text-slate-300">
                                <span class="rounded-full bg-slate-900/5 px-2.5 py-0.5 font-semibold dark:bg-white/10">
                                    {{ $emp->designation }}
                                </span>
                            </td>
                            <td class="text-xs text-slate-600 dark:text-slate-400">
                                <div>{{ $emp->phone ?: '—' }}</div>
                                <div class="font-mono text-[11px]">{{ $emp->cnic ?: '' }}</div>
                            </td>
                            <td class="tabular font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $emp->basic_salary, 2) }}
                            </td>
                            <td class="tabular font-mono text-xs {{ $loanBal > 0 ? 'font-bold text-amber-600' : 'text-slate-400' }}">
                                {{ $loanBal > 0 ? 'Rs. ' . number_format($loanBal, 2) : '—' }}
                            </td>
                            <td>
                                @if ($emp->status === 'ACTIVE')
                                    <span class="pill-status pill-active"><span class="dot" aria-hidden="true"></span>{{ $emp->status }}</span>
                                @elseif ($emp->status === 'TERMINATED')
                                    <span class="pill-status bg-red-500/15 text-red-700 dark:text-red-300"><span class="dot bg-red-500" aria-hidden="true"></span>{{ $emp->status }}</span>
                                @else
                                    <span class="pill-status pill-inactive"><span class="dot" aria-hidden="true"></span>{{ $emp->status }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <div class="flex items-center justify-center gap-1.5">
                                    <button type="button" @click="selectedEmpId = {{ $emp->id }}; selectedEmpName = '{{ addslashes($emp->name) }}'; advanceModal = true" class="btn-3d btn-3d-amber btn-3d-sm">{{ __('admin.employees.advance_btn') }}
                                    </button>
                                    <button type="button" @click="selectedEmpId = {{ $emp->id }}; selectedEmpName = '{{ addslashes($emp->name) }}'; adjustmentModal = true" class="btn-3d btn-3d-sm bg-gradient-to-b from-sky-400 to-sky-600 shadow">{{ __('admin.employees.adjustment_btn') }}
                                    </button>
                                    <a href="{{ route('employees.edit', $emp) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.edit') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-slate-500">
                                {{ __('admin.employees.none') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 px-4 py-3 dark:border-slate-700/60">{{ $employees->links() }}</div>
    </div>

    {{-- Give Advance Modal --}}
    <div x-show="advanceModal" x-cloak x-on:keydown.escape.window="advanceModal = false" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="advanceModal = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
            <div class="modal-bounce glass-card relative w-full max-w-md p-6 text-center">
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('admin.employees.advance_title') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.employees.employee_label') }}: <strong x-text="selectedEmpName"></strong></p>

                <form :action="'/employees/' + selectedEmpId + '/advances'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.advance_amount') }} *</label>
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 10000" class="input-3d text-center font-mono text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.monthly_deduction') }}</label>
                        <input type="number" step="0.01" min="0" name="monthly_deduction" placeholder="e.g. 2500" class="input-3d text-center font-mono text-sm">
                        <span class="mt-1 block text-[11px] text-slate-400">{{ __('admin.employees.monthly_deduction_hint') }}</span>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.payment_method') }} *</label>
                        <select name="payment_method" required class="input-3d text-center text-sm">
                            <option value="CASH">{{ __('admin.employees.cash_till') }}</option>
                            <option value="BANK_TRANSFER">{{ __('admin.employees.bank_transfer') }}</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.bank_account') }}</label>
                        <select name="bank_account_id" class="input-3d text-center text-sm">
                            <option value="">{{ __('admin.employees.none_cash') }}</option>
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.active_shift') }}</label>
                        <select name="shift_id" class="input-3d text-center text-sm">
                            <option value="">{{ __('admin.employees.none_shift') }}</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}">{{ $s->shift_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.reason_details') }}</label>
                        <input type="text" name="reason" placeholder="{{ __('admin.employees.reason_placeholder') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <button type="button" @click="advanceModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-amber btn-3d-sm">{{ __('admin.employees.grant_advance') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Adjustment Modal --}}
    <div x-show="adjustmentModal" x-cloak x-on:keydown.escape.window="adjustmentModal = false" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="adjustmentModal = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
            <div class="modal-bounce glass-card relative w-full max-w-md p-6 text-center">
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('admin.employees.adjustment_title') }}</h3>
                <p class="mt-1 text-xs text-slate-500">{{ __('admin.employees.employee_label') }}: <strong x-text="selectedEmpName"></strong></p>

                <form :action="'/employees/' + selectedEmpId + '/adjustments'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.adjustment_type') }} *</label>
                        <select name="type" required class="input-3d text-center text-sm">
                            <option value="OVERTIME">{{ __('admin.employees.adj_overtime') }}</option>
                            <option value="BONUS">{{ __('admin.employees.adj_bonus') }}</option>
                            <option value="FINE">{{ __('admin.employees.adj_fine') }}</option>
                            <option value="SHORTAGE_RECOVERY">{{ __('admin.employees.adj_shortage') }}</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.amount_rs') }} *</label>
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 1500" class="input-3d text-center font-mono text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.payroll_month') }} *</label>
                        <input type="month" name="payroll_month" value="{{ date('Y-m') }}" required class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.reason_description') }} *</label>
                        <input type="text" name="reason" required placeholder="{{ __('admin.employees.overtime_placeholder') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <button type="button" @click="adjustmentModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-navy btn-3d-sm">{{ __('admin.employees.record_adjustment') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================= Floating Action Button ================= --}}
    <a href="{{ route('employees.create') }}" class="fab-3d" title="{{ __('admin.employees.add_employee') }}">
        <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('admin.employees.add_employee') }}
    </a>
</div>
@endsection
