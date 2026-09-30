@extends('layouts.app')

@section('title', 'Monthly Payroll Sheet / ماہانہ تنخواہ شیٹ')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('employees.index') }}">Staff</a></li>
    <li class="text-slate-500">Payroll</li>
@endsection

@section('content')
<div x-data="{ payModal: false, activeSalaryId: null, activeEmpName: '', activeNet: '0.00' }" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Monthly Payroll / تنخواہ شیٹ</h1>
                <span class="rounded bg-red-100 px-2.5 py-0.5 font-mono text-xs font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-300">
                    {{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}
                </span>
            </div>
            <p class="mt-1 text-sm text-slate-500">
                Attendance days, overtime, bonuses, fines, and automatic advance loan recovery.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <form action="{{ route('employees.payroll.generate') }}" method="POST">
                @csrf
                <input type="hidden" name="month" value="{{ $month }}">
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    Generate / Recalculate Sheet
                </button>
            </form>
            <a href="{{ route('employees.attendance') }}" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                Attendance
            </a>
            <button onclick="window.print()" class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                Print Sheet
            </button>
        </div>
    </div>

    {{-- Month selector & Stats --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <label for="month" class="mb-1 block text-xs font-semibold uppercase text-slate-500">Payroll Month</label>
            <form method="GET" action="{{ route('employees.payroll') }}">
                <input type="month" id="month" name="month" value="{{ $month }}" onchange="this.form.submit()"
                       class="w-full rounded-lg border-slate-300 font-mono text-sm font-bold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </form>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-slate-500">Total Net Payable</span>
            <div class="mt-1 font-mono text-xl font-bold text-slate-900 dark:text-white">
                Rs. {{ number_format((float) $totalNet, 2) }}
            </div>
            <div class="mt-0.5 text-xs text-red-600 font-medium">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalNet) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-emerald-600">Total Paid Out</span>
            <div class="mt-1 font-mono text-xl font-bold text-emerald-600">
                Rs. {{ number_format((float) $totalPaid, 2) }}
            </div>
            <div class="mt-0.5 text-xs text-slate-400">Recorded as station expense</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-amber-600">Pending Payout</span>
            <div class="mt-1 font-mono text-xl font-bold text-amber-600">
                Rs. {{ number_format((float) $totalPending, 2) }}
            </div>
            <div class="mt-0.5 text-xs text-slate-400">Awaiting disbursement</div>
        </div>
    </div>

    {{-- Payroll Sheet Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3 text-center">Days (P/A/L/H)</th>
                        <th class="px-4 py-3 text-right">Basic</th>
                        <th class="px-4 py-3 text-right text-emerald-600">+ Overtime/Bonus</th>
                        <th class="px-4 py-3 text-right text-red-600">- Loan Deduction</th>
                        <th class="px-4 py-3 text-right font-bold">Net Salary (خالص رقم)</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($salaries as $sal)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $sal->employee?->name }}</div>
                                <div class="text-xs text-slate-400">{{ $sal->employee?->code }} &bull; {{ $sal->employee?->designation }}</div>
                            </td>
                            <td class="px-4 py-3 text-center text-xs">
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-mono font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                                    {{ $sal->present_days }}P / {{ $sal->absent_days }}A / {{ $sal->leave_days }}L / {{ $sal->half_days }}H
                                </span>
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono text-slate-700 dark:text-slate-300">
                                {{ number_format((float) $sal->basic_salary, 2) }}
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono text-emerald-600">
                                +{{ number_format((float) $sal->allowances, 2) }}
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono text-red-600">
                                -{{ number_format((float) $sal->deductions, 2) }}
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $sal->net_salary, 2) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'rounded px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' => $sal->isPaid(),
                                    'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' => ! $sal->isPaid(),
                                ])>{{ $sal->status }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <div class="flex items-center justify-end gap-1.5">
                                    @if (! $sal->isPaid())
                                        <button type="button" @click="activeSalaryId = {{ $sal->id }}; activeEmpName = '{{ addslashes($sal->employee?->name) }}'; activeNet = '{{ number_format((float) $sal->net_salary, 2) }}'; payModal = true" class="rounded bg-emerald-600 px-3 py-1 font-semibold text-white hover:bg-emerald-700">
                                            Pay Salary
                                        </button>
                                    @endif
                                    <a href="{{ route('employees.payslip', $sal) }}" target="_blank" class="rounded border border-slate-300 px-2.5 py-1 font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                                        Payslip
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                                No salary sheet generated yet for {{ $month }}.
                                <div class="mt-2">
                                    <form action="{{ route('employees.payroll.generate') }}" method="POST" class="inline">
                                        @csrf
                                        <input type="hidden" name="month" value="{{ $month }}">
                                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-xs font-semibold text-white hover:bg-red-700">
                                            Click to Generate Payroll Sheet for {{ $month }}
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
    <div x-show="payModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="payModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Pay Monthly Salary (تنخواہ کی ادائیگی)</h3>
                <p class="mt-1 text-xs text-slate-500">
                    Employee: <strong x-text="activeEmpName"></strong> &bull; Net Amount: <strong class="text-red-600" x-text="'Rs. ' + activeNet"></strong>
                </p>

                <form :action="'/employees/payroll/' + activeSalaryId + '/pay'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Payment Method *</label>
                        <select name="payment_method" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="CASH">Cash (Shift Till)</option>
                            <option value="BANK_TRANSFER">Bank Transfer (Station Account)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Active Shift (if Cash from drawer)</label>
                        <select name="shift_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="">None / Outside shift</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}">{{ $s->shift_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Station Bank Account (if Bank Transfer)</label>
                        <select name="bank_account_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="">None / Cash</option>
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Notes / Remarks</label>
                        <input type="text" name="notes" placeholder="e.g. Salary paid in full" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="payModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Confirm Payment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
