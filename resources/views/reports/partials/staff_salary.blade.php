<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
    <div class="stat-tile-3d stat-navy">
        <div class="stat-label">Active Staff Count (ملازمین کی تعداد)</div>
        <div class="stat-value">{{ count($data['employees']) }} Employees</div>
    </div>
    <div class="stat-tile-3d stat-red">
        <div class="stat-label">Salaries Paid in Month ({{ $data['month'] }})</div>
        <div class="stat-value">{{ \App\Support\PakistaniCurrency::format($data['total_salaries']) }}</div>
    </div>
</div>

<div class="table-3d">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : '' }}">
        <thead>
            <tr>
                <th>Code</th>
                <th>Employee Name</th>
                <th>Designation</th>
                <th>Phone</th>
                <th class="text-right">Basic Salary (Rs.)</th>
                <th class="text-right">Daily Wage (Rs.)</th>
                <th class="text-center">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data['employees'] as $emp)
                <tr>
                    <td><code>{{ $emp->code }}</code></td>
                    <td class="fw-bold">{{ $emp->name }}</td>
                    <td>{{ $emp->designation }}</td>
                    <td>{{ $emp->phone ?? '-' }}</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($emp->basic_salary) }}</td>
                    <td class="text-right tabular">{{ \App\Support\PakistaniCurrency::format($emp->daily_wage) }}</td>
                    <td class="text-center"><span class="badge bg-success">{{ $emp->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No employees registered.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
