@extends('layouts.app')

@section('title', 'Staff & Employees / ملازمین')
@section('breadcrumb')
    <li class="text-slate-500">Staff &amp; Payroll</li>
@endsection

@section('content')
<div x-data="{ advanceModal: false, adjustmentModal: false, selectedEmpId: null, selectedEmpName: '' }" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Staff Management / عملہ و ملازمین</h1>
            <p class="mt-1 text-sm text-slate-500">
                Pump attendants, cashiers, security guards, and station supervisors for Mehar Filling Station.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('employees.attendance') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Daily Attendance
            </a>
            <a href="{{ route('employees.payroll') }}" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Monthly Payroll
            </a>
            <a href="{{ route('employees.create') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Employee
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-4">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-slate-500">Active Staff</span>
            <div class="mt-2 text-2xl font-bold text-slate-900 dark:text-white">{{ $activeCount }}</div>
            <div class="mt-0.5 text-xs text-slate-400">Total station headcount</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-slate-500">Monthly Basic Payroll</span>
            <div class="mt-2 font-mono text-xl font-bold text-slate-900 dark:text-white">
                Rs. {{ number_format((float) $totalSalaryBudget, 2) }}
            </div>
            <div class="mt-0.5 text-xs text-red-600 font-medium">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalSalaryBudget) }}</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-amber-600">Active Advances / Loans</span>
            <div class="mt-2 font-mono text-xl font-bold text-amber-600">
                Rs. {{ number_format((float) $totalAdvances, 2) }}
            </div>
            <div class="mt-0.5 text-xs text-slate-400">Recoverable from payroll</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-emerald-600">Attendance Status</span>
            <div class="mt-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                Daily Ledger Active
            </div>
            <div class="mt-1 text-xs text-slate-400">P/A/L/Half tracking enabled</div>
        </div>
    </div>

    {{-- Filter Search --}}
    <form method="GET" action="{{ route('employees.index') }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-3 sm:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Search Name / Phone / CNIC</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. Muhammad Ali, 0300-1234567" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div>
                <label class="mb-1 block text-xs font-semibold uppercase text-slate-500">Status</label>
                <select name="status" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>Active</option>
                    <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Inactive</option>
                    <option value="TERMINATED" @selected(request('status') === 'TERMINATED')>Terminated</option>
                </select>
            </div>
            <div class="flex items-end gap-2 sm:col-span-2">
                <button type="submit" class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-900">Filter Staff</button>
                <a href="{{ route('employees.index') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">Reset</a>
            </div>
        </div>
    </form>

    {{-- Employees Table --}}
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3">Code</th>
                        <th class="px-4 py-3">Employee Name</th>
                        <th class="px-4 py-3">Designation</th>
                        <th class="px-4 py-3">Phone / CNIC</th>
                        <th class="px-4 py-3 text-right">Basic Salary</th>
                        <th class="px-4 py-3 text-right">Active Loan</th>
                        <th class="px-4 py-3 text-center">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse ($employees as $emp)
                        @php
                            $loanBal = (float) $emp->outstandingAdvances();
                        @endphp
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-slate-500">{{ $emp->code }}</td>
                            <td class="px-4 py-3">
                                <div class="font-bold text-slate-900 dark:text-white">{{ $emp->name }}</div>
                                <div class="text-xs text-slate-400">Joined: {{ $emp->joining_date?->format('d M Y') ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-700 dark:text-slate-300">
                                <span class="rounded bg-slate-100 px-2 py-0.5 font-medium dark:bg-slate-800">
                                    {{ $emp->designation }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-600 dark:text-slate-400">
                                <div>{{ $emp->phone ?: '—' }}</div>
                                <div class="font-mono text-[11px]">{{ $emp->cnic ?: '' }}</div>
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono font-bold text-slate-900 dark:text-white">
                                Rs. {{ number_format((float) $emp->basic_salary, 2) }}
                            </td>
                            <td class="tabular px-4 py-3 text-right font-mono text-xs {{ $loanBal > 0 ? 'text-amber-600 font-bold' : 'text-slate-400' }}">
                                {{ $loanBal > 0 ? 'Rs. ' . number_format($loanBal, 2) : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'rounded px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300' => $emp->status === 'ACTIVE',
                                    'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400' => $emp->status === 'INACTIVE',
                                    'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => $emp->status === 'TERMINATED',
                                ])>{{ $emp->status }}</span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-xs">
                                <div class="flex items-center justify-end gap-1.5">
                                    <button type="button" @click="selectedEmpId = {{ $emp->id }}; selectedEmpName = '{{ addslashes($emp->name) }}'; advanceModal = true" class="rounded bg-amber-50 px-2.5 py-1 font-medium text-amber-700 hover:bg-amber-100 dark:bg-amber-900/30 dark:text-amber-300">
                                        + Advance
                                    </button>
                                    <button type="button" @click="selectedEmpId = {{ $emp->id }}; selectedEmpName = '{{ addslashes($emp->name) }}'; adjustmentModal = true" class="rounded bg-sky-50 px-2.5 py-1 font-medium text-sky-700 hover:bg-sky-100 dark:bg-sky-900/30 dark:text-sky-300">
                                        Adjustment
                                    </button>
                                    <a href="{{ route('employees.edit', $emp) }}" class="rounded border border-slate-300 px-2.5 py-1 font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                                No employees registered. Click "Add Employee" to set up your staff.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $employees->links() }}</div>
    </div>

    {{-- Give Advance Modal --}}
    <div x-show="advanceModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="advanceModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Give Advance Loan (قرضہ / پیشگی رقم)</h3>
                <p class="mt-1 text-xs text-slate-500">Employee: <strong x-text="selectedEmpName"></strong></p>

                <form :action="'/employees/' + selectedEmpId + '/advances'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Advance Amount (Rs.) *</label>
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 10000" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Monthly Deduction Cut (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="monthly_deduction" placeholder="e.g. 2500" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                        <span class="text-[11px] text-slate-400">Amount auto-deducted from each monthly salary.</span>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Payment Method *</label>
                        <select name="payment_method" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="CASH">Cash (Paid from Till)</option>
                            <option value="BANK_TRANSFER">Bank Transfer</option>
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
                        <label class="block text-xs font-semibold uppercase text-slate-500">Active Shift (if Cash Out from Drawer)</label>
                        <select name="shift_id" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="">None / Outside shift</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}">{{ $s->shift_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Reason / Details</label>
                        <input type="text" name="reason" placeholder="e.g. Medical emergency / Family advance" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="advanceModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">Grant Advance</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Adjustment Modal --}}
    <div x-show="adjustmentModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="adjustmentModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Record Adjustment (اوور ٹائم / جرمانہ / بونس)</h3>
                <p class="mt-1 text-xs text-slate-500">Employee: <strong x-text="selectedEmpName"></strong></p>

                <form :action="'/employees/' + selectedEmpId + '/adjustments'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Adjustment Type *</label>
                        <select name="type" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="OVERTIME">Overtime (اوور ٹائم - Add)</option>
                            <option value="BONUS">Bonus (انعام - Add)</option>
                            <option value="FINE">Fine / Penalty (جرمانہ - Deduct)</option>
                            <option value="SHORTAGE_RECOVERY">Cash Shortage Recovery (کیش کی کمی - Deduct)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Amount (Rs.) *</label>
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 1500" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Payroll Month (YYYY-MM) *</label>
                        <input type="month" name="payroll_month" value="{{ date('Y-m') }}" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Reason / Description *</label>
                        <input type="text" name="reason" required placeholder="e.g. 12 hours overtime on Eid night" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="adjustmentModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-900">Record Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
