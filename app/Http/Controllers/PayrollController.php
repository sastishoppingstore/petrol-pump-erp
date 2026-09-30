<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\EmployeeSalary;
use App\Services\Payroll\PayrollService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PayrollController extends Controller
{
    private PayrollService $payrollService;

    public function __construct(PayrollService $payrollService)
    {
        $this->payrollService = $payrollService;
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

            $checkInTime = $validated['check_in_time']
                ? Carbon::createFromFormat('H:i', $validated['check_in_time'])
                : null;

            $checkOutTime = $validated['check_out_time']
                ? Carbon::createFromFormat('H:i', $validated['check_out_time'])
                : null;

            $attendance = $this->payrollService->recordAttendance(
                $employee,
                new \DateTime($validated['attendance_date']),
                $validated['status'],
                $checkInTime,
                $checkOutTime,
                $validated['notes'] ?? null
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

        $summary = $this->payrollService->getAttendanceSummary(
            $employee,
            Carbon::parse($validated['from_date']),
            Carbon::parse($validated['to_date'])
        );

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

        return view('payroll.advances', compact('employees'));
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

            $advance = $this->payrollService->recordAdvance(
                $employee,
                (float) $validated['amount'],
                new \DateTime(),
                $validated['notes'] ?? null
            );

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

        $salarySheets = EmployeeSalary::where('payroll_month', sprintf('%04d-%02d', $currentYear, $currentMonth))
            ->with('employee')
            ->paginate(20);

        $summary = $this->payrollService->getPayrollSummary($currentYear, $currentMonth);

        return view('payroll.payroll', compact('salarySheets', 'summary', 'currentMonth', 'currentYear'));
    }

    /**
     * Generate salary sheets for all employees for a given month
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
            $employees = Employee::where('status', 'ACTIVE')->get();

            $generated = 0;
            foreach ($employees as $employee) {
                $this->payrollService->generateSalarySheet(
                    $employee,
                    $validated['year'],
                    $validated['month'],
                    $branchId
                );
                $generated++;
            }

            return response()->json([
                'success' => true,
                'message' => "Salary sheets generated for {$generated} employees",
                'generated_count' => $generated,
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
            $this->payrollService->markSalaryAsPaid(
                $salary,
                new \DateTime($validated['payment_date'])
            );

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

        $summary = $this->payrollService->getPayrollSummary(
            $validated['year'],
            $validated['month'],
            $branchId
        );

        return response()->json([
            'success' => true,
            'summary' => $summary,
        ]);
    }
}
