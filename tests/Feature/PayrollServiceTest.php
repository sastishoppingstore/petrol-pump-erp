<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeSalary;
use App\Services\Payroll\PayrollService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayrollServiceTest extends TestCase
{
    use RefreshDatabase;

    private PayrollService $payrollService;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->payrollService = app(PayrollService::class);

        // Create test employee
        $this->employee = Employee::factory()->create([
            'code' => 'EMP001',
            'name' => 'Test Employee',
            'basic_salary' => 50000,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test recording employee attendance
     */
    public function test_record_attendance_present()
    {
        $today = now();
        $checkIn = Carbon::now()->setHours(8, 0);
        $checkOut = Carbon::now()->setHours(17, 0);

        $attendance = $this->payrollService->recordAttendance(
            $this->employee,
            $today,
            'PRESENT',
            $checkIn,
            $checkOut,
            'Regular work day'
        );

        $this->assertNotNull($attendance->id);
        $this->assertEquals('PRESENT', $attendance->status);
        $this->assertEquals(9, $attendance->working_hours); // 9 hours between 8am and 5pm
        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $this->employee->id,
            'attendance_date' => $today->toDateString(),
            'status' => 'PRESENT',
        ]);
    }

    /**
     * Test updating existing attendance
     */
    public function test_update_existing_attendance()
    {
        $today = now();

        // Create initial attendance
        $this->payrollService->recordAttendance(
            $this->employee,
            $today,
            'PRESENT',
            null,
            null
        );

        // Update it
        $updated = $this->payrollService->recordAttendance(
            $this->employee,
            $today,
            'HALF_DAY',
            null,
            null
        );

        $this->assertEquals('HALF_DAY', $updated->status);

        // Should only have 1 record
        $this->assertEquals(1, EmployeeAttendance::where('employee_id', $this->employee->id)->count());
    }

    /**
     * Test recording staff advance
     */
    public function test_record_staff_advance()
    {
        $advance = $this->payrollService->recordAdvance(
            $this->employee,
            5000,
            now(),
            'Employee requested advance'
        );

        $this->assertNotNull($advance->id);
        $this->assertEquals(5000, $advance->amount);
        $this->assertEquals('ACTIVE', $advance->status);
        $this->assertEquals(5000, $advance->balance);
        $this->assertDatabaseHas('employee_advances', [
            'employee_id' => $this->employee->id,
            'amount' => 5000,
        ]);
    }

    /**
     * Test settling staff advance
     */
    public function test_settle_staff_advance()
    {
        $advance = $this->payrollService->recordAdvance($this->employee, 5000, now());

        // Partial settlement
        $settled = $this->payrollService->settleAdvance($advance, now(), 2000);
        $this->assertEquals(3000, $settled->balance);
        $this->assertEquals('ACTIVE', $settled->status);

        // Full settlement
        $fullySettled = $this->payrollService->settleAdvance($settled, now(), 3000);
        $this->assertEquals(0, $fullySettled->balance);
        $this->assertEquals('SETTLED', $fullySettled->status);
    }

    /**
     * Test attendance summary calculation
     */
    public function test_get_attendance_summary()
    {
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->endOfMonth();

        // Record various attendance statuses
        $this->payrollService->recordAttendance($this->employee, $startDate->copy()->addDays(0), 'PRESENT', null, null);
        $this->payrollService->recordAttendance($this->employee, $startDate->copy()->addDays(1), 'PRESENT', null, null);
        $this->payrollService->recordAttendance($this->employee, $startDate->copy()->addDays(2), 'ABSENT', null, null);
        $this->payrollService->recordAttendance($this->employee, $startDate->copy()->addDays(3), 'HALF_DAY', null, null);
        $this->payrollService->recordAttendance($this->employee, $startDate->copy()->addDays(4), 'LEAVE', null, null);

        $summary = $this->payrollService->getAttendanceSummary($this->employee, $startDate, $endDate);

        $this->assertEquals(2, $summary['present']);
        $this->assertEquals(1, $summary['absent']);
        $this->assertEquals(1, $summary['half_day']);
        $this->assertEquals(1, $summary['leave']);
        $this->assertEquals(5, $summary['total_days']);
    }

    /**
     * Test salary sheet generation
     */
    public function test_generate_salary_sheet()
    {
        $year = 2024;
        $month = 10;
        $branchId = 1;

        // Record some attendance (8 hours = 1 day)
        for ($day = 1; $day <= 26; $day++) {
            $date = Carbon::create($year, $month, $day);
            if ($date->isBetween(now()->startOfMonth(), now()->endOfMonth())) {
                $checkIn = $date->copy()->setHours(8, 0);
                $checkOut = $date->copy()->setHours(17, 0);
                $this->payrollService->recordAttendance(
                    $this->employee,
                    $date,
                    'PRESENT',
                    $checkIn,
                    $checkOut
                );
            }
        }

        $salary = $this->payrollService->generateSalarySheet(
            $this->employee,
            $year,
            $month,
            $branchId
        );

        $this->assertNotNull($salary->id);
        $this->assertEquals($this->employee->id, $salary->employee_id);
        $this->assertEquals('2024-10', $salary->payroll_month);
        $this->assertEquals(50000, $salary->basic_salary);
        $this->assertGreaterThan(0, $salary->overtime_amount);
        $this->assertEquals('GENERATED', $salary->status);
    }

    /**
     * Test net salary calculation with deductions
     */
    public function test_salary_calculation_with_advances()
    {
        $year = 2024;
        $month = 10;
        $branchId = 1;

        // Record advance
        $this->payrollService->recordAdvance($this->employee, 3000, now());

        // Generate salary
        $salary = $this->payrollService->generateSalarySheet(
            $this->employee,
            $year,
            $month,
            $branchId
        );

        $this->assertEquals(3000, $salary->advance_deduction);
        $this->assertLessThan($salary->basic_salary, $salary->net_salary);
    }

    /**
     * Test marking salary as paid
     */
    public function test_mark_salary_as_paid()
    {
        $salary = EmployeeSalary::factory()->create([
            'employee_id' => $this->employee->id,
            'status' => 'GENERATED',
        ]);

        $paymentDate = now();
        $updated = $this->payrollService->markSalaryAsPaid($salary, $paymentDate);

        $this->assertEquals('PAID', $updated->status);
        $this->assertNotNull($updated->paid_date);
    }

    /**
     * Test payroll summary
     */
    public function test_get_payroll_summary()
    {
        $year = 2024;
        $month = 10;
        $branchId = 1;

        // Create 3 employees and generate salaries
        $employees = Employee::factory(3)->create(['status' => 'ACTIVE']);

        foreach ($employees as $emp) {
            $this->payrollService->generateSalarySheet($emp, $year, $month, $branchId);
        }

        $summary = $this->payrollService->getPayrollSummary($year, $month, $branchId);

        $this->assertEquals(3, $summary['total_employees']);
        $this->assertGreaterThan(0, $summary['total_base_salary']);
        $this->assertGreaterThan(0, $summary['total_net_salary']);
        $this->assertEquals(3, $summary['pending_count']);
        $this->assertEquals(0, $summary['paid_count']);
    }

    /**
     * Test shortage history retrieval
     */
    public function test_get_shortage_history()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        // Shortages would be added via EmployeeAdjustment model
        // This test validates the retrieval mechanism
        $history = $this->payrollService->getShortageHistory($this->employee, $startDate, $endDate);

        $this->assertIsIterable($history);
        $this->assertEmpty($history);
    }

    /**
     * Test expected staff calculation
     */
    public function test_expected_staff_for_shift()
    {
        $this->assertEquals(3, $this->payrollService->getExpectedStaffForShift('MORNING'));
        $this->assertEquals(2, $this->payrollService->getExpectedStaffForShift('EVENING'));
        $this->assertEquals(2, $this->payrollService->getExpectedStaffForShift('NIGHT'));
        $this->assertEquals(4, $this->payrollService->getExpectedStaffForShift('MIXED'));
    }
}
