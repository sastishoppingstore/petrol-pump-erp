@extends('layouts.app')

@section('title', 'Staff Advances / پیشگی')
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('employees.index') }}" class="hover:text-vital-primary">Staff</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Advances</li>
@endsection

{{--
    Payroll — Staff Advances screen (payroll.advances).
    Naya advance PayrollController@recordAdvance (JSON) se save hota hai;
    history aur outstanding total controller se aate hain — asal data.
--}}
@section('content')
    <div class="page-head">
        <h1>💵 Staff Advances</h1>
        <p>Employee advances / loans — salary se automatic recovery ke liye record</p>
        <div class="page-actions">
            <a href="{{ route('payroll.attendance') }}" class="btn-3d btn-3d-ghost">Attendance</a>
            <a href="{{ route('payroll.payroll') }}" class="btn-3d btn-3d-ghost">Payroll Sheet</a>
        </div>
    </div>

    <div class="mb-5 grid gap-4 sm:grid-cols-2">
        <div class="stat-tile-3d tilt-3d stat-amber">
            <div class="stat-label">Total Outstanding Advances</div>
            <div class="stat-value">Rs. {{ number_format((float) $totalOutstanding, 2) }}</div>
            <div class="stat-sub">ACTIVE advances ka kul balance</div>
        </div>
        <div class="stat-tile-3d tilt-3d stat-navy">
            <div class="stat-label">Active Employees</div>
            <div class="stat-value">{{ $employees->count() }}</div>
            <div class="stat-sub">Advance ke liye eligible staff</div>
        </div>
    </div>

    <div id="adv-msg" class="mb-5 hidden rounded-2xl px-5 py-3 text-center text-sm font-bold"></div>

    {{-- ================= New advance ================= --}}
    <div class="glass-card mb-5 p-6">
        <h2 class="mb-4 text-center text-base font-black text-slate-800 dark:text-white">➕ Record New Advance</h2>
        <form id="adv-form" class="grid items-end gap-4 md:grid-cols-4">
            <div class="field-3d">
                <label for="adv_employee" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Employee *</label>
                <select id="adv_employee" name="employee_id" class="input-3d" required>
                    <option value="">— Select employee —</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->name }} ({{ $employee->designation }})</option>
                    @endforeach
                </select>
            </div>
            <div class="field-3d">
                <label for="adv_amount" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Amount (Rs.) *</label>
                <input type="number" id="adv_amount" name="amount" step="0.01" min="0.01" class="input-3d" required>
            </div>
            <div class="field-3d">
                <label for="adv_notes" class="mb-1.5 block text-center text-xs font-bold text-slate-600 dark:text-slate-300">Reason / Notes</label>
                <input type="text" id="adv_notes" name="notes" maxlength="500" class="input-3d" placeholder="Optional">
            </div>
            <button type="submit" class="btn-3d btn-3d-primary w-full">✔ Save Advance</button>
        </form>
    </div>

    {{-- ================= Advances history ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr><th>Date</th><th>Employee</th><th>Amount</th><th>Balance</th><th>Method</th><th>Reason</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($advances as $advance)
                        <tr>
                            <td class="whitespace-nowrap text-xs">{{ $advance->advance_date?->format('d M Y') }}</td>
                            <td class="font-semibold">{{ $advance->employee?->name ?? '—' }}</td>
                            <td class="tabular text-xs font-semibold">Rs. {{ number_format((float) $advance->amount, 2) }}</td>
                            <td class="tabular text-xs font-black text-slate-800 dark:text-white">Rs. {{ number_format((float) $advance->balance, 2) }}</td>
                            <td class="text-xs">{{ $advance->payment_method }}</td>
                            <td class="text-xs text-slate-400">{{ $advance->reason ?: '—' }}</td>
                            <td>
                                <span class="pill-status {{ $advance->status === 'ACTIVE' ? 'pill-pending' : ($advance->status === 'RECOVERED' ? 'pill-active' : 'pill-danger') }}">
                                    {{ $advance->status }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-slate-400">No advances recorded yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">{{ $advances->links() }}</div>
@endsection

@push('scripts')
<script>
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const msg = document.getElementById('adv-msg');
    document.getElementById('adv-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        const body = Object.fromEntries(new FormData(e.target).entries());
        try {
            const res = await fetch('{{ route('payroll.advances.store') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            const data = await res.json();
            msg.textContent = data.message || 'Request failed.';
            msg.className = 'mb-5 rounded-2xl px-5 py-3 text-center text-sm font-bold ' +
                (res.ok && data.success ? 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' : 'bg-red-500/15 text-red-700 dark:text-red-300');
            msg.classList.remove('hidden');
            if (res.ok && data.success) setTimeout(() => window.location.reload(), 800);
        } catch (err) {
            msg.textContent = 'Network error — advance not saved.';
            msg.className = 'mb-5 rounded-2xl px-5 py-3 text-center text-sm font-bold bg-red-500/15 text-red-700 dark:text-red-300';
            msg.classList.remove('hidden');
        }
    });
</script>
@endpush
