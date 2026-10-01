<?php

namespace App\Services\Payroll;

use App\Models\BankAccount;
use App\Models\Employee;
use App\Models\EmployeeAdjustment;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use App\Models\Shift;
use App\Models\ShiftCash;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Support\AmountInWords;
use App\Support\Money;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Payroll business logic for the employees.* screens (EmployeeController).
 *
 * SCHEMA NOTE (2026-10 sweep fix): this service was originally written
 * against a phantom schema and called columns that do not exist
 * (employee_salaries.payroll_month / salary_date / paid_date,
 * employee_attendances.attendance_date / check_in_time / working_hours,
 * salary status GENERATED). The REAL migrated schema — the same one
 * PayrollController (payroll.*) uses — is:
 *
 *   employees:            code (unique), basic_salary, daily_wage, status
 *   employee_salaries:    month (YYYY-MM), present_days, absent_days,
 *                         half_days, leave_days, basic_salary,
 *                         overtime_amount, bonus_amount, fine_amount,
 *                         advance_deduction, allowances, deductions,
 *                         net_salary, paid_amount, payment_date,
 *                         payment_method, bank_account_id, shift_id,
 *                         status (PAID|PENDING), created_by
 *                         + UNIQUE(employee_id, month)
 *   employee_attendances: date, in_time, out_time, status
 *                         (PRESENT|ABSENT|LEAVE|HALF_DAY), recorded_by
 *                         + UNIQUE(employee_id, date)
 *   employee_advances:    amount, balance, monthly_deduction, advance_date,
 *                         payment_method, bank_account_id, shift_id,
 *                         reason, status (ACTIVE|RECOVERED|CANCELLED)
 *   employee_adjustments: type (OVERTIME|BONUS|FINE|SHORTAGE_RECOVERY),
 *                         amount, effective_date, payroll_month (YYYY-MM —
 *                         this table really has it), shift_id, reason,
 *                         is_applied, approved_by
 *
 * Salary formula (identical to PayrollController::generateSalarySheets):
 *   Net = Basic + Overtime + Bonus − Advance Deduction − Fine − Shortage
 *   deductions column = Fine + Shortage
 * Existing salary sheets are NEVER overwritten (append-only history).
 */
