<?php

namespace App\Http\Controllers;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\EmployeeAdjustment;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use App\Models\Shift;
use App\Services\Payroll\PayrollService;
use App\Services\Security\BranchScopeService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly PayrollService $payroll,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * List all employees with their advances & stats.
     */
    public function index(Request $request): View
    {
        $branchId = $request->user()->branch_id ?? 1;

        $query = Employee::query()
            ->with(['branch', 'advances' => fn ($q) => $q->where('status', 'ACTIVE')])
            ->where('branch_id', $branchId);

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('search')) {
            $search = '%' . $request->input('search') . '%';
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search)
                    ->orWhere('phone', 'like', $search)
                    ->orWhere('cnic', 'like', $search)
                    ->orWhere('designation', 'like', $search);
            });
        }

        $employees = $query->orderBy('name')->paginate(20)->withQueryString();

        $activeEmployees = Employee::where('branch_id', $branchId)->where('status', Employee::STATUS_ACTIVE)->get();
        $totalSalaryBudget = $activeEmployees->sum(fn ($e) => (float) $e->basic_salary);
        $totalAdvances = EmployeeAdvance::where('branch_id', $branchId)->where('status', EmployeeAdvance::STATUS_ACTIVE)->sum('balance');

        return view('employees.index', [
            'employees' => $employees,
            'activeCount' => $activeEmployees->count(),
            'totalSalaryBudget' => $totalSalaryBudget,
            'totalAdvances' => $totalAdvances,
            'bankAccounts' => BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get(),
            'shifts' => Shift::query()->where('status', 'OPEN')->get(),
        ]);
    }

    /**
     * Create employee form.
     */
    public function create(Request $request): View
    {
        return view('employees.create', [
            'employee' => new Employee(['status' => 'ACTIVE', 'basic_salary' => '0.00', 'daily_wage' => '0.00']),
        ]);
    }

    /**
     * Store new employee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'designation' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'cnic' => ['nullable', 'string', 'max:30'],
            'joining_date' => ['nullable', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'daily_wage' => ['nullable', 'numeric', 'min:0'],
        ]);

        $branchId = $request->user()->branch_id ?? 1;

        try {
            $employee = $this->payroll->createEmployee($request->user(), array_merge($validated, [
                'branch_id' => $branchId,
            ]));
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->name} ({$employee->code}) registered successfully.");
    }

    /**
     * Edit employee.
     */
    public function edit(Employee $employee): View
    {
        return view('employees.create', [
            'employee' => $employee,
        ]);
    }

    /**
     * Update employee.
     */
    public function update(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'designation' => ['required', 'string', 'max:100'],
            'phone' => ['nullable', 'string', 'max:30'],
            'cnic' => ['nullable', 'string', 'max:30'],
            'joining_date' => ['nullable', 'date'],
            'basic_salary' => ['required', 'numeric', 'min:0'],
            'daily_wage' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:ACTIVE,INACTIVE,TERMINATED'],
        ]);

        $employee->update($validated);

        return redirect()->route('employees.index')
            ->with('success', "Employee {$employee->name} updated successfully.");
    }

    /**
     * Daily Attendance page.
     */
    public function attendance(Request $request): View
    {
        $branchId = $request->user()->branch_id ?? 1;
        $date = $request->input('date', today()->toDateString());

        $employees = Employee::query()
            ->where('branch_id', $branchId)
            ->where('status', Employee::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        $existingAttendances = EmployeeAttendance::query()
            ->where('branch_id', $branchId)
            ->where('date', $date)
            ->get()
            ->keyBy('employee_id');

        $stats = [
            'present' => $existingAttendances->where('status', EmployeeAttendance::STATUS_PRESENT)->count(),
            'absent' => $existingAttendances->where('status', EmployeeAttendance::STATUS_ABSENT)->count(),
            'leave' => $existingAttendances->where('status', EmployeeAttendance::STATUS_LEAVE)->count(),
            'half_day' => $existingAttendances->where('status', EmployeeAttendance::STATUS_HALF_DAY)->count(),
        ];

        return view('employees.attendance', [
            'date' => $date,
            'employees' => $employees,
            'existingAttendances' => $existingAttendances,
            'stats' => $stats,
        ]);
    }

    /**
     * Save bulk attendance.
     */
    public function storeAttendance(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'attendance' => ['required', 'array'],
            'attendance.*' => ['required', 'in:PRESENT,ABSENT,LEAVE,HALF_DAY'],
        ]);

        $branchId = $request->user()->branch_id ?? 1;

        try {
            $count = $this->payroll->bulkAttendance(
                actor: $request->user(),
                branchId: $branchId,
                date: $validated['date'],
                records: $validated['attendance'],
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('employees.attendance', ['date' => $validated['date']])
            ->with('success', "Attendance marked for {$count} staff members on {$validated['date']}.");
    }

    /**
     * Issue Advance / Loan to staff.
     */
    public function storeAdvance(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'monthly_deduction' => ['nullable', 'numeric', 'min:0'],
            'payment_method' => ['required', 'in:CASH,BANK_TRANSFER'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'reason' => ['nullable', 'string', 'max:255'],
            'advance_date' => ['nullable', 'date'],
        ]);

        try {
            $this->payroll->giveAdvance(
                actor: $request->user(),
                employeeId: $employee->id,
                amount: (string) $validated['amount'],
                paymentMethod: $validated['payment_method'],
                bankAccountId: $validated['bank_account_id'] ?? null,
                shiftId: $validated['shift_id'] ?? null,
                monthlyDeduction: (string) ($validated['monthly_deduction'] ?? '0.00'),
                reason: $validated['reason'] ?? 'Staff Advance Loan',
                date: $validated['advance_date'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Advance of Rs. " . number_format($validated['amount'], 2) . " granted to {$employee->name}.");
    }

    /**
     * Record adjustment: Overtime, bonus, fine, cash shortage.
     */
    public function storeAdjustment(Request $request, Employee $employee): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'in:OVERTIME,BONUS,FINE,SHORTAGE_RECOVERY'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payroll_month' => ['required', 'regex:/^\d{4}-\d{2}$/'],
            'reason' => ['required', 'string', 'max:255'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
        ]);

        try {
            $this->payroll->recordAdjustment(
                actor: $request->user(),
                employeeId: $employee->id,
                type: $validated['type'],
                amount: (string) $validated['amount'],
                effectiveDate: today()->toDateString(),
                payrollMonth: $validated['payroll_month'],
                shiftId: $validated['shift_id'] ?? null,
                reason: $validated['reason'],
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Adjustment of Rs. " . number_format($validated['amount'], 2) . " recorded for {$employee->name}.");
    }

    /**
     * Monthly payroll salary sheet.
     */
    public function payroll(Request $request): View
    {
        $branchId = $request->user()->branch_id ?? 1;
        $month = $request->input('month', date('Y-m'));

        $salaries = EmployeeSalary::query()
            ->with(['employee', 'bankAccount.bank', 'shift'])
            ->where('branch_id', $branchId)
            ->where('month', $month)
            ->orderBy('id')
            ->get();

        $activeEmployeesCount = Employee::where('branch_id', $branchId)->where('status', Employee::STATUS_ACTIVE)->count();

        $totalNet = $salaries->sum(fn ($s) => (float) $s->net_salary);
        $totalPaid = $salaries->where('status', EmployeeSalary::STATUS_PAID)->sum(fn ($s) => (float) $s->paid_amount);
        $totalPending = $salaries->where('status', EmployeeSalary::STATUS_PENDING)->sum(fn ($s) => (float) $s->net_salary);

        return view('employees.payroll', [
            'month' => $month,
            'salaries' => $salaries,
            'activeEmployeesCount' => $activeEmployeesCount,
            'totalNet' => $totalNet,
            'totalPaid' => $totalPaid,
            'totalPending' => $totalPending,
            'bankAccounts' => BankAccount::query()->with('bank')->where('status', 'ACTIVE')->get(),
            'shifts' => Shift::query()->where('status', 'OPEN')->get(),
        ]);
    }

    /**
     * Generate or recalculate monthly payroll sheet.
     */
    public function generatePayroll(Request $request): RedirectResponse
    {
        $month = $request->input('month', date('Y-m'));
        $branchId = $request->user()->branch_id ?? 1;

        try {
            $sheet = $this->payroll->generateMonthlySalarySheet($request->user(), $branchId, $month);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('employees.payroll', ['month' => $month])
            ->with('success', "Monthly payroll sheet generated for {$month} ({$sheet->count()} employees).");
    }

    /**
     * Pay single salary.
     */
    public function paySalary(Request $request, EmployeeSalary $salary): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => ['required', 'in:CASH,BANK_TRANSFER'],
            'bank_account_id' => ['nullable', 'exists:bank_accounts,id'],
            'shift_id' => ['nullable', 'exists:shifts,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->payroll->paySalary(
                actor: $request->user(),
                salary: $salary,
                paymentMethod: $validated['payment_method'],
                bankAccountId: $validated['bank_account_id'] ?? null,
                shiftId: $validated['shift_id'] ?? null,
                notes: $validated['notes'] ?? null,
            );
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Salary of Rs. " . number_format((float) $salary->net_salary, 2) . " paid to {$salary->employee->name}.");
    }

    /**
     * Printable payslip.
     */
    public function payslip(EmployeeSalary $salary): View
    {
        $salary->load(['employee', 'branch', 'bankAccount.bank', 'shift', 'creator']);
        $payslipData = $this->payroll->getPayslipData($salary);

        return view('employees.payslip', $payslipData);
    }
}
