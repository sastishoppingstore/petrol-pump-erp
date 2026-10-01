@extends('layouts.app')

@section('title', 'Salary Slip / تنخواہ سلپ')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('payroll.payroll') }}" class="hover:text-vital-primary">Payroll</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Salary Slip</li>
@endsection

{{--
    Payroll — Salary slip (payroll.salary.show).
    Tamam figures employee_salaries ke stored row se aate hain
    (PayrollController@showSalary employee/branch/bankAccount load
    karke deta hai) — koi recalculation ya dummy number nahi.
--}}
@section('content')
    <div class="page-head">
        <h1>🧾 Salary Slip</h1>
        <p>{{ $salary->employee?->name }} — {{ \Carbon\Carbon::parse($salary->month . '-01')->format('F Y') }}</p>
        <div class="page-actions">
            <a href="{{ route('payroll.payroll') }}" class="btn-3d btn-3d-ghost">← Payroll Sheet</a>
            <button type="button" onclick="window.print()" class="btn-3d btn-3d-navy">🖨 Print Slip</button>
            @if ($salary->status !== 'PAID')
                <button type="button" id="btn-mark-paid" class="btn-3d btn-3d-success">✔ Mark Paid</button>
            @endif
        </div>
    </div>

    <div id="slip-msg" class="mb-5 hidden rounded-2xl px-5 py-3 text-center text-sm font-bold"></div>

    <div class="glass-card mx-auto w-full max-w-3xl p-7">
        {{-- Employee header --}}
        <div class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-200/70 pb-5 dark:border-slate-700/60">
            <div>
                <div class="text-xl font-black text-slate-800 dark:text-white">{{ $salary->employee?->name ?? '—' }}</div>
                <div class="text-sm text-slate-500 dark:text-slate-400">
                    {{ $salary->employee?->designation ?? '' }}
                    @if ($salary->employee?->code) · Code: {{ $salary->employee->code }} @endif
                    @if ($salary->employee?->phone) · {{ $salary->employee->phone }} @endif
                </div>
                <div class="text-xs text-slate-400">{{ $salary->branch?->name }}</div>
            </div>
            <span class="pill-status {{ $salary->status === 'PAID' ? 'pill-active' : 'pill-pending' }}">{{ $salary->status }}</span>
        </div>

        {{-- Attendance days --}}
        <div class="mb-6 grid grid-cols-4 gap-3 text-center">
            <div class="rounded-2xl bg-emerald-500/10 p-3"><div class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ $salary->present_days }}</div><div class="text-[11px] font-bold text-slate-500">Present</div></div>
            <div class="rounded-2xl bg-red-500/10 p-3"><div class="text-lg font-black text-red-600 dark:text-red-400">{{ $salary->absent_days }}</div><div class="text-[11px] font-bold text-slate-500">Absent</div></div>
            <div class="rounded-2xl bg-amber-500/10 p-3"><div class="text-lg font-black text-amber-600 dark:text-amber-400">{{ $salary->half_days }}</div><div class="text-[11px] font-bold text-slate-500">Half Day</div></div>
            <div class="rounded-2xl bg-sky-500/10 p-3"><div class="text-lg font-black text-sky-600 dark:text-sky-400">{{ $salary->leave_days }}</div><div class="text-[11px] font-bold text-slate-500">Leave</div></div>
        </div>

        <div class="grid gap-6 md:grid-cols-2">
            {{-- Earnings --}}
            <div>
                <h3 class="mb-3 text-center text-sm font-black uppercase tracking-wide text-emerald-600 dark:text-emerald-400">Earnings</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Basic Salary</span><span class="tabular font-semibold">Rs. {{ number_format((float) $salary->basic_salary, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Overtime</span><span class="tabular font-semibold">Rs. {{ number_format((float) $salary->overtime_amount, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Bonus</span><span class="tabular font-semibold">Rs. {{ number_format((float) $salary->bonus_amount, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Allowances</span><span class="tabular font-semibold">Rs. {{ number_format((float) $salary->allowances, 2) }}</span></div>
                </div>
            </div>
            {{-- Deductions --}}
            <div>
                <h3 class="mb-3 text-center text-sm font-black uppercase tracking-wide text-red-600 dark:text-red-400">Deductions</h3>
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between"><span class="text-slate-500">Advance Recovery</span><span class="tabular font-semibold">Rs. {{ number_format((float) $salary->advance_deduction, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Fine</span><span class="tabular font-semibold">Rs. {{ number_format((float) $salary->fine_amount, 2) }}</span></div>
                    <div class="flex justify-between"><span class="text-slate-500">Other Deductions</span><span class="tabular font-semibold">Rs. {{ number_format((float) $salary->deductions, 2) }}</span></div>
                </div>
            </div>
        </div>

        {{-- Net --}}
        <div class="mt-7 rounded-3xl bg-gradient-to-r from-rose-500/15 via-amber-500/10 to-blue-500/15 p-5 text-center">
            <div class="text-xs font-black uppercase tracking-widest text-slate-500">Net Salary</div>
            <div class="tabular text-3xl font-black text-slate-800 dark:text-white">Rs. {{ number_format((float) $salary->net_salary, 2) }}</div>
            @if ($salary->status === 'PAID')
                <div class="mt-2 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                    Paid Rs. {{ number_format((float) $salary->paid_amount, 2) }}
                    @if ($salary->payment_date) on {{ $salary->payment_date->format('d M Y') }} @endif
                    · {{ $salary->payment_method }}
                    @if ($salary->bankAccount) · {{ $salary->bankAccount->account_title ?? '' }} @endif
                </div>
            @endif
            @if ($salary->notes)
                <div class="mt-2 text-xs text-slate-400">{{ $salary->notes }}</div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
    const markBtn = document.getElementById('btn-mark-paid');
    if (markBtn) {
        markBtn.addEventListener('click', async () => {
            const msg = document.getElementById('slip-msg');
            try {
                const res = await fetch('{{ route('payroll.salary.mark-paid', $salary) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json', 'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ payment_date: '{{ now()->toDateString() }}' }),
                });
                const data = await res.json();
                msg.textContent = data.message || 'Request failed.';
                msg.className = 'mb-5 rounded-2xl px-5 py-3 text-center text-sm font-bold ' +
                    (res.ok && data.success ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-red-500/15 text-red-700 dark:text-red-300');
                msg.classList.remove('hidden');
                if (res.ok && data.success) setTimeout(() => window.location.reload(), 900);
            } catch (err) {
                msg.textContent = 'Network error — salary not marked paid.';
                msg.className = 'mb-5 rounded-2xl px-5 py-3 text-center text-sm font-bold bg-red-500/15 text-red-700 dark:text-red-300';
                msg.classList.remove('hidden');
            }
        });
    }
</script>
@endpush