class PayrollService
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {
    }

    /* =====================================================================
     | EmployeeController API
     ===================================================================== */

    /**
     * Register a new employee. Generates the unique employee code
     * (EMP-0001, EMP-0002, …) because `employees.code` is NOT NULL + unique
     * and the create form does not ask for one.
     */
    public function createEmployee(User $actor, array $data): Employee
    {
        return DB::transaction(function () use ($actor, $data) {
            $seq = (int) (Employee::query()->max('id') ?? 0) + 1;
            do {
                $code = 'EMP-' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
                $seq++;
            } while (Employee::query()->where('code', $code)->exists());

            $employee = Employee::create([
                'branch_id' => $data['branch_id'],
                'code' => $code,
                'name' => $data['name'],
                'designation' => $data['designation'],
                'phone' => $data['phone'] ?? null,
                'cnic' => $data['cnic'] ?? null,
                'joining_date' => $data['joining_date'] ?? null,
                'basic_salary' => Money::round(Money::n($data['basic_salary'] ?? 0)),
                'daily_wage' => Money::round(Money::n($data['daily_wage'] ?? 0)),
                'status' => $data['status'] ?? Employee::STATUS_ACTIVE,
            ]);

            $this->audit->record(
                userId: $actor->id,
                action: 'employee_created',
                module: 'employees',
                referenceType: Employee::class,
                referenceId: $employee->id,
                newData: ['code' => $employee->code, 'name' => $employee->name],
            );

            return $employee;
        });
    }

    /**
     * Save one day's attendance grid (employee_id => status).
     * Only employees of the given branch are processed; returns how many
     * rows were saved. Re-saving the same date updates the row
     * (UNIQUE employee_id + date).
     *
     * @param  array<int|string, string>  $records
     */
    public function bulkAttendance(User $actor, int $branchId, string $date, array $records): int
    {
        $date = Carbon::parse($date)->toDateString();
        $allowed = [
            EmployeeAttendance::STATUS_PRESENT,
            EmployeeAttendance::STATUS_ABSENT,
            EmployeeAttendance::STATUS_LEAVE,
            EmployeeAttendance::STATUS_HALF_DAY,
        ];

        $employeeIds = Employee::query()
            ->where('branch_id', $branchId)
            ->whereIn('id', array_map('intval', array_keys($records)))
            ->pluck('id')
            ->all();

        $count = 0;

        DB::transaction(function () use ($actor, $branchId, $date, $records, $allowed, $employeeIds, &$count) {
            foreach ($records as $employeeId => $status) {
                $employeeId = (int) $employeeId;
                if (! in_array($employeeId, $employeeIds, true) || ! in_array($status, $allowed, true)) {
                    continue;
                }

                EmployeeAttendance::updateOrCreate(
                    ['employee_id' => $employeeId, 'date' => $date],
                    [
                        'branch_id' => $branchId,
                        'status' => $status,
                        'recorded_by' => $actor->id,
                    ]
                );
                $count++;
            }
        });

        return $count;
    }

    /**
     * Issue an advance / loan to an employee. The advance starts ACTIVE
     * with balance = amount; it is recovered through salary sheets.
     * Money trail: BANK_TRANSFER posts a WITHDRAWAL bank transaction,
     * CASH taken from an open shift posts a shift_cash CASH_OUT entry
     * (so the shift's expected cash drops — same convention as expenses).
     */
    public function giveAdvance(
        User $actor,
        int $employeeId,
        string $amount,
        string $paymentMethod,
        ?int $bankAccountId,
        ?int $shiftId,
        string $monthlyDeduction,
        string $reason,
        ?string $date = null,
    ): EmployeeAdvance {
        $amount = Money::round(Money::n($amount));
        if (! Money::isPositive($amount)) {
            throw new RuntimeException('Advance amount must be greater than zero.');
        }

        $employee = Employee::query()->findOrFail($employeeId);
        $advanceDate = $date ? Carbon::parse($date)->toDateString() : now()->toDateString();

        return DB::transaction(function () use (
            $actor, $employee, $amount, $paymentMethod, $bankAccountId,
            $shiftId, $monthlyDeduction, $reason, $advanceDate
        ) {
            $advance = EmployeeAdvance::create([
                'branch_id' => $employee->branch_id,
                'employee_id' => $employee->id,
                'amount' => $amount,
                'balance' => $amount,
                'monthly_deduction' => Money::round(Money::n($monthlyDeduction)),
                'advance_date' => $advanceDate,
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'shift_id' => $shiftId,
                'reason' => $reason,
                'status' => EmployeeAdvance::STATUS_ACTIVE,
                'approved_by' => $actor->id,
            ]);

            $this->recordMoneyOutflow(
                branchId: $employee->branch_id,
                paymentMethod: $paymentMethod,
                bankAccountId: $bankAccountId,
                shiftId: $shiftId,
                amount: $amount,
                description: "Staff advance to {$employee->name} ({$employee->code})",
                reference: 'ADV-' . $advance->id,
                actorId: $actor->id,
            );

            $this->audit->record(
                userId: $actor->id,
                action: 'advance_given',
                module: 'payroll',
                referenceType: EmployeeAdvance::class,
                referenceId: $advance->id,
                newData: ['employee_id' => $employee->id, 'amount' => $amount, 'method' => $paymentMethod],
            );

            return $advance;
        });
    }

    /**
     * Record a manual payroll adjustment (overtime / bonus / fine /
     * shortage recovery) against an employee's payroll month. Salary
     * generation picks these up by (employee_id, payroll_month, type).
     */
    public function recordAdjustment(
        User $actor,
        int $employeeId,
        string $type,
        string $amount,
        string $effectiveDate,
        string $payrollMonth,
        ?int $shiftId,
        string $reason,
    ): EmployeeAdjustment {
        $amount = Money::round(Money::n($amount));
        if (! Money::isPositive($amount)) {
            throw new RuntimeException('Adjustment amount must be greater than zero.');
        }

        $employee = Employee::query()->findOrFail($employeeId);

        $adjustment = EmployeeAdjustment::create([
            'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id,
            'type' => $type,
            'amount' => $amount,
            'effective_date' => Carbon::parse($effectiveDate)->toDateString(),
            'payroll_month' => $payrollMonth,
            'shift_id' => $shiftId,
            'reason' => $reason,
            'is_applied' => true,
            'approved_by' => $actor->id,
        ]);

        $this->audit->record(
            userId: $actor->id,
            action: 'adjustment_recorded',
            module: 'payroll',
            referenceType: EmployeeAdjustment::class,
            referenceId: $adjustment->id,
            newData: ['employee_id' => $employee->id, 'type' => $type, 'amount' => $amount, 'month' => $payrollMonth],
        );

        return $adjustment;
    }

    /**
     * Generate the monthly salary sheet for every ACTIVE employee of the
     * branch. Employees who already have a sheet for the month keep it —
     * sheets are never overwritten. Returns the branch's full sheet for
     * the month (EmployeeController shows its count).
     *
     * @return Collection<int, EmployeeSalary>
     */
    public function generateMonthlySalarySheet(User $actor, int $branchId, string $month): Collection
    {
        if (! preg_match('/^\d{4}-\d{2}$/', $month)) {
            throw new RuntimeException('Payroll month must be in YYYY-MM format.');
        }

        DB::transaction(function () use ($actor, $branchId, $month) {
            $employees = Employee::query()
                ->where('branch_id', $branchId)
                ->where('status', Employee::STATUS_ACTIVE)
                ->orderBy('name')
                ->get();

            foreach ($employees as $employee) {
                $this->buildSalarySheet($employee, $month, $branchId, $actor->id);
            }
        });

        $this->audit->record(
            userId: $actor->id,
            action: 'payroll_generated',
            module: 'payroll',
            newData: ['branch_id' => $branchId, 'month' => $month],
        );

        return EmployeeSalary::query()
            ->with('employee')
            ->where('branch_id', $branchId)
            ->where('month', $month)
            ->orderBy('id')
            ->get();
    }

    /**
     * Pay one employee's salary sheet in full. Marks the sheet PAID,
     * settles the advances that were deducted in this sheet (oldest
     * first), and posts the money trail (bank withdrawal / shift cash
     * out) exactly like giveAdvance().
     */
    public function paySalary(
        User $actor,
        EmployeeSalary $salary,
        string $paymentMethod,
        ?int $bankAccountId,
        ?int $shiftId,
        ?string $notes = null,
    ): EmployeeSalary {
        if ($salary->status === EmployeeSalary::STATUS_PAID) {
            throw new RuntimeException("Salary for {$salary->month} has already been paid to {$salary->employee?->name}.");
        }

        return DB::transaction(function () use ($actor, $salary, $paymentMethod, $bankAccountId, $shiftId, $notes) {
            $salary->update([
                'status' => EmployeeSalary::STATUS_PAID,
                'paid_amount' => $salary->net_salary,
                'payment_date' => now()->toDateString(),
                'payment_method' => $paymentMethod,
                'bank_account_id' => $bankAccountId,
                'shift_id' => $shiftId ?? $salary->shift_id,
                'notes' => $notes ?? $salary->notes,
            ]);

            // Recover the advances this sheet deducted, oldest first.
            $this->settleAdvances($salary->employee, Money::n($salary->advance_deduction));

            $this->recordMoneyOutflow(
                branchId: $salary->branch_id,
                paymentMethod: $paymentMethod,
                bankAccountId: $bankAccountId,
                shiftId: $shiftId,
                amount: Money::n($salary->net_salary),
                description: "Salary {$salary->month} paid to {$salary->employee?->name} ({$salary->employee?->code})",
                reference: 'SAL-' . $salary->id,
                actorId: $actor->id,
            );

            $this->audit->record(
                userId: $actor->id,
                action: 'salary_paid',
                module: 'payroll',
                referenceType: EmployeeSalary::class,
                referenceId: $salary->id,
                newData: ['employee_id' => $salary->employee_id, 'month' => $salary->month, 'amount' => Money::n($salary->net_salary), 'method' => $paymentMethod],
            );

            return $salary->fresh();
        });
    }

    /**
     * Data for the printable payslip (employees/payslip.blade.php).
     * The view expects exactly: $salary, $employee, $net_salary_in_words
     * (Urdu words — the view renders it in an Urdu Nastaliq block).
     *
     * @return array{salary: EmployeeSalary, employee: Employee, net_salary_in_words: string}
     */
    public function getPayslipData(EmployeeSalary $salary): array
    {
        $salary->loadMissing(['employee', 'branch', 'bankAccount.bank', 'shift', 'creator']);

        return [
            'salary' => $salary,
            'employee' => $salary->employee,
            'net_salary_in_words' => AmountInWords::toUrdu(Money::n($salary->net_salary)),
        ];
    }

    /* =====================================================================
     | Salary sheet construction (shared by the two generate entry points)
     ===================================================================== */

    /**
     * Create (or return the existing) salary sheet for one employee and
     * month, using the PayrollController formula. Never overwrites.
     */
    private function buildSalarySheet(Employee $employee, string $monthString, int $branchId, ?int $createdBy): EmployeeSalary
    {
        $existing = EmployeeSalary::query()
            ->where('employee_id', $employee->id)
            ->where('month', $monthString)
            ->first();

        if ($existing) {
            return $existing;
        }

        $c = $this->payrollComponents($employee, $monthString);

        return EmployeeSalary::create([
            'branch_id' => $employee->branch_id ?? $branchId,
            'employee_id' => $employee->id,
            'month' => $monthString,
            'present_days' => $c['present_days'],
            'absent_days' => $c['absent_days'],
            'half_days' => $c['half_days'],
            'leave_days' => $c['leave_days'],
            'basic_salary' => $c['basic'],
            'overtime_amount' => $c['overtime'],
            'bonus_amount' => $c['bonus'],
            'fine_amount' => $c['fine'],
            'advance_deduction' => $c['advance_deduction'],
            'allowances' => '0.00',
            'deductions' => $c['deductions'],
            'net_salary' => $c['net'],
            'paid_amount' => '0.00',
            'status' => EmployeeSalary::STATUS_PENDING,
            'created_by' => $createdBy,
        ]);
    }

    /**
     * The one salary formula for the whole ERP (PayrollController parity):
     *   Net = Basic + Overtime + Bonus − Advance − Fine − Shortage
     * Overtime/Bonus/Fine/Shortage come from employee_adjustments for the
     * payroll month; Advance is the total ACTIVE advance balance.
     *
     * @return array<string, string|int>
     */
    private function payrollComponents(Employee $employee, string $monthString): array
    {
        [$year, $month] = array_map('intval', explode('-', $monthString));
        $monthStart = Carbon::create($year, $month, 1);
        $monthEnd = $monthStart->copy()->endOfMonth();

        $attendance = EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get();

        $adjustments = EmployeeAdjustment::query()
            ->where('employee_id', $employee->id)
            ->where('payroll_month', $monthString);

        $sumType = fn (string $type): string => Money::n(
            (clone $adjustments)->where('type', $type)->sum('amount')
        );

        $overtime = $sumType(EmployeeAdjustment::TYPE_OVERTIME);
        $bonus = $sumType(EmployeeAdjustment::TYPE_BONUS);
        $fine = $sumType(EmployeeAdjustment::TYPE_FINE);
        $shortage = $sumType(EmployeeAdjustment::TYPE_SHORTAGE_RECOVERY);

        $advanceDeduction = Money::n(
            $employee->advances()->where('status', EmployeeAdvance::STATUS_ACTIVE)->sum('balance')
        );

        $basic = Money::round(Money::n($employee->basic_salary));

        $net = Money::add(Money::add($basic, $overtime), $bonus);
        $net = Money::subtract($net, $advanceDeduction);
        $net = Money::subtract($net, $fine);
        $net = Money::subtract($net, $shortage);
        if (Money::isNegative($net)) {
            $net = '0.00';
        }

        return [
            'present_days' => $attendance->where('status', EmployeeAttendance::STATUS_PRESENT)->count(),
            'absent_days' => $attendance->where('status', EmployeeAttendance::STATUS_ABSENT)->count(),
            'half_days' => $attendance->where('status', EmployeeAttendance::STATUS_HALF_DAY)->count(),
            'leave_days' => $attendance->where('status', EmployeeAttendance::STATUS_LEAVE)->count(),
            'basic' => $basic,
            'overtime' => Money::round($overtime),
            'bonus' => Money::round($bonus),
            'fine' => Money::round($fine),
            'shortage' => Money::round($shortage),
            'advance_deduction' => Money::round($advanceDeduction),
            'deductions' => Money::round(Money::add($fine, $shortage)),
            'net' => Money::round($net),
        ];
    }

    /**
     * Reduce an employee's ACTIVE advances (oldest first) by the amount a
     * salary sheet deducted. An advance whose balance reaches zero is
     * marked RECOVERED.
     */
    private function settleAdvances(?Employee $employee, string $amount): void
    {
        if (! $employee || Money::isZero(Money::n($amount))) {
            return;
        }

        $remaining = Money::n($amount);

        $advances = $employee->advances()
            ->where('status', EmployeeAdvance::STATUS_ACTIVE)
            ->orderBy('advance_date')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($advances as $advance) {
            if (Money::isZero($remaining)) {
                break;
            }

            $balance = Money::n($advance->balance);
            $pay = Money::compare($balance, $remaining) <= 0 ? $balance : $remaining;
            $newBalance = Money::subtract($balance, $pay);

            $advance->update([
                'balance' => $newBalance,
                'status' => Money::isZero($newBalance)
                    ? EmployeeAdvance::STATUS_RECOVERED
                    : EmployeeAdvance::STATUS_ACTIVE,
            ]);

            $remaining = Money::subtract($remaining, $pay);
        }
    }

    /**
     * Post the cash/bank trail for money leaving the station
     * (advance given, salary paid):
     *  - BANK_TRANSFER + account → bank_transactions WITHDRAWAL row
     *    (bank balance is derived from this ledger — see
     *    BankAccount::currentBalance()).
     *  - CASH + shift → shift_cash CASH_OUT row, so the shift's
     *    expected cash decreases (ShiftService::sumCashOut()).
     */
    private function recordMoneyOutflow(
        int $branchId,
        string $paymentMethod,
        ?int $bankAccountId,
        ?int $shiftId,
        string $amount,
        string $description,
        string $reference,
        ?int $actorId,
    ): void {
        if (! Money::isPositive(Money::n($amount))) {
            return;
        }

        if ($paymentMethod === EmployeeSalary::METHOD_BANK_TRANSFER && $bankAccountId) {
            $account = BankAccount::query()->find($bankAccountId);
            if ($account) {
                $before = $account->currentBalance();
                DB::table('bank_transactions')->insert([
                    'branch_id' => $branchId,
                    'bank_account_id' => $account->id,
                    'type' => 'WITHDRAWAL',
                    'amount' => Money::round($amount),
                    'balance_before' => $before,
                    'balance_after' => Money::subtract($before, $amount),
                    'reference_number' => $reference,
                    'transaction_date' => now(),
                    'description' => $description,
                    'performed_by' => $actorId,
                    'shift_id' => $shiftId,
                    'status' => 'COMPLETED',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($paymentMethod === EmployeeSalary::METHOD_CASH && $shiftId) {
            ShiftCash::create([
                'shift_id' => $shiftId,
                'entry_type' => 'CASH_OUT',
                'amount' => Money::round($amount),
                'notes' => $description . ' (' . $reference . ')',
                'user_id' => $actorId,
            ]);
        }
    }

    /* =====================================================================
     | Legacy helpers (fixed to the real schema — signatures preserved)
     ===================================================================== */

    /**
     * Record attendance for an employee (single day).
     */
    public function recordAttendance(
        Employee $employee,
        \DateTime $attendanceDate,
        string $status,
        ?\DateTime $checkInTime = null,
        ?\DateTime $checkOutTime = null,
        ?string $notes = null
    ): EmployeeAttendance {
        return EmployeeAttendance::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => Carbon::instance($attendanceDate)->toDateString(),
            ],
            [
                'branch_id' => $employee->branch_id,
                'status' => $status,
                'in_time' => $checkInTime ? Carbon::instance($checkInTime)->format('H:i:s') : null,
                'out_time' => $checkOutTime ? Carbon::instance($checkOutTime)->format('H:i:s') : null,
                'notes' => $notes,
            ]
        );
    }

    /**
     * Record a staff advance/loan (simple variant — see giveAdvance()
     * for the full EmployeeController flow with payment side effects).
     */
    public function recordAdvance(
        Employee $employee,
        float $amount,
        \DateTime $advanceDate,
        ?string $notes = null
    ): EmployeeAdvance {
        return EmployeeAdvance::create([
            'branch_id' => $employee->branch_id,
            'employee_id' => $employee->id,
            'amount' => round($amount, 2),
            'balance' => round($amount, 2),
            'monthly_deduction' => 0,
            'advance_date' => Carbon::instance($advanceDate)->toDateString(),
            'payment_method' => EmployeeAdvance::METHOD_CASH,
            'reason' => $notes,
            'status' => EmployeeAdvance::STATUS_ACTIVE,
        ]);
    }

    /**
     * Settle (part of) a staff advance outside a salary sheet.
     */
    public function settleAdvance(EmployeeAdvance $advance, \DateTime $settlementDate, float $settlementAmount): EmployeeAdvance
    {
        $newBalance = Money::subtract(Money::n($advance->balance), Money::n($settlementAmount));

        $advance->update([
            'balance' => Money::isNegative($newBalance) ? '0.00' : $newBalance,
            'status' => Money::isNegative($newBalance) || Money::isZero($newBalance)
                ? EmployeeAdvance::STATUS_RECOVERED
                : EmployeeAdvance::STATUS_ACTIVE,
        ]);

        return $advance;
    }

    /**
     * Record shortage deduction for a staff member.
     * Called when shift has stock variance exceeding tolerance.
     * Uses EmployeeAdjustment with TYPE_SHORTAGE_RECOVERY — the
     * employee_adjustments table really does have payroll_month.
     */
    public function recordShortageDeduction(
        Employee $employee,
        Shift $shift,
        float $shortageAmount,
        string $reason,
        int $branchId
    ): EmployeeAdjustment {
        return DB::transaction(function () use ($employee, $shift, $shortageAmount, $reason, $branchId) {
            $adjustment = EmployeeAdjustment::create([
                'branch_id' => $branchId,
                'employee_id' => $employee->id,
                'type' => EmployeeAdjustment::TYPE_SHORTAGE_RECOVERY,
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
     * Approve a shortage deduction.
     */
    public function approveShortageDeduction(EmployeeAdjustment $adjustment, int $approvedById): EmployeeAdjustment
    {
        $adjustment->update([
            'is_applied' => true,
            'approved_by' => $approvedById,
        ]);

        return $adjustment;
    }

    /**
     * Generate the salary sheet for ONE employee for a given month
     * (delegates to the same builder as generateMonthlySalarySheet()).
     */
    public function generateSalarySheet(
        Employee $employee,
        int $year,
        int $month,
        int $branchId
    ): EmployeeSalary {
        return DB::transaction(fn () => $this->buildSalarySheet(
            $employee,
            sprintf('%04d-%02d', $year, $month),
            $branchId,
            null,
        ));
    }

    /**
     * Mark a salary sheet as paid (simple variant — see paySalary()
     * for the full EmployeeController flow with payment side effects).
     */
    public function markSalaryAsPaid(
        EmployeeSalary $salarySheet,
        \DateTime $paymentDate,
        ?string $notes = null
    ): EmployeeSalary {
        $salarySheet->update([
            'status' => EmployeeSalary::STATUS_PAID,
            'payment_date' => Carbon::instance($paymentDate)->toDateString(),
            'paid_amount' => $salarySheet->net_salary,
            'notes' => $notes ?? $salarySheet->notes,
        ]);

        Log::info('Salary marked as paid', [
            'employee_id' => $salarySheet->employee_id,
            'amount' => $salarySheet->net_salary,
            'date' => $paymentDate->format('Y-m-d'),
        ]);

        return $salarySheet;
    }

    /**
     * Get attendance summary for an employee in a date range.
     * Working hours are computed from in_time/out_time (there is no
     * working_hours column on the real table).
     */
    public function getAttendanceSummary(Employee $employee, Carbon $fromDate, Carbon $toDate): array
    {
        $attendances = EmployeeAttendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$fromDate->toDateString(), $toDate->toDateString()])
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

        return $summary;
    }

    /**
     * Get payroll summary for the entire staff for a given month.
     * Shortage totals come from employee_adjustments (the salaries table
     * folds shortage into `deductions` together with fines).
     */
    public function getPayrollSummary(int $year, int $month, ?int $branchId = null): array
    {
        $monthString = sprintf('%04d-%02d', $year, $month);

        $query = EmployeeSalary::query()->where('month', $monthString);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        $sheets = $query->get();

        $shortageQuery = EmployeeAdjustment::query()
            ->where('type', EmployeeAdjustment::TYPE_SHORTAGE_RECOVERY)
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

    /**
     * Get shortage deductions for an employee in a date range.
     */
    public function getShortageHistory(Employee $employee, Carbon $fromDate, Carbon $toDate): Collection
    {
        return EmployeeAdjustment::query()
            ->where('employee_id', $employee->id)
            ->where('type', EmployeeAdjustment::TYPE_SHORTAGE_RECOVERY)
            ->whereBetween('effective_date', [$fromDate->toDateString(), $toDate->toDateString()])
            ->orderByDesc('effective_date')
            ->get();
    }

    /**
     * Calculate expected staff for a shift based on shift type.
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
     * Check if a shift is understaffed and needs deductions.
     */
    public function isShiftUnderstaffed(Shift $shift): bool
    {
        $expectedStaff = $this->getExpectedStaffForShift($shift->shift_type ?? 'MIXED');
        $actualStaff = $shift->nozzles()->count();

        return $actualStaff < $expectedStaff;
    }
}
