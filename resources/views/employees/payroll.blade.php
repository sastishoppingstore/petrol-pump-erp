@extends('layouts.app')

@section('title', __('admin.emp_payroll.title'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('employees.index') }}" class="hover:text-vital-primary">{{ __('admin.employees.staff') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.emp_payroll.breadcrumb') }}</li>
@endsection

{{--
    Monthly Payroll — 2026 redesign.
    Page head centered; stats .stat-tile-3d; sheet .table-3d. Pay Salary
    modal page ke end par Alpine state se. Tamam routes (generate / pay /
    payslip), hidden month fields aur window.print() hook pehle jaisay hi.
--}}
@section('content')
<div x-data="{ payModal: false, activeSalaryId: null, activeEmpName: '', activeNet: '0.00' }" class="space-y-6">
    {{-- Header --}}
    <div class="page-head">
        <h1>💰 {{ __('admin.emp_payroll.title') }}
            <span class="ml-1 align-middle rounded-full bg-vital-primary/15 px-3 py-1 font-mono text-xs font-black text-vital-darkred dark:text-red-300">
                {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}
            </span>
        </h1>
        <p>{{ __('admin.emp_payroll.subtitle') }}</p>
        <div class="page-actions">
            <form action="{{ route('employees.payroll.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit" class="btn-3d btn-3d-primary">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    {{ __('admin.emp_payroll.generate') }}
                </button>
            </form>
            <a href="{{ route('employees.attendance') }}" class="btn-3d btn-3d-ghost">{{ __('admin.emp_attendance.breadcrumb') }}
            </a>
            <button onclick="window.print()" class="btn-3d btn-3d-ghost">{{ __('admin.emp_payroll.print_sheet') }}
            </button>
        </div>
    </div>

    {{-- Month selector & Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="glass-card p-4">
            <div class="field-3d">
                <label for="month" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.emp_payroll.payroll_month') }}</label>
                <form method="GET" action="{{ route('employees.payroll') }}">
                    <input type="month" id="month" name="month" value="{{ $month }}" onchange="this.form.submit()"
                           class="input-3d text-center font-mono text-sm font-bold">
                </form>
            </div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">{{ __('admin.emp_payroll.total_net') }}</div>
            <div class="stat-value text-2xl">Rs. {{ number_format((float) $totalNet, 2) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalNet) }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">{{ __('admin.emp_payroll.total_paid') }}</div>
            <div class="stat-value text-2xl">Rs. {{ number_format((float) $totalPaid, 2) }}</div>
            <div class="stat-sub">{{ __('admin.emp_payroll.total_paid_sub') }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">{{ __('admin.emp_payroll.pending_payout') }}</div>
            <div class="stat-value text-2xl">Rs. {{ number_format((float) $totalPending, 2) }}</div>
            <div class="stat-sub">{{ __('admin.emp_payroll.pending_sub') }}</div>
        </div>
    </div>

    {{-- Payroll Sheet Table --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.common.employee') }}</th>
                        <th>{{ __('admin.emp_payroll.days') }}</th>
                        <th>{{ __('admin.emp_payroll.basic') }}</th>
                        <th class="text-emerald-600">{{ __('admin.emp_payroll.overtime_bonus') }}</th>
                        <th class="text-red-500">{{ __('admin.emp_payroll.loan_deduction') }}</th>
                        <th>{{ __('admin.emp_payroll.net_salary') }}</th>
                        <th>{{ __('admin.common.status') }}</th>
                        <th>{{ __('admin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salaries as $sal)
                        <tr>
                            <td>
                                <div class="font-bold text-slate-900 dark:text-white">{{ $sal->employee?->name }}</div>
                                <div class="text-xs text-slate-400">{{ $sal->employee?->code }} &bull; {{ $sal->employee?->designation }}</div>
                            </td>
                            <td class="text-xs">
                                <span class="rounded-full bg-slate-900/5 px-2.5 py-0.5 font-mono font-bold text-slate-700 dark:bg-white/10 dark:text-slate-300">
                                    {{ $sal->present_days }}P / {{ $sal->absent_days }}A / {{ $sal->leave_days }}L / {{ $sal->half_days }}H
                                </span>
                            </td>
                            <td class="tabular font-mono text-slate-700 dark:text-slate-300">
                                {{ number_format((float) $sal->basic_salary, 2) }}
                            </td>
                            <td class="tabular font-mono text-emerald-600">
                                +{{ number_format((float) $sal->allowances, 2) }}
                            </td>
                            <td class="tabular font-mono text-red-500">
                                -{{ number_format((float) $sal->deductions, 2) }}
                            </td>
                            <td class="tabular font-mono font-black text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $sal->net_salary, 2) }}
                            </td>
                            <td>
                                @if ($sal->isPaid())
                                    <span class="pill-status pill-active"><span class="dot" aria-hidden="true"></span>{{ $sal->status }}</span>
                                @else
                                    <span class="pill-status bg-amber-500/15 text-amber-700 dark:text-amber-300"><span class="dot bg-amber-500" aria-hidden="true"></span>{{ $sal->status }}</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <div class="flex items-center justify-center gap-1.5">
                                    @if (! $sal->isPaid())
                                        <button type="button" @click="activeSalaryId = {{ $sal->id }}; activeEmpName = '{{ addslashes($sal->employee?->name) }}'; activeNet = '{{ number_format((float) $sal->net_salary, 2) }}'; payModal = true" class="btn-3d btn-3d-success btn-3d-sm">{{ __('admin.emp_payroll.pay_salary') }}
                                        </button>
                                    @endif
                                    <a href="{{ route('employees.payslip', $sal) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('admin.emp_payroll.payslip') }}
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-slate-500">
                                {{ __('admin.emp_payroll.none', ['month' => $month]) }}
                                <div class="mt-3">
                                    <form action="{{ route('employees.payroll.generate') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $month }}">
                                        <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">{{ __('admin.emp_payroll.generate_for', ['month' => $month]) }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pay Salary Modal --}}
    <div x-show="payModal" x-cloak x-on:keydown.escape.window="payModal = false" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="payModal = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
            <div class="modal-bounce glass-card relative w-full max-w-md p-6 text-center">
                <h3 class="text-base font-black text-slate-900 dark:text-white">{{ __('admin.emp_payroll.pay_title') }}</h3>
                <p class="mt-1 text-xs text-slate-500">
                    {{ __('admin.employees.employee_label') }}: <strong x-text="activeEmpName"></strong> &bull; {{ __('admin.emp_payroll.net_amount') }}: <strong class="text-vital-primary" x-text="'Rs. ' + activeNet"></strong>
                </p>

                <form :action="'/employees/payroll/' + activeSalaryId + '/pay'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employees.payment_method') }} *</label>
                        <select name="payment_method" required class="input-3d text-center text-sm">
                            <option value="CASH">{{ __('admin.emp_payroll.cash_shift') }}</option>
                            <option value="BANK_TRANSFER">{{ __('admin.emp_payroll.bank_transfer_station') }}</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.emp_payroll.active_shift_cash') }}</label>
                        <select name="shift_id" class="input-3d text-center text-sm">
                            <option value="">{{ __('admin.employees.none_shift') }}</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}">{{ $s->shift_number }}</option>
                            @endforeach
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
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.emp_payroll.notes_remarks') }}</label>
                        <input type="text" name="notes" placeholder="{{ __('admin.emp_payroll.notes_placeholder') }}" class="input-3d text-center text-sm">
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <button type="button" @click="payModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.cancel') }}</button>
                        <button type="submit" class="btn-3d btn-3d-success btn-3d-sm">{{ __('admin.emp_payroll.confirm_payment') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
