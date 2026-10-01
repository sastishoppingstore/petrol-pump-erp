@extends('layouts.app')

@section('title', 'Staff Attendance / حاضری')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('employees.index') }}" class="hover:text-vital-primary">Staff</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Attendance</li>
@endsection

{{--
    Payroll — Attendance screen (payroll.attendance).
    Record form + per-employee summary tool dono PayrollController ke
    JSON endpoints (payroll.attendance.store / .summary) se baat karte
    hain. Employees ki list controller se aati hai — koi dummy data nahi.
--}}
@section('content')
    <div class="page-head">
        <h1>🗓️ Staff Attendance</h1>
        <p>Rozana ki hazri record karein aur kisi bhi employee ka summary dekhein</p>
        <div class="page-actions">
            <a href="{{ route('payroll.payroll') }}" class="btn-3d btn-3d-ghost">Payroll Sheet</a>
            <a href="{{ route('payroll.advances') }}" class="btn-3d btn-3d-ghost">Advances</a>
            <a href="{{ route('employees.attendance') }}" class="btn-3d btn-3d-navy">Bulk Attendance</a>
        </div>
    </div>

    <div id="att-msg" class="mb-5 hidden rounded-2xl px-5 py-3 text-center text-sm font-bold"></div>

    <div class="grid gap-5 xl:grid-cols-2">
        {{-- ================= Record attendance ================= --}}
        <div class="glass-card p-6">
            <h2 class="mb-4 text-center text-base font-black text-slate-800 dark:text-white">✍️ Record Attendance</h2>
            <form id="att-form" class="space-y-4">
                <div class="field-3d">
                    <label for="att_employee" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Employee *</label>
                    <select id="att_employee" name="employee_id" class="input-3d" required>
                        <option value="">— Select employee —</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->designation }})</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="field-3d">
                        <label for="att_date" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Date *</label>
                        <input type="date" id="att_date" name="attendance_date" value="{{ now()->toDateString() }}" class="input-3d" required>
                    </div>
                    <div class="field-3d">
                        <label for="att_status" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Status *</label>
                        <select id="att_status" name="status" class="input-3d" required>
                            <option value="PRESENT">Present</option>
                            <option value="ABSENT">Absent</option>
                            <option value="HALF_DAY">Half Day</option>
                            <option value="LEAVE">Leave</option>
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="field-3d">
                        <label for="att_in" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Check-in</label>
                        <input type="time" id="att_in" name="check_in_time" class="input-3d">
                    </div>
                    <div class="field-3d">
                        <label for="att_out" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Check-out</label>
                        <input type="time" id="att_out" name="check_out_time" class="input-3d">
                    </div>
                </div>
                <div class="field-3d">
                    <label for="att_notes" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Notes</label>
                    <input type="text" id="att_notes" name="notes" maxlength="500" class="input-3d" placeholder="Optional note">
                </div>
                <button type="submit" class="btn-3d btn-3d-primary w-full">✔ Save Attendance</button>
            </form>
        </div>

        {{-- ================= Attendance summary ================= --}}
        <div class="glass-card p-6">
            <h2 class="mb-4 text-center text-base font-black text-slate-800 dark:text-white">📊 Attendance Summary</h2>
            <form id="sum-form" class="space-y-4">
                <div class="field-3d">
                    <label for="sum_employee" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Employee *</label>
                    <select id="sum_employee" class="input-3d" required>
                        <option value="">— Select employee —</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}">{{ $employee->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div class="field-3d">
                        <label for="sum_from" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">From *</label>
                        <input type="date" id="sum_from" value="{{ now()->startOfMonth()->toDateString() }}" class="input-3d" required>
                    </div>
                    <div class="field-3d">
                        <label for="sum_to" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">To *</label>
                        <input type="date" id="sum_to" value="{{ now()->toDateString() }}" class="input-3d" required>
                    </div>
                </div>
                <button type="submit" class="btn-3d btn-3d-navy w-full">View Summary</button>
            </form>
            <div id="sum-result" class="mt-5 hidden">
                <div class="grid grid-cols-3 gap-3 text-center">
                    <div class="rounded-2xl bg-emerald-500/10 p-3"><div class="text-xl font-black text-emerald-600 dark:text-emerald-400" id="sum-present">0</div><div class="text-[11px] font-bold text-slate-500">Present</div></div>
                    <div class="rounded-2xl bg-red-500/10 p-3"><div class="text-xl font-black text-red-600 dark:text-red-400" id="sum-absent">0</div><div class="text-[11px] font-bold text-slate-500">Absent</div></div>
                    <div class="rounded-2xl bg-amber-500/10 p-3"><div class="text-xl font-black text-amber-600 dark:text-amber-400" id="sum-half">0</div><div class="text-[11px] font-bold text-slate-500">Half Day</div></div>
                    <div class="rounded-2xl bg-sky-500/10 p-3"><div class="text-xl font-black text-sky-600 dark:text-sky-400" id="sum-leave">0</div><div class="text-[11px] font-bold text-slate-500">Leave</div></div>
                    <div class="rounded-2xl bg-slate-500/10 p-3"><div class="text-xl font-black text-slate-700 dark:text-slate-200" id="sum-total">0</div><div class="text-[11px] font-bold text-slate-500">Total Days</div></div>
                    <div class="rounded-2xl bg-violet-500/10 p-3"><div class="text-xl font-black text-violet-600 dark:text-violet-400" id="sum-hours">0</div><div class="text-[11px] font-bold text-slate-500">Work Hours</div></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Active employees ================= --}}
    <div class="glass-card mt-5 overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr><th>Employee</th><th>Designation</th><th>Phone</th><th>Basic Salary</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($employees as $employee)
                        <tr>
                            <td class="font-semibold">{{ $employee->name }}</td>
                            <td class="text-xs">{{ $employee->designation }}</td>
                            <td class="text-xs">{{ $employee->phone ?? '—' }}</td>
                            <td class="tabular text-xs font-semibold">Rs. {{ number_format((float) $employee->basic_salary, 2) }}</td>
                            <td><span class="pill-status pill-active">{{ $employee->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-slate-400">No active employees found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $employees->links() }}</div>
@endsection

@push('scripts')
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const msg = document.getElementById('att-msg');
    const showMsg = (ok, text) => {
        msg.textContent = text;
        msg.className = 'mb-5 rounded-2xl px-5 py-3 text-center text-sm font-bold ' +
            (ok ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-red-500/15 text-red-700 dark:text-red-300');
        msg.classList.remove('hidden');
    };

    document.getElementById('att-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await fetch('{{ route('payroll.attendance.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            showMsg(res.ok && data.success, data.message || 'Request failed.');
            if (res.ok && data.success) e.target.reset();
        } catch (err) { showMsg(false, 'Network error — attendance not saved.'); }
    });

    document.getElementById('sum-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const emp = document.getElementById('sum_employee').value;
        if (!emp) { showMsg(false, 'Select an employee first.'); return; }
        const from = document.getElementById('sum_from').value;
        const to = document.getElementById('sum_to').value;
        const url = '{{ route('payroll.attendance.summary', ['employee' => '__EMP__']) }}'.replace('__EMP__', emp) +
            '?from_date=' + from + '&to_date=' + to;
        try {
            const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
            const data = await res.json();
            if (res.ok && data.success) {
                document.getElementById('sum-present').textContent = data.summary.present;
                document.getElementById('sum-absent').textContent = data.summary.absent;
                document.getElementById('sum-half').textContent = data.summary.half_day;
                document.getElementById('sum-leave').textContent = data.summary.leave;
                document.getElementById('sum-total').textContent = data.summary.total_days;
                document.getElementById('sum-hours').textContent = data.summary.total_working_hours;
                document.getElementById('sum-result').classList.remove('hidden');
            } else { showMsg(false, data.message || 'Summary failed.'); }
        } catch (err) { showMsg(false, 'Network error — summary not loaded.'); }
    });
</script>
@endpush
