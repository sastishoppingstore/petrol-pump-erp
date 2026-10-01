@extends('layouts.app')

@section('title', 'Payroll Sheet / تنخواہ شیٹ')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('employees.index') }}" class="hover:text-vital-primary">Staff</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Payroll</li>
@endsection

{{--
    Payroll — Salary generation screen (payroll.payroll).
    Summary tiles PayrollController@buildSummary se, sheets table
    employee_salaries ke asal rows se. Generate / Mark Paid dono
    controller ke JSON endpoints ko call karte hain.
--}}
@section('content')
    <div class="page-head">
        <h1>💰 Payroll Sheet
            <span class="ml-1 align-middle rounded-full bg-vital-primary/15 px-3 py-1 font-mono text-xs font-black text-vital-darkred dark:text-red-300">
                {{ \Carbon\Carbon::create($currentYear, $currentMonth, 1)->format('F Y') }}
            </span>
        </h1>
        <p>Is mahine ki salary sheets — generate karein, slip dekhein, paid mark karein</p>
        <div class="page-actions">
            <button type="button" id="btn-generate" class="btn-3d btn-3d-primary">⚙ Generate Salary Sheets</button>
            <a href="{{ route('payroll.attendance') }}" class="btn-3d btn-3d-ghost">Attendance</a>
            <a href="{{ route('payroll.advances') }}" class="btn-3d btn-3d-ghost">Advances</a>
            <a href="{{ route('employees.payroll') }}" class="btn-3d btn-3d-navy">Detailed Payroll</a>
        </div>
    </div>

    <div id="pay-msg" class="mb-5 hidden rounded-2xl px-5 py-3 text-center text-sm font-bold"></div>

    {{-- ================= Summary tiles ================= --}}
    <div class="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">Total Net Payable</div>
            <div class="stat-value">Rs. {{ number_format((float) $summary['total_net_salary'], 2) }}</div>
            <div class="stat-sub">{{ $summary['total_employees'] }} salary sheets is mahine</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-slate">
            <div class="stat-label">Total Basic Salary</div>
            <div class="stat-value">Rs. {{ number_format((float) $summary['total_base_salary'], 2) }}</div>
            <div class="stat-sub">Overtime: Rs. {{ number_format((float) $summary['total_overtime'], 2) }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">Deductions</div>
            <div class="stat-value">Rs. {{ number_format((float) $summary['total_advances_deducted'] + (float) $summary['total_shortage_deductions'], 2) }}</div>
            <div class="stat-sub">Advances Rs. {{ number_format((float) $summary['total_advances_deducted'], 2) }} · Shortage Rs. {{ number_format((float) $summary['total_shortage_deductions'], 2) }}</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-green">
            <div class="stat-label">Paid / Pending</div>
            <div class="stat-value">{{ $summary['paid_count'] }} / {{ $summary['pending_count'] }}</div>
            <div class="stat-sub">Paid sheets / baqi pending sheets</div>
        </div>
    </div>

    {{-- ================= Salary sheets ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Employee</th><th>Days (P/A/H/L)</th><th>Basic</th><th>Overtime</th>
                        <th>Bonus</th><th>Advance Ded.</th><th>Net Salary</th><th>Status</th><th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($salarySheets as $sheet)
                        <tr>
                            <td class="font-semibold">{{ $sheet->employee?->name ?? '—' }}<div class="text-[11px] font-normal text-slate-400">{{ $sheet->employee?->designation }}</div></td>
                            <td class="tabular text-xs">{{ $sheet->present_days }}/{{ $sheet->absent_days }}/{{ $sheet->half_days }}/{{ $sheet->leave_days }}</td>
                            <td class="tabular text-xs">Rs. {{ number_format((float) $sheet->basic_salary, 2) }}</td>
                            <td class="tabular text-xs">Rs. {{ number_format((float) $sheet->overtime_amount, 2) }}</td>
                            <td class="tabular text-xs">Rs. {{ number_format((float) $sheet->bonus_amount, 2) }}</td>
                            <td class="tabular text-xs">Rs. {{ number_format((float) $sheet->advance_deduction, 2) }}</td>
                            <td class="tabular text-xs font-black text-slate-800 dark:text-white">Rs. {{ number_format((float) $sheet->net_salary, 2) }}</td>
                            <td>
                                <span class="pill-status {{ $sheet->status === 'PAID' ? 'pill-active' : 'pill-pending' }}">{{ $sheet->status }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                <a href="{{ route('payroll.salary.show', $sheet) }}" class="btn-3d btn-3d-ghost btn-3d-sm">Slip</a>
                                @if ($sheet->status !== 'PAID')
                                    <button type="button" class="btn-3d btn-3d-success btn-3d-sm mark-paid" data-id="{{ $sheet->id }}">Mark Paid</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="py-8 text-slate-400">Is mahine ki salary sheets abhi generate nahi huin — upar wala Generate button dabayein.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $salarySheets->links() }}</div>
@endsection

@push('scripts')
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const msg = document.getElementById('pay-msg');
    const showMsg = (ok, text) => {
        msg.textContent = text;
        msg.className = 'mb-5 rounded-2xl px-5 py-3 text-center text-sm font-bold ' +
            (ok ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-red-500/15 text-red-700 dark:text-red-300');
        msg.classList.remove('hidden');
    };
    const postJson = async (url, body) => {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(body),
        });
        return { res, data: await res.json() };
    };

    document.getElementById('btn-generate').addEventListener('click', async () => {
        try {
            const { res, data } = await postJson('{{ route('payroll.generate') }}', {
                year: {{ $currentYear }}, month: {{ $currentMonth }},
            });
            showMsg(res.ok && data.success, data.message || 'Generation failed.');
            if (res.ok && data.success) setTimeout(() => window.location.reload(), 900);
        } catch (err) { showMsg(false, 'Network error — sheets not generated.'); }
    });

    document.querySelectorAll('.mark-paid').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const url = '{{ route('payroll.salary.mark-paid', ['salary' => '__ID__']) }}'.replace('__ID__', btn.dataset.id);
            try {
                const { res, data } = await postJson(url, { payment_date: '{{ now()->toDateString() }}' });
                showMsg(res.ok && data.success, data.message || 'Mark-paid failed.');
                if (res.ok && data.success) setTimeout(() => window.location.reload(), 900);
            } catch (err) { showMsg(false, 'Network error — salary not marked paid.'); }
        });
    });
</script>
@endpush
