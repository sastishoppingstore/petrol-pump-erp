@extends('layouts.app')

@section('title', 'Staff & Employees / ملازمین')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Staff &amp; Payroll</li>
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
        <h1>👨‍🔧 Staff Management / عملہ و ملازمین</h1>
        <p>Pump attendants, cashiers, security guards, and station supervisors for Mehar Filling Station.</p>
        <div class="page-actions">
            <a href="{{ route('employees.attendance') }}" class="btn-3d btn-3d-ghost">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                Daily Attendance
            </a>
            <a href="{{ route('employees.payroll') }}" class="btn-3d btn-3d-ghost">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                Monthly Payroll
            </a>
            <a href="{{ route('employees.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Employee
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Active Staff</div>
            <div class="stat-value">{{ $activeCount }}</div>
            <div class="stat-sub">Total station headcount</div>
        </div>
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Monthly Basic Payroll</div>
            <div class="stat-value text-2xl">Rs. {{ number_format((float) $totalSalaryBudget, 2) }}</div>
            <div class="stat-sub">{{ \App\Support\PakistaniCurrency::toUrduWords((string) $totalSalaryBudget) }}</div>
        </div>
        <div class="stat-tile-3d stat-amber">
            <div class="stat-label">Active Advances / Loans</div>
            <div class="stat-value text-2xl">Rs. {{ number_format((float) $totalAdvances, 2) }}</div>
            <div class="stat-sub">Recoverable from payroll</div>
        </div>
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">Attendance Status</div>
            <div class="stat-value text-xl">Daily Ledger Active</div>
            <div class="stat-sub">P/A/L/Half tracking enabled</div>
        </div>
    </div>

    {{-- Filter Search --}}
    <form method="GET" action="{{ route('employees.index') }}" class="glass-card p-5">
        <div class="grid gap-4 sm:grid-cols-4">
            <div class="field-3d sm:col-span-2">
                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Search Name / Phone / CNIC</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="e.g. Muhammad Ali, 0300-1234567" class="input-3d text-center text-sm">
            </div>
            <div class="field-3d">
                <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Status</label>
                <select name="status" class="input-3d text-center text-sm">
                    <option value="">All Statuses</option>
                    <option value="ACTIVE" @selected(request('status') === 'ACTIVE')>Active</option>
                    <option value="INACTIVE" @selected(request('status') === 'INACTIVE')>Inactive</option>
                    <option value="TERMINATED" @selected(request('status') === 'TERMINATED')>Terminated</option>
                </select>
            </div>
            <div class="flex items-end justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-navy btn-3d-sm">Filter Staff</button>
                <a href="{{ route('employees.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">Reset</a>
            </div>
        </div>
    </form>

    {{-- Employees Table --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Employee Name</th>
                        <th>Designation</th>
                        <th>Phone / CNIC</th>
                        <th>Basic Salary</th>
                        <th>Active Loan</th>
                        <th>Status</th>
                        <th>Actions</th>
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
                                <div class="text-xs text-slate-400">Joined: {{ $emp->joining_date?->format('d M Y') ?? 'N/A' }}</div>
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
                                    <button type="button" @click="selectedEmpId = {{ $emp->id }}; selectedEmpName = '{{ addslashes($emp->name) }}'; advanceModal = true" class="btn-3d btn-3d-amber btn-3d-sm">
                                        + Advance
                                    </button>
                                    <button type="button" @click="selectedEmpId = {{ $emp->id }}; selectedEmpName = '{{ addslashes($emp->name) }}'; adjustmentModal = true" class="btn-3d btn-3d-sm bg-gradient-to-b from-sky-400 to-sky-600 shadow">
                                        Adjustment
                                    </button>
                                    <a href="{{ route('employees.edit', $emp) }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-slate-500">
                                No employees registered. Click "Add Employee" to set up your staff.
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
                <h3 class="text-base font-black text-slate-900 dark:text-white">Give Advance Loan (قرضہ / پیشگی رقم)</h3>
                <p class="mt-1 text-xs text-slate-500">Employee: <strong x-text="selectedEmpName"></strong></p>

                <form :action="'/employees/' + selectedEmpId + '/advances'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Advance Amount (Rs.) *</label>
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 10000" class="input-3d text-center font-mono text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Monthly Deduction Cut (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="monthly_deduction" placeholder="e.g. 2500" class="input-3d text-center font-mono text-sm">
                        <span class="mt-1 block text-[11px] text-slate-400">Amount auto-deducted from each monthly salary.</span>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Payment Method *</label>
                        <select name="payment_method" required class="input-3d text-center text-sm">
                            <option value="CASH">Cash (Paid from Till)</option>
                            <option value="BANK_TRANSFER">Bank Transfer</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Station Bank Account (if Bank Transfer)</label>
                        <select name="bank_account_id" class="input-3d text-center text-sm">
                            <option value="">None / Cash</option>
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Active Shift (if Cash Out from Drawer)</label>
                        <select name="shift_id" class="input-3d text-center text-sm">
                            <option value="">None / Outside shift</option>
                            @foreach ($shifts as $s)
                                <option value="{{ $s->id }}">{{ $s->shift_number }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Reason / Details</label>
                        <input type="text" name="reason" placeholder="e.g. Medical emergency / Family advance" class="input-3d text-center text-sm">
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <button type="button" @click="advanceModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-amber btn-3d-sm">Grant Advance</button>
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
                <h3 class="text-base font-black text-slate-900 dark:text-white">Record Adjustment (اوور ٹائم / جرمانہ / بونس)</h3>
                <p class="mt-1 text-xs text-slate-500">Employee: <strong x-text="selectedEmpName"></strong></p>

                <form :action="'/employees/' + selectedEmpId + '/adjustments'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Adjustment Type *</label>
                        <select name="type" required class="input-3d text-center text-sm">
                            <option value="OVERTIME">Overtime (اوور ٹائم - Add)</option>
                            <option value="BONUS">Bonus (انعام - Add)</option>
                            <option value="FINE">Fine / Penalty (جرمانہ - Deduct)</option>
                            <option value="SHORTAGE_RECOVERY">Cash Shortage Recovery (کیش کی کمی - Deduct)</option>
                        </select>
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Amount (Rs.) *</label>
                        <input type="number" step="0.01" min="1" name="amount" required placeholder="e.g. 1500" class="input-3d text-center font-mono text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Payroll Month (YYYY-MM) *</label>
                        <input type="month" name="payroll_month" value="{{ date('Y-m') }}" required class="input-3d text-center text-sm">
                    </div>
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Reason / Description *</label>
                        <input type="text" name="reason" required placeholder="e.g. 12 hours overtime on Eid night" class="input-3d text-center text-sm">
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <button type="button" @click="adjustmentModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-navy btn-3d-sm">Record Adjustment</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ================= Floating Action Button ================= --}}
    <a href="{{ route('employees.create') }}" class="fab-3d" title="Add a new employee">
        <span class="text-xl leading-none" aria-hidden="true">＋</span> Add Employee
    </a>
</div>
@endsection
