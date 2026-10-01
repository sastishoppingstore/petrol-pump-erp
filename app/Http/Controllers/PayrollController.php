<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeAdjustment;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Payroll screens & JSON actions (routes/payroll.php, names payroll.*).
 *
 * NOTE (schema fix, 2026-10 sweep): PayrollService was written against a
 * phantom schema (employee_salaries.payroll_month, employee_attendances.
 * attendance_date / check_in_time / working_hours) that does not exist in
 * the migrated tables. The real columns — used by EmployeeController and
 * the employees.* screens — are:
 *   employee_salaries:  month (YYYY-MM), status PAID|PENDING, payment_date
 *   employee_attendances: date, in_time, out_time
 *   employee_adjustments: payroll_month (this table really has it)
 * This controller therefore works directly against the real schema so the
 * payroll.* endpoints function; PayrollService itself still needs the
 * same correction for EmployeeController's calls (createEmployee,
 * recordAdjustment, generateMonthlySalarySheet, paySalary, getPayslipData
 * are missing there entirely).
 */
class PayrollController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * Attendance tracking screen
     */
    public function attendance(): View
    {
        $employees = Employee::where('status', 'ACTIVE')
            ->orderBy('name')
            ->paginate(20);

        return view('payroll.attendance', compact('employees'));
    }

    /**
     * Record or update employee attendance
     */
    public function recordAttendance(Request $request): JsonResponse
    {
        $this->authorize('create', EmployeeSalary::class);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'attendance_date' => 'required|date',
            'status' => 'required|in:PRESENT,ABSENT,HALF_DAY,LEAVE',
            'check_in_time' => 'nullable|date_format:H:i',
            'check_out_time' => 'nullable|date_format:H:i',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $employee = Employee::findOrFail($validated['employee_id']);

            $attendance = EmployeeAttendance::updateOrCreate(
                [
                    'employee_id' => $employee->id,
                    'date' => Carbon::parse($validated['attendance_date'])->toDateString(),
                ],
                [
                    'branch_id' => $employee->branch_id,
                    'status' => $validated['status'],
                    'in_time' => $validated['check_in_time'] ?? null,
                    'out_time' => $validated['check_out_time'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'recorded_by' => $request->user()->id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Attendance recorded successfully',
                'attendance' => $attendance,
            ]);
        } catch (\Exception $e) {
            \Log::error('Attendance recording failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to record attendance. No changes were saved.',
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * View attendance summary for an employee
     */
    public function attendanceSummary(Employee $employee, Request $request): JsonResponse
    {
        $this->authorize('view', EmployeeSalary::class);

        $validated = $request->validate([
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
        ]);

        $attendances = EmployeeAttendance::where('employee_id', $employee->id)
            ->whereBetween('date', [$validated['from_date'], $validated['to_date']])
            ->get();

        $summary = [
            'present' => 0,
            'absent' => 0,
            'half_day' => 0,
            'leave' => 0,
            'total_days' => $attendances->count(),
            'total_working_hours' => 0.0,
        ];

        foreach ($attendances as $attendance) {
            match ($attendance->status) {
                EmployeeAttendance::STATUS_PRESENT => $summary['present']++,
                EmployeeAttendance::STATUS_ABSENT => $summary['absent']++,
                EmployeeAttendance::STATUS_HALF_DAY => $summary['half_day']++,
                EmployeeAttendance::STATUS_LEAVE => $summary['leave']++,
                default => null,
            };

            if ($attendance->in_time && $attendance->out_time) {
                $in = Carbon::parse($attendance->in_time);
                $out = Carbon::parse($attendance->out_time);
                if ($out->greaterThan($in)) {
                    $summary['total_working_hours'] += $out->diffInMinutes($in) / 60;
                }
            }
        }

        $summary['total_working_hours'] = round($summary['total_working_hours'], 2);

        return response()->json([
            'success' => true,
            'employee' => $employee->name,
            'summary' => $summary,
        ]);
    }

    /**
     * Staff advances screen
     */
    public function advances(): View
    {
        $employees = Employee::where('status', 'ACTIVE')->orderBy('name')->get();

        $advances = EmployeeAdvance::with('employee')
            ->orderByDesc('advance_date')
            ->orderByDesc('id')
            ->paginate(20);

        $totalOutstanding = EmployeeAdvance::where('status', EmployeeAdvance::STATUS_ACTIVE)->sum('balance');

        return view('payroll.advances', compact('employees', 'advances', 'totalOutstanding'));
    }

    /**
     * Record staff advance
     */
    public function recordAdvance(Request $request): JsonResponse
    {
        $this->authorize('create', EmployeeSalary::class);

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $employee = Employee::findOrFail($validated['employee_id']);

            $advance = EmployeeAdvance::create([
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'amount' => round((float) $validated['amount'], 2),
                'balance' => round((float) $validated['amount'], 2),
                'monthly_deduction' => 0,
                'advance_date' => now()->toDateString(),
                'payment_method' => EmployeeAdvance::METHOD_CASH,
                'reason' => $validated['notes'] ?? null,
                'status' => EmployeeAdvance::STATUS_ACTIVE,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Advance recorded successfully',
                'advance' => $advance,
            ]);
        } catch (\Exception $e) {
            \Log::error('Advance recording failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to record advance. No changes were saved.',
            ], 400);
        }
    }

    /**
     * Salary generation screen
     */
    public function payroll(): View
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;
        $monthString = sprintf('%04d-%02d', $currentYear, $currentMonth);

        $salarySheets = EmployeeSalary::where('month', $monthString)
            ->with('employee')
            ->orderBy('id')
            ->paginate(20);

        $summary = $this->buildSummary($currentYear, $currentMonth);

        return view('payroll.payroll', compact('salarySheets', 'summary', 'currentMonth', 'currentYear'));
    }

    /**
     * Generate salary sheets for all employees for a given month.
     * Formula (AGENTS.md / PayrollService docblock):
     *   Net = Basic + Overtime + Bonus − Advance Deduction − Fine − Shortage
     * Existing sheets are never overwritten (append-only payroll history).
     */
    public function generateSalarySheets(Request $request): JsonResponse
    {
        $this->authorize('create', EmployeeSalary::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        try {
            $branchId = auth()->user()->branch_id ?? 1;
            $monthString = sprintf('%04d-%02d', $validated['year'], $validated['month']);
            $monthStart = Carbon::create($validated['year'], $validated['month'], 1);
            $monthEnd = $monthStart->copy()->endOfMonth();

            $employees = Employee::where('status', 'ACTIVE')->get();

            $generated = 0;
            $skipped = 0;

            DB::transaction(function () use ($employees, $monthString, $monthStart, $monthEnd, $branchId, $request, &$generated, &$skipped) {
                foreach ($employees as $employee) {
                    $exists = EmployeeSalary::where('employee_id', $employee->id)
                        ->where('month', $monthString)
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    $attendance = EmployeeAttendance::where('employee_id', $employee->id)
                        ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                        ->get();

                    $adjustments = EmployeeAdjustment::where('employee_id', $employee->id)
                        ->where('payroll_month', $monthString);

                    $overtime = (float) (clone $adjustments)->where('type', 'OVERTIME')->sum('amount');
                    $bonus = (float) (clone $adjustments)->where('type', 'BONUS')->sum('amount');
                    $fine = (float) (clone $adjustments)->where('type', 'FINE')->sum('amount');
                    $shortage = (float) (clone $adjustments)->where('type', 'SHORTAGE_RECOVERY')->sum('amount');

                    $advanceDeduction = (float) $employee->advances()
                        ->where('status', EmployeeAdvance::STATUS_ACTIVE)
                        ->sum('balance');

                    $basic = round((float) $employee->basic_salary, 2);
                    $net = max(0, round($basic + $overtime + $bonus - $advanceDeduction - $fine - $shortage, 2));

                    EmployeeSalary::create([
                        'branch_id' => $employee->branch_id ?? $branchId,
                        'employee_id' => $employee->id,
                        'month' => $monthString,
                        'present_days' => $attendance->where('status', EmployeeAttendance::STATUS_PRESENT)->count(),
                        'absent_days' => $attendance->where('status', EmployeeAttendance::STATUS_ABSENT)->count(),
                        'half_days' => $attendance->where('status', EmployeeAttendance::STATUS_HALF_DAY)->count(),
                        'leave_days' => $attendance->where('status', EmployeeAttendance::STATUS_LEAVE)->count(),
                        'basic_salary' => $basic,
                        'overtime_amount' => round($overtime, 2),
                        'bonus_amount' => round($bonus, 2),
                        'fine_amount' => round($fine, 2),
                        'advance_deduction' => round($advanceDeduction, 2),
                        'allowances' => 0,
                        'deductions' => round($fine + $shortage, 2),
                        'net_salary' => $net,
                        'paid_amount' => 0,
                        'status' => EmployeeSalary::STATUS_PENDING,
                        'created_by' => $request->user()->id,
                    ]);

                    $generated++;
                }
            });

            return response()->json([
                'success' => true,
                'message' => "Salary sheets generated for {$generated} employees",
                'generated_count' => $generated,
                'skipped_count' => $skipped,
            ]);
        } catch (\Exception $e) {
            \Log::error('Salary generation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate salary sheets. No changes were saved.',
            ], 400);
        }
    }

    /**
     * Get detailed salary sheet
     */
    public function showSalary(EmployeeSalary $salary): View
    {
        $this->authorize('view', $salary);

        $salary->load(['employee', 'branch', 'bankAccount']);

        return view('payroll.show-salary', compact('salary'));
    }

    /**
     * Mark salary as paid
     */
    public function markSalaryPaid(EmployeeSalary $salary, Request $request): JsonResponse
    {
        $this->authorize('update', $salary);

        $validated = $request->validate([
            'payment_date' => 'required|date',
        ]);

        try {
            $salary->update([
                'status' => EmployeeSalary::STATUS_PAID,
                'payment_date' => $validated['payment_date'],
                'paid_amount' => $salary->net_salary,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Salary marked as paid',
                'salary' => $salary->fresh(),
            ]);
        } catch (\Exception $e) {
            \Log::error('Salary payment marking failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to mark salary as paid. No changes were saved.',
            ], 400);
        }
    }

    /**
     * Payroll summary report
     */
    public function summary(Request $request): JsonResponse
    {
        $this->authorize('view', EmployeeSalary::class);

        $validated = $request->validate([
            'year' => 'required|integer|min:2020|max:2099',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $branchId = auth()->user()->branch_id ?? null;

        $summary = $this->buildSummary($validated['year'], $validated['month'], $branchId);

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }

    /**
     * Monthly payroll totals from the real schema. Shortage deductions live
     * on employee_adjustments (SHORTAGE_RECOVERY, keyed by payroll_month);
     * every other figure comes from employee_salaries itself.
     */
    private function buildSummary(int $year, int $month, ?int $branchId = null): array
    {
        $monthString = sprintf('%04d-%02d', $year, $month);

        $query = EmployeeSalary::where('month', $monthString);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        $sheets = $query->get();

        $shortageQuery = EmployeeAdjustment::where('type', 'SHORTAGE_RECOVERY')
            ->where('payroll_month', $monthString);
        if ($branchId) {
            $shortageQuery->where('branch_id', $branchId);
        }

        return [
            'total_employees' => $sheets->count(),
            'total_base_salary' => round((float) $sheets->sum('basic_salary'), 2),
            'total_overtime' => round((float) $sheets->sum('overtime_amount'), 2),
            'total_advances_deducted' => round((float) $sheets->sum('advance_deduction'), 2),
            'total_shortage_deductions' => round((float) $shortageQuery->sum('amount'), 2),
            'total_net_salary' => round((float) $sheets->sum('net_salary'), 2),
            'paid_count' => $sheets->where('status', EmployeeSalary::STATUS_PAID)->count(),
            'pending_count' => $sheets->where('status', EmployeeSalary::STATUS_PENDING)->count(),
        ];
    }
}
