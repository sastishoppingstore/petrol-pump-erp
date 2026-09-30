<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-primary">
            <small class="text-muted text-uppercase fw-bold">Active Staff Count (ملازمین کی تعداد)</small>
            <div class="fs-4 fw-bold">{{ count($data['employees']) }} Employees</div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 bg-light rounded border-start border-4 border-danger">
            <small class="text-muted text-uppercase fw-bold">Salaries Paid in Month ({{ $data['month'] }})</small>
            <div class="fs-4 fw-bold text-danger">{{ \App\Support\PakistaniCurrency::format($data['total_salaries']) }}</div>
        </div>
    </div>
</div>

<div class="table-responsive">
    <table class="{{ ($isPrint ?? false) ? 'report-table' : 'table table-hover table-striped align-middle border' }}">
        <thead class="table-light">
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
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($emp->basic_salary) }}</td>
                    <td class="text-right">{{ \App\Support\PakistaniCurrency::format($emp->daily_wage) }}</td>
                    <td class="text-center"><span class="badge bg-success">{{ $emp->status }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="text-center text-muted py-4">No employees registered.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
