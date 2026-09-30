<?php

namespace App\Services\Payroll;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeSalary;
use App\Models\Shift;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollService
{
    /**
     * Record attendance for an employee
     */
    public function recordAttendance(
        Employee $employee,
        \DateTime $attendanceDate,
        string $status, // PRESENT, ABSENT, HALF_DAY, LEAVE
        ?\DateTime $checkInTime = null,
        ?\DateTime $checkOutTime = null,
        ?string $notes = null
    ): EmployeeAttendance {
        return DB::transaction(function () use (
            $employee,
            $attendanceDate,
            $status,
            $checkInTime,
            $checkOutTime,
            $notes
        ) {
            // Check if attendance already exists for this date
            $existing = EmployeeAttendance::where('employee_id', $employee->id)
                ->where('attendance_date', $attendanceDate)
                ->first();

            if ($existing) {
                // Update existing record
                $existing->update([
                    'status' => $status,
                    'check_in_time' => $checkInTime,
                    'check_out_time' => $checkOutTime,
                    'working_hours' => $this->calculateWorkingHours($checkInTime, $checkOutTime),
                    'notes' => $notes,
                ]);
                return $existing;
            }

            // Create new attendance record
            return EmployeeAttendance::create([
                'employee_id' => $employee->id,
                'attendance_date' => $attendanceDate,
                'status' => $status,
                'check_in_time' => $checkInTime,
                'check_out_time' => $checkOutTime,
                'working_hours' => $this->calculateWorkingHours($checkInTime, $checkOutTime),
                'notes' => $notes,
            ]);
        });
    }

    /**
     * Calculate working hours between check-in and check-out
     */
    private function calculateWorkingHours(?Carbon $checkIn, ?Carbon $checkOut): float
    {
        if (!$checkIn || !$checkOut) {
            return 0;
        }

        // Calculate hours, rounded to 2 decimals
        $hours = $checkOut->diffInMinutes($checkIn) / 60;
        return round($hours, 2);
    }

    /**
     * Record staff advance/loan
     */
    public function recordAdvance(
        Employee $employee,
        float $amount,
        \DateTime $advanceDate,
        ?string $notes = null
    ): EmployeeAdvance {
        return EmployeeAdvance::create([
            'employee_id' => $employee->id,
            'amount' => round($amount, 2),
            'advance_date' => $advanceDate,
            'status' => 'ACTIVE',
            'balance' => round($amount, 2),
            'notes' => $notes,
        ]);
    }

    /**
     * Settle staff advance
     */
    public function settleAdvance(EmployeeAdvance $advance, \DateTime $settlementDate, float $settlementAmount): EmployeeAdvance
    {
        $newBalance = round($advance->balance - $settlementAmount, 2);
        $status = $newBalance <= 0 ? 'SETTLED' : 'ACTIVE';

        $advance->update([
            'status' => $status,
            'balance' => max(0, $newBalance),
        ]);

        return $advance;
    }

    /**
     * Record shortage deduction for a staff member
     * Called when shift has stock variance exceeding tolerance
     * Uses EmployeeAdjustment model with TYPE_SHORTAGE_RECOVERY
     */
    public function recordShortageDeduction(
        Employee $employee,
        Shift $shift,
        float $shortageAmount,
        string $reason,
        int $branchId
    ): \App\Models\EmployeeAdjustment {
        return DB::transaction(function () use (
            $employee,
            $shift,
            $shortageAmount,
            $reason,
            $branchId
        ) {
            $adjustment = \App\Models\EmployeeAdjustment::create([
                'branch_id' => $branchId,
                'employee_id' => $employee->id,
                'type' => 'SHORTAGE_RECOVERY',
                'amount' => round($shortageAmount, 2),
                'effective_date' => now()->toDateString(),
                'payroll_month' => now()->format('Y-m'),
                'shift_id' => $shift->id,
                'reason' => $reason,
                'is_applied' => false,
            ]);

            Log::info("Shortage deduction recorded for {$employee->name}", [
                'employee_id' => $employee->id,
                'shift_id' => $shift->id,
                'amount' => $shortageAmount,
            ]);

            return $adjustment;
        });
    }

    /**
     * Approve a shortage deduction
     */
    public function approveShortageDeduction(\App\Models\EmployeeAdjustment $adjustment, int $approvedById): \App\Models\EmployeeAdjustment
    {
        $adjustment->update([
            'is_applied' => true,
            'approved_by' => $approvedById,
        ]);

        return $adjustment;
    }

    /**
     * Generate salary sheet for an employee for a given month
     * Formula: Base + Overtime - Advances - Shortages - Other Deductions = Net
     */
    public function generateSalarySheet(
        Employee $employee,
        int $year,
        int $month,
        int $branchId
    ): EmployeeSalary {
        return DB::transaction(function () use ($employee, $year, $month, $branchId) {
            // Check if already generated
            $existing = EmployeeSalary::where('employee_id', $employee->id)
                ->where('payroll_month', sprintf('%04d-%02d', $year, $month))
                ->first();

            if ($existing) {
                return $existing;
            }

            // Calculate attendance-based overtime
            $overtimeAmount = $this->calculateOvertime($employee, $year, $month);

            // Get all pending advances to deduct
            $advanceDeduction = $employee->advances()
                ->where('status', 'ACTIVE')
                ->sum(DB::raw('CAST(balance AS DECIMAL(14,2))'));

            // Get all shortage deductions for this month
            $shortageDeduction = \App\Models\EmployeeAdjustment::where('employee_id', $employee->id)
                ->where('type', 'SHORTAGE_RECOVERY')
                ->where('payroll_month', sprintf('%04d-%02d', $year, $month))
                ->sum(DB::raw('CAST(amount AS DECIMAL(14,2))'));

            // Calculate net salary
            $baseSalary = round($employee->basic_salary, 2);
            $overtimeAmount = round($overtimeAmount, 2);
            $advanceDeduction = round($advanceDeduction, 2);
            $shortageDeduction = round($shortageDeduction, 2);
            $otherDeductions = 0; // Can be extended for custom deductions

            $netSalary = max(0, $baseSalary + $overtimeAmount - $advanceDeduction - $shortageDeduction - $otherDeductions);

            $salarySheet = EmployeeSalary::create([
                'branch_id' => $branchId,
                'employee_id' => $employee->id,
                'payroll_month' => sprintf('%04d-%02d', $year, $month),
                'salary_date' => now(),
                'basic_salary' => $baseSalary,
                'overtime_amount' => $overtimeAmount,
                'advance_deduction' => $advanceDeduction,
                'shortage_deduction' => $shortageDeduction,
                'other_deductions' => $otherDeductions,
                'net_salary' => $netSalary,
                'status' => 'GENERATED',
            ]);

            Log::info("Salary sheet generated for {$employee->name}", [
                'employee_id' => $employee->id,
                'month' => $month,
                'year' => $year,
                'net_salary' => $netSalary,
            ]);

            return $salarySheet;
        });
    }

    /**
     * Calculate overtime amount based on attendance
     * Assumption: Standard 8-hour day, overtime is 1.5x rate per hour
     */
    private function calculateOvertime(Employee $employee, int $year, int $month): float
    {
        $startDate = Carbon::create($year, $month, 1);
        $endDate = $startDate->copy()->endOfMonth();

        // Get all attendance records for the month
        $attendances = EmployeeAttendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$startDate, $endDate])
            ->get();

        $totalOvertimeHours = 0;
        $standardHoursPerDay = 8;

        foreach ($attendances as $attendance) {
            // Only count PRESENT and HALF_DAY for overtime calculation
            if (in_array($attendance->status, ['PRESENT', 'HALF_DAY'])) {
                $workingHours = $attendance->working_hours ?? 0;
                if ($workingHours > $standardHoursPerDay) {
                    $totalOvertimeHours += $workingHours - $standardHoursPerDay;
                }
            }
        }

        // Overtime rate: 1.5x hourly rate
        // Hourly rate = basic_salary / 208 (26 working days * 8 hours)
        $hourlyRate = $employee->basic_salary / 208;
        $overtimeRate = $hourlyRate * 1.5;

        return $totalOvertimeHours * $overtimeRate;
    }

    /**
     * Mark salary as paid
     */
    public function markSalaryAsPaid(
        EmployeeSalary $salarySheet,
        \DateTime $paymentDate,
        ?string $notes = null
    ): EmployeeSalary {
        $salarySheet->update([
            'status' => 'PAID',
            'paid_date' => $paymentDate,
        ]);

        Log::info("Salary marked as paid", [
            'employee_id' => $salarySheet->employee_id,
            'amount' => $salarySheet->net_salary,
            'date' => $paymentDate,
        ]);

        return $salarySheet;
    }

    /**
     * Get attendance summary for an employee in a date range
     */
    public function getAttendanceSummary(Employee $employee, Carbon $fromDate, Carbon $toDate): array
    {
        $attendances = EmployeeAttendance::where('employee_id', $employee->id)
            ->whereBetween('attendance_date', [$fromDate, $toDate])
            ->get();

        $summary = [
            'present' => 0,
            'absent' => 0,
            'half_day' => 0,
            'leave' => 0,
            'total_days' => 0,
            'total_working_hours' => 0,
        ];

        foreach ($attendances as $attendance) {
            $summary['total_days']++;
            $summary['total_working_hours'] += $attendance->working_hours ?? 0;

            match ($attendance->status) {
                'PRESENT' => $summary['present']++,
                'ABSENT' => $summary['absent']++,
                'HALF_DAY' => $summary['half_day']++,
                'LEAVE' => $summary['leave']++,
                default => null,
            };
        }

        return $summary;
    }

    /**
     * Get payroll summary for the entire staff for a given month
     */
    public function getPayrollSummary(int $year, int $month, ?int $branchId = null): array
    {
        $payrollMonth = sprintf('%04d-%02d', $year, $month);
        $query = EmployeeSalary::where('payroll_month', $payrollMonth);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $salarySheets = $query->get();

        $summary = [
            'total_employees' => $salarySheets->count(),
            'total_base_salary' => 0,
            'total_overtime' => 0,
            'total_advances_deducted' => 0,
            'total_shortage_deductions' => 0,
            'total_net_salary' => 0,
            'paid_count' => 0,
            'pending_count' => 0,
        ];

        foreach ($salarySheets as $sheet) {
            $summary['total_base_salary'] += $sheet->basic_salary;
            $summary['total_overtime'] += $sheet->overtime_amount;
            $summary['total_advances_deducted'] += $sheet->advance_deduction;
            $summary['total_shortage_deductions'] += $sheet->shortage_deduction;
            $summary['total_net_salary'] += $sheet->net_salary;

            if ($sheet->status === 'PAID') {
                $summary['paid_count']++;
            } elseif ($sheet->status === 'GENERATED') {
                $summary['pending_count']++;
            }
        }

        // Round all amounts to 2 decimals
        foreach (['total_base_salary', 'total_overtime', 'total_advances_deducted', 'total_shortage_deductions', 'total_net_salary'] as $key) {
            $summary[$key] = round($summary[$key], 2);
        }

        return $summary;
    }

    /**
     * Get shortage deductions for an employee in a date range
     */
    public function getShortageHistory(Employee $employee, Carbon $fromDate, Carbon $toDate): Collection
    {
        return \App\Models\EmployeeAdjustment::where('employee_id', $employee->id)
            ->where('type', 'SHORTAGE_RECOVERY')
            ->whereBetween('effective_date', [$fromDate, $toDate])
            ->orderByDesc('effective_date')
            ->get();
    }

    /**
     * Calculate expected staff for a shift based on shift type
     * Can be used to determine if staff shortage should trigger deductions
     */
    public function getExpectedStaffForShift(string $shiftType): int
    {
        $expectedStaff = [
            'MORNING' => 3,
            'EVENING' => 2,
            'NIGHT' => 2,
            'MIXED' => 4,
        ];

        return $expectedStaff[$shiftType] ?? 2;
    }

    /**
     * Check if a shift is understaffed and needs deductions
     */
    public function isShiftUnderstaffed(Shift $shift): bool
    {
        $expectedStaff = $this->getExpectedStaffForShift($shift->shift_type ?? 'MIXED');
        $actualStaff = $shift->nozzles()->count(); // Number of assigned nozzles/staff

        return $actualStaff < $expectedStaff;
    }
}
