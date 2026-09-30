<?php

namespace App\Services\Payroll;

use App\Models\BankAccount;
use App\Models\BankTransaction;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\EmployeeAdjustment;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Shift;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\System\NumberSequenceService;
use App\Support\Decimal;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(
        private readonly AuditLogService $audit,
        private readonly NumberSequenceService $sequences,
    ) {
    }

    /**
     * Create Employee.
     */
    public function createEmployee(User $actor, array $data): Employee
    {
        $branchId = $data['branch_id'] ?? 1;
        $code = $data['code'] ?? ('EMP-' . str_pad((string) (Employee::max('id') + 1), 4, '0', STR_PAD_LEFT));

        $employee = Employee::create([
            'branch_id' => $branchId,
            'user_id' => $data['user_id'] ?? null,
            'code' => $code,
            'name' => $data['name'],
            'designation' => $data['designation'] ?? 'Pump Attendant',
            'phone' => $data['phone'] ?? null,
            'cnic' => $data['cnic'] ?? null,
            'joining_date' => $data['joining_date'] ?? today()->toDateString(),
            'basic_salary' => Money::round(Money::n($data['basic_salary'] ?? '0.00')),
            'daily_wage' => Money::round(Money::n($data['daily_wage'] ?? '0.00')),
            'status' => Employee::STATUS_ACTIVE,
        ]);

        $this->audit->record(
            userId: $actor->id,
            action: 'employee_created',
            module: 'payroll',
            referenceType: Employee::class,
            referenceId: $employee->id,
            newData: ['name' => $employee->name, 'code' => $employee->code],
        );

        return $employee;
    }

    /**
     * Record single employee attendance.
     */
    public function recordAttendance(
        User $actor,
        int $employeeId,
        string $date,
        string $status,
        ?string $inTime = null,
        ?string $outTime = null,
        ?string $notes = null,
    ): EmployeeAttendance {
        $employee = Employee::findOrFail($employeeId);

        $validStatuses = [
            EmployeeAttendance::STATUS_PRESENT,
            EmployeeAttendance::STATUS_ABSENT,
            EmployeeAttendance::STATUS_LEAVE,
            EmployeeAttendance::STATUS_HALF_DAY,
        ];

        if (! in_array($status, $validStatuses, true)) {
            throw ValidationException::withMessages(['status' => 'Invalid attendance status.']);
        }

        return EmployeeAttendance::updateOrCreate(
            ['employee_id' => $employee->id, 'date' => $date],
            [
                'branch_id' => $employee->branch_id,
                'status' => $status,
                'in_time' => $inTime,
                'out_time' => $outTime,
                'notes' => $notes,
                'recorded_by' => $actor->id,
            ]
        );
    }

    /**
     * Bulk save attendance for a branch on a given date.
     * $records format: [employee_id => status, ...]
     */
    public function bulkAttendance(User $actor, int $branchId, string $date, array $records): int
    {
        return DB::transaction(function () use ($actor, $branchId, $date, $records) {
            $count = 0;
            foreach ($records as $empId => $status) {
                $employee = Employee::where('id', $empId)->where('branch_id', $branchId)->first();
                if ($employee) {
                    $this->recordAttendance($actor, $employee->id, $date, $status);
                    $count++;
                }
            }

            return $count;
        });
    }

    /**
     * Issue an advance (loan) to staff.
     */
    public function giveAdvance(
        User $actor,
        int $employeeId,
        string $amount,
        string $paymentMethod = EmployeeAdvance::METHOD_CASH,
        ?int $bankAccountId = null,
        ?int $shiftId = null,
        string $monthlyDeduction = '0.00',
        ?string $reason = null,
        ?string $date = null,
    ): EmployeeAdvance {
        $employee = Employee::findOrFail($employeeId);
        $amount = Money::round(Money::n($amount));
        $monthlyDeduction = Money::round(Money::n($monthlyDeduction));

        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages(['amount' => 'Advance amount must be greater than zero.']);
        }

        return DB::transaction(function () use (
            $actor, $employee, $amount, $paymentMethod, $bankAccountId, $shiftId, $monthlyDeduction, $reason, $date
        ) {
            $advanceDate = $date ?: today()->toDateString();

            $advance = EmployeeAdvance::create([
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'amount' => $amount,
                'balance' => $amount,
                'monthly_deduction' => $monthlyDeduction,
                'advance_date' => $advanceDate,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'shift_id' => $shiftId,
                'reason' => $reason,
                'status' => EmployeeAdvance::STATUS_ACTIVE,
                'approved_by' => $actor->id,
            ]);

            // Deduct cash from till or bank account
            if ($paymentMethod === EmployeeAdvance::METHOD_CASH && $shiftId) {
                DB::table('shift_cash')->insert([
                    'shift_id' => $shiftId,
                    'entry_type' => 'STAFF_ADVANCE',
                    'amount' => $amount,
                    'notes' => "Advance to {$employee->name}: {$reason}",
                    'user_id' => $actor->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } elseif ($paymentMethod === EmployeeAdvance::METHOD_BANK_TRANSFER && $bankAccountId) {
                $account = BankAccount::findOrFail($bankAccountId);
                $before = $account->currentBalance();
                $after = Money::subtract($before, $amount);

                BankTransaction::create([
                    'branch_id' => $employee->branch_id,
                    'bank_account_id' => $account->id,
                    'type' => BankTransaction::TYPE_WITHDRAWAL,
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'reference_number' => 'ADV-' . $advance->id,
                    'transaction_date' => Carbon::parse($advanceDate),
                    'description' => "Staff Advance paid to {$employee->name}",
                    'performed_by' => $actor->id,
                    'status' => BankTransaction::STATUS_COMPLETED,
                ]);
            }

            $this->audit->record(
                userId: $actor->id,
                action: 'staff_advance',
                module: 'payroll',
                referenceType: EmployeeAdvance::class,
                referenceId: $advance->id,
                newData: [
                    'employee' => $employee->name,
                    'amount' => $amount,
                    'method' => $paymentMethod,
                ],
            );

            return $advance;
        });
    }

    /**
     * Record employee adjustments: Overtime, bonus, fine, cashier shortage recovery.
     */
    public function recordAdjustment(
        User $actor,
        int $employeeId,
        string $type,
        string $amount,
        string $effectiveDate,
        string $payrollMonth,
        ?int $shiftId = null,
        string $reason = '',
    ): EmployeeAdjustment {
        $employee = Employee::findOrFail($employeeId);
        $amount = Money::round(Money::n($amount));

        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages(['amount' => 'Adjustment amount must be greater than zero.']);
        }

        $validTypes = [
            EmployeeAdjustment::TYPE_OVERTIME,
            EmployeeAdjustment::TYPE_BONUS,
            EmployeeAdjustment::TYPE_FINE,
            EmployeeAdjustment::TYPE_SHORTAGE_RECOVERY,
        ];

        if (! in_array($type, $validTypes, true)) {
            throw ValidationException::withMessages(['type' => 'Invalid adjustment type.']);
        }

        return EmployeeAdjustment::create([
            'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id,
            'type' => $type,
            'amount' => $amount,
            'effective_date' => $effectiveDate,
            'payroll_month' => $payrollMonth,
            'shift_id' => $shiftId,
            'reason' => $reason,
            'is_applied' => false,
            'approved_by' => $actor->id,
        ]);
    }

    /**
     * Generate Monthly Salary Sheet for a branch and month (YYYY-MM).
     */
    public function generateMonthlySalarySheet(User $actor, int $branchId, string $month): \Illuminate\Support\Collection
    {
        $carbonMonth = Carbon::parse($month . '-01');
        $daysInMonth = $carbonMonth->daysInMonth;
        $startDate = $carbonMonth->startOfMonth()->toDateString();
        $endDate = $carbonMonth->endOfMonth()->toDateString();

        $employees = Employee::query()
            ->where('branch_id', $branchId)
            ->where('status', Employee::STATUS_ACTIVE)
            ->get();

        $sheet = collect();

        foreach ($employees as $employee) {
            $attendances = EmployeeAttendance::query()
                ->where('employee_id', $employee->id)
                ->whereBetween('date', [$startDate, $endDate])
                ->get();

            $pCount = $attendances->where('status', EmployeeAttendance::STATUS_PRESENT)->count();
            $aCount = $attendances->where('status', EmployeeAttendance::STATUS_ABSENT)->count();
            $hCount = $attendances->where('status', EmployeeAttendance::STATUS_HALF_DAY)->count();
            $lCount = $attendances->where('status', EmployeeAttendance::STATUS_LEAVE)->count();

            // If no attendance recorded at all in ERP, treat as full attendance
            if ($attendances->isEmpty()) {
                $pCount = $daysInMonth;
            }

            $basic = Money::n($employee->basic_salary);
            $dailyRate = Decimal::divide($basic, (string) $daysInMonth, 2);

            // Deduct for explicit absences (absent days + 0.5 * half-days)
            $deductibleDays = $aCount + ($hCount * 0.5);
            $absentDeduction = $deductibleDays > 0
                ? Money::round(Decimal::multiply($dailyRate, (string) $deductibleDays, 2))
                : '0.00';
            $earnedSalary = Money::subtract($basic, $absentDeduction);

            // Adjustments for month
            $adjustments = EmployeeAdjustment::query()
                ->where('employee_id', $employee->id)
                ->where('payroll_month', $month)
                ->get();

            $overtime = Money::n($adjustments->where('type', EmployeeAdjustment::TYPE_OVERTIME)->sum('amount'));
            $bonus = Money::n($adjustments->where('type', EmployeeAdjustment::TYPE_BONUS)->sum('amount'));
            $fine = Money::n($adjustments->whereIn('type', [
                EmployeeAdjustment::TYPE_FINE,
                EmployeeAdjustment::TYPE_SHORTAGE_RECOVERY,
            ])->sum('amount'));

            // Advance loan deduction
            $activeAdvances = EmployeeAdvance::query()
                ->where('employee_id', $employee->id)
                ->where('status', EmployeeAdvance::STATUS_ACTIVE)
                ->get();

            $advanceDeduction = '0.00';
            foreach ($activeAdvances as $adv) {
                $deduct = Money::n($adv->monthly_deduction);
                if (Money::compare($deduct, '0.00') <= 0) {
                    $deduct = $adv->balance;
                }
                $deduct = Money::compare($deduct, $adv->balance) > 0 ? $adv->balance : $deduct;
                $advanceDeduction = Money::add($advanceDeduction, $deduct);
            }

            // Net salary = earned + overtime + bonus - fine - advanceDeduction
            $allowances = Money::add($overtime, $bonus);
            $deductions = Money::add($fine, $advanceDeduction);
            $gross = Money::add($earnedSalary, $allowances);
            $net = Money::subtract($gross, $deductions);
            if (Money::isNegative($net)) {
                $net = '0.00';
            }

            $salaryRecord = EmployeeSalary::updateOrCreate(
                [
                    'branch_id' => $branchId,
                    'employee_id' => $employee->id,
                    'month' => $month,
                ],
                [
                    'present_days' => $pCount,
                    'absent_days' => $aCount,
                    'half_days' => $hCount,
                    'leave_days' => $lCount,
                    'basic_salary' => $basic,
                    'overtime_amount' => $overtime,
                    'bonus_amount' => $bonus,
                    'fine_amount' => $fine,
                    'advance_deduction' => $advanceDeduction,
                    'allowances' => $allowances,
                    'deductions' => $deductions,
                    'net_salary' => $net,
                    'status' => EmployeeSalary::STATUS_PENDING,
                    'created_by' => $actor->id,
                ]
            );

            $sheet->push($salaryRecord);
        }

        $this->audit->record(
            userId: $actor->id,
            action: 'salary_sheet_generated',
            module: 'payroll',
            referenceType: EmployeeSalary::class,
            referenceId: null,
            newData: ['month' => $month, 'employee_count' => $employees->count()],
        );

        return $sheet;
    }

    /**
     * Post Salary Payment to Cash/Bank + Expense.
     */
    public function paySalary(
        User $actor,
        EmployeeSalary $salary,
        string $paymentMethod = EmployeeSalary::METHOD_CASH,
        ?int $bankAccountId = null,
        ?int $shiftId = null,
        ?string $notes = null,
    ): EmployeeSalary {
        if ($salary->isPaid()) {
            throw ValidationException::withMessages(['status' => 'This salary has already been paid.']);
        }

        return DB::transaction(function () use ($actor, $salary, $paymentMethod, $bankAccountId, $shiftId, $notes) {
            $locked = EmployeeSalary::query()->whereKey($salary->id)->lockForUpdate()->firstOrFail();
            $employee = $locked->employee;
            $amount = $locked->net_salary;
            $payDate = today()->toDateString();

            // 1. Post to Cash or Bank
            if ($paymentMethod === EmployeeSalary::METHOD_CASH) {
                if ($shiftId) {
                    DB::table('shift_cash')->insert([
                        'shift_id' => $shiftId,
                        'entry_type' => 'SALARY_PAYMENT',
                        'amount' => $amount,
                        'notes' => "Salary payment for {$locked->month} to {$employee->name}",
                        'user_id' => $actor->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            } elseif ($paymentMethod === EmployeeSalary::METHOD_BANK_TRANSFER && $bankAccountId) {
                $account = BankAccount::findOrFail($bankAccountId);
                $before = $account->currentBalance();
                $after = Money::subtract($before, $amount);

                BankTransaction::create([
                    'branch_id' => $locked->branch_id,
                    'bank_account_id' => $account->id,
                    'type' => BankTransaction::TYPE_WITHDRAWAL,
                    'amount' => $amount,
                    'balance_before' => $before,
                    'balance_after' => $after,
                    'reference_number' => "SAL-{$locked->month}-{$employee->id}",
                    'transaction_date' => Carbon::parse($payDate),
                    'description' => "Salary payment for {$locked->month} to {$employee->name}",
                    'performed_by' => $actor->id,
                    'status' => BankTransaction::STATUS_COMPLETED,
                ]);
            }

            // 2. Post to Expenses under Salaries category
            $salaryCategory = ExpenseCategory::firstOrCreate(
                ['code' => 'EXP-SAL'],
                ['name' => 'Salaries', 'urdu_name' => 'تنخواہیں', 'status' => ExpenseCategory::STATUS_ACTIVE]
            );

            Expense::create([
                'branch_id' => $locked->branch_id,
                'category_id' => $salaryCategory->id,
                'expense_number' => "EXP-SAL-{$locked->month}-{$employee->id}",
                'date' => $payDate,
                'title' => "Staff Salary: {$employee->name} ({$locked->month})",
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'shift_id' => $shiftId,
                'payee' => $employee->name,
                'status' => Expense::STATUS_PAID,
                'created_by' => $actor->id,
                'approved_by' => $actor->id,
                'notes' => $notes ?: "Monthly salary payout for {$locked->month}",
            ]);

            // 3. Recover advances
            if (Money::compare($locked->advance_deduction, '0.00') > 0) {
                $activeAdvances = EmployeeAdvance::query()
                    ->where('employee_id', $employee->id)
                    ->where('status', EmployeeAdvance::STATUS_ACTIVE)
                    ->get();

                $toRecover = $locked->advance_deduction;
                foreach ($activeAdvances as $adv) {
                    if (Money::compare($toRecover, '0.00') <= 0) {
                        break;
                    }

                    $cut = Money::compare($toRecover, $adv->balance) > 0 ? $adv->balance : $toRecover;
                    $newBalance = Money::subtract($adv->balance, $cut);
                    $adv->update([
                        'balance' => $newBalance,
                        'status' => Money::isZero($newBalance) ? EmployeeAdvance::STATUS_RECOVERED : EmployeeAdvance::STATUS_ACTIVE,
                    ]);

                    $toRecover = Money::subtract($toRecover, $cut);
                }
            }

            // 4. Mark adjustments as applied
            EmployeeAdjustment::query()
                ->where('employee_id', $employee->id)
                ->where('payroll_month', $locked->month)
                ->update(['is_applied' => true]);

            // 5. Update salary status
            $locked->update([
                'paid_amount' => $amount,
                'payment_date' => $payDate,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'shift_id' => $shiftId,
                'status' => EmployeeSalary::STATUS_PAID,
                'notes' => $notes,
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: 'salary_paid',
                module: 'payroll',
                referenceType: EmployeeSalary::class,
                referenceId: $locked->id,
                newData: [
                    'employee' => $employee->name,
                    'amount' => $amount,
                    'method' => $paymentMethod,
                    'month' => $locked->month,
                ],
            );

            return $locked->fresh();
        });
    }

    /**
     * Payslip data for view / PDF generation.
     */
    public function getPayslipData(EmployeeSalary $salary): array
    {
        $employee = $salary->employee;
        $branch = $salary->branch;

        return [
            'salary' => $salary,
            'employee' => $employee,
            'branch' => $branch,
            'station_name' => 'Vital Petroleum — Mehar Filling Station',
            'station_address' => 'GT Road, Sheikhupura, Punjab',
            'net_salary_formatted' => PakistaniCurrency::format($salary->net_salary),
            'net_salary_in_words' => PakistaniCurrency::toUrduWords($salary->net_salary),
        ];
    }
}
