<?php

namespace Tests\Feature;

use App\Models\ApprovalRequest;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\BankTransaction;
use App\Models\Branch;
use App\Models\Cheque;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\EmployeeAdvance;
use App\Models\EmployeeAttendance;
use App\Models\EmployeeSalary;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Role;
use App\Models\Shift;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PakistaniCurrency;
use App\Support\PakistaniIbanValidator;
use Carbon\Carbon;
use Database\Seeders\BankSeeder;
use Database\Seeders\ChartOfAccountsSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BankingAndPayrollTest extends TestCase
{
    use DatabaseTransactions;

    private User $admin;
    private Branch $branch;
    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        $this->seed(BankSeeder::class);
        $this->seed(ChartOfAccountsSeeder::class);

        $this->branch = Branch::factory()->create([
            'name' => 'Mehar Filling Station',
            'code' => 'MEHAR-01',
            'status' => Branch::STATUS_ACTIVE,
        ]);

        $this->admin = User::factory()->superAdmin()->create([
            'name' => 'Sheikh Usman (Owner)',
            'email' => 'owner@meharpetrol.com',
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->admin->branches()->attach($this->branch->id, ['is_default' => true]);

        $hbl = Bank::where('short_name', 'HBL')->first() ?? Bank::first();

        $this->bankAccount = BankAccount::create([
            'branch_id' => $this->branch->id,
            'bank_id' => $hbl->id,
            'account_title' => 'Mehar Filling Station - Main Collection',
            'account_number' => '00427901823901',
            'iban' => PakistaniIbanValidator::generate('HABB', '00427901823901'),
            'account_type' => 'CURRENT',
            'opening_balance' => '500000.00',
            'status' => 'ACTIVE',
        ]);
    }

    /** 1. Pakistani Banking: 25+ banks, IBAN validation, deposit with slip upload, withdrawal, transfer, bank book balance */

    public function test_can_manage_25_plus_pakistani_banks_and_accounts(): void
    {
        $this->actingAs($this->admin);

        // State Bank scheduled banks list must exceed 25
        $this->assertGreaterThanOrEqual(25, Bank::count());

        $response = $this->get(route('banks.index'));
        $response->assertOk();
        $response->assertSee('Mehar Filling Station - Main Collection');
        $response->assertSee('HBL');
        $response->assertSee('Habib Bank Limited');
        $response->assertSee('Meezan Bank Limited');
    }

    public function test_pakistani_iban_validation(): void
    {
        $this->actingAs($this->admin);

        $validIban = PakistaniIbanValidator::generate('MEZN', '1234567890123456');
        $this->assertTrue(PakistaniIbanValidator::isValid($validIban));

        $invalidIban = 'PK99INVALID123456789012';
        $this->assertFalse(PakistaniIbanValidator::isValid($invalidIban));

        // Test form validation rejects invalid IBAN
        $mcb = Bank::where('short_name', 'MCB')->first() ?? Bank::first();
        $response = $this->post(route('bank-accounts.store'), [
            'bank_id' => $mcb->id,
            'account_title' => 'MCB Pump Account',
            'account_number' => '112233445566',
            'iban' => 'PK00MCBL0000000000000000', // Invalid checksum
            'account_type' => 'CURRENT',
            'status' => 'ACTIVE',
        ]);
        $response->assertSessionHasErrors('iban');

        // Valid IBAN succeeds
        $validPostIban = PakistaniIbanValidator::generate('MUCB', '112233445566');
        $successResponse = $this->post(route('bank-accounts.store'), [
            'bank_id' => $mcb->id,
            'account_title' => 'MCB Pump Account',
            'account_number' => '112233445566',
            'iban' => $validPostIban,
            'account_type' => 'CURRENT',
            'status' => 'ACTIVE',
        ]);
        $successResponse->assertSessionHasNoErrors();
        $this->assertDatabaseHas('bank_accounts', ['account_title' => 'MCB Pump Account']);
    }

    public function test_cash_to_bank_deposit_with_slip_photo_upload(): void
    {
        $this->actingAs($this->admin);

        $fakeSlip = UploadedFile::fake()->image('hbl_deposit_slip.jpg', 600, 800);

        $response = $this->post(route('bank-deposits.store'), [
            'bank_account_id' => $this->bankAccount->id,
            'amount' => '150000.00',
            'reference' => 'HBL-SLIP-9901',
            'notes' => 'Evening shift cash deposit',
            'deposited_at' => now()->toDateString(),
            'slip' => $fakeSlip,
        ]);

        $response->assertRedirect(route('bank-deposits.index'));
        $response->assertSessionHas('success');

        // Check deposit record and slip upload
        $deposit = BankDeposit::where('reference_number', 'HBL-SLIP-9901')->first();
        $this->assertNotNull($deposit);
        $this->assertEquals('150000.00', (string) $deposit->amount);
        $this->assertNotNull($deposit->slip_path);
        Storage::disk('public')->assertExists($deposit->slip_path);

        // Check bank transaction created
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_DEPOSIT,
            'amount' => '150000.00',
        ]);
    }

    public function test_bank_withdrawal_and_inter_bank_transfer(): void
    {
        $this->actingAs($this->admin);

        // 1. Withdrawal
        $withdrawResponse = $this->post(route('banks.withdraw', $this->bankAccount), [
            'amount' => '50000.00',
            'reason' => 'Emergency Cash Drawer Replenishment',
            'reference' => 'CHQ-98124',
        ]);
        $withdrawResponse->assertRedirect();
        $withdrawResponse->assertSessionHas('success');

        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_WITHDRAWAL,
            'amount' => '50000.00',
        ]);

        // 2. Transfer between accounts
        $meezan = Bank::where('short_name', 'Meezan Bank')->first() ?? Bank::first();
        $destinationAccount = BankAccount::create([
            'branch_id' => $this->branch->id,
            'bank_id' => $meezan->id,
            'account_title' => 'Mehar Filling Station - Meezan Islamic',
            'account_number' => '02010109283921',
            'account_type' => 'CURRENT',
            'opening_balance' => '100000.00',
            'status' => 'ACTIVE',
        ]);

        $transferResponse = $this->post(route('banks.transfer'), [
            'from_account_id' => $this->bankAccount->id,
            'to_account_id' => $destinationAccount->id,
            'amount' => '75000.00',
            'charges' => '100.00',
            'reference' => 'FT-99120',
            'notes' => 'Transfer for PSO indent balance',
        ]);
        $transferResponse->assertRedirect();
        $transferResponse->assertSessionHas('success');

        // Check transfer-out and transfer-in transactions
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_TRANSFER_OUT,
            'amount' => '75000.00',
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $destinationAccount->id,
            'type' => BankTransaction::TYPE_TRANSFER_IN,
            'amount' => '75000.00',
        ]);
    }

    public function test_can_view_bank_book_with_running_balance(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get(route('banks.book', $this->bankAccount));
        $response->assertOk();
        $response->assertSee('Bank Book');
        $response->assertSee('Mehar Filling Station - Main Collection');
        $response->assertSee('Opening Balance');
        $response->assertSee('Rs. 500,000.00');
    }

    /** 2. Cheque Register: customer received, supplier issued, PDC calendar, deposit, clearance, and bounce handling */

    public function test_can_receive_customer_cheque_and_credit_ledger(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'account_number' => 'CUST-001',
            'name' => 'Bilal Goods Transport',
            'phone' => '0300-9876543',
            'credit_limit' => '1000000.00',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $fakeChequeImg = UploadedFile::fake()->image('cheque_scan.jpg', 800, 400);

        $response = $this->post(route('cheques.store'), [
            'type' => 'RECEIVED',
            'customer_id' => $customer->id,
            'bank_name' => 'Faysal Bank Limited',
            'cheque_number' => '09812455',
            'amount' => '250000.00',
            'cheque_date' => now()->toDateString(),
            'due_date' => now()->addDays(15)->toDateString(), // PDC
            'payee_name' => 'Mehar Filling Station',
            'notes' => 'Received against bulk diesel supply',
            'image' => $fakeChequeImg,
        ]);

        $response->assertRedirect();
        $cheque = Cheque::where('cheque_number', '09812455')->first();
        $this->assertNotNull($cheque);
        $this->assertTrue($cheque->is_pdc);
        $this->assertEquals('RECEIVED', $cheque->status);
        $this->assertNotNull($cheque->image_path);

        // Verify Customer Ledger was credited
        $this->assertDatabaseHas('customer_ledger', [
            'customer_id' => $customer->id,
            'credit' => '250000.00',
        ]);
    }

    public function test_can_deposit_and_clear_cheque(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'account_number' => 'CUST-002',
            'name' => 'Tariq Logistics',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $cheque = Cheque::create([
            'branch_id' => $this->branch->id,
            'type' => Cheque::TYPE_RECEIVED,
            'cheque_number' => '00112233',
            'bank_name' => 'Askari Bank',
            'customer_id' => $customer->id,
            'amount' => '80000.00',
            'cheque_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'status' => Cheque::STATUS_RECEIVED,
        ]);

        // 1. Deposit into station account
        $depositResponse = $this->post(route('cheques.deposit', $cheque), [
            'bank_account_id' => $this->bankAccount->id,
            'deposit_date' => now()->toDateString(),
        ]);
        $depositResponse->assertRedirect();
        $cheque->refresh();
        $this->assertEquals(Cheque::STATUS_DEPOSITED, $cheque->status);

        // 2. Clear cheque
        $clearResponse = $this->post(route('cheques.clear', $cheque), [
            'cleared_date' => now()->toDateString(),
        ]);
        $clearResponse->assertRedirect();
        $cheque->refresh();
        $this->assertEquals(Cheque::STATUS_CLEARED, $cheque->status);

        // Bank transaction created
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_CHEQUE_DEPOSIT,
            'amount' => '80000.00',
        ]);
    }

    public function test_cheque_bounce_reverses_customer_ledger_and_applies_bank_charges(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'account_number' => 'CUST-003',
            'name' => 'Sheikhupura Brick Kiln',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        $cheque = Cheque::create([
            'branch_id' => $this->branch->id,
            'type' => Cheque::TYPE_RECEIVED,
            'cheque_number' => '00998877',
            'bank_name' => 'Bank of Punjab',
            'bank_account_id' => $this->bankAccount->id,
            'customer_id' => $customer->id,
            'amount' => '120000.00',
            'cheque_date' => now()->toDateString(),
            'due_date' => now()->toDateString(),
            'status' => Cheque::STATUS_DEPOSITED,
        ]);

        $bounceResponse = $this->post(route('cheques.bounce', $cheque), [
            'bounce_reason' => 'Insufficient Funds / فنڈز ناکافی',
            'bank_charges' => '650.00',
            'bounced_date' => now()->toDateString(),
        ]);
        $bounceResponse->assertRedirect();
        $cheque->refresh();

        $this->assertEquals(Cheque::STATUS_BOUNCED, $cheque->status);
        $this->assertEquals('Insufficient Funds / فنڈز ناکافی', $cheque->bounce_reason);

        // Verify customer ledger was debited (reversal + charges)
        $this->assertDatabaseHas('customer_ledger', [
            'customer_id' => $customer->id,
            'debit' => '120000.00',
        ]);
        $this->assertDatabaseHas('customer_ledger', [
            'customer_id' => $customer->id,
            'debit' => '650.00',
        ]);

        // Verify bank was charged
        $this->assertDatabaseHas('bank_transactions', [
            'bank_account_id' => $this->bankAccount->id,
            'type' => BankTransaction::TYPE_CHARGES,
            'amount' => '650.00',
        ]);

        // Verify critical notification was dispatched
        $this->assertDatabaseHas('notifications', [
            'type' => 'CHEQUE_BOUNCED',
            'level' => 'CRITICAL',
        ]);
    }

    public function test_supplier_cheque_issuance_and_pdc_calendar(): void
    {
        $this->actingAs($this->admin);

        $supplier = Supplier::create([
            'branch_id' => $this->branch->id,
            'code' => 'SUP-PSO',
            'name' => 'Pakistan State Oil (PSO)',
            'company_name' => 'Pakistan State Oil Company Limited',
            'status' => 'ACTIVE',
        ]);

        $futureDate = now()->addDays(10)->format('Y-m-d');

        $response = $this->post(route('cheques.store'), [
            'type' => 'ISSUED',
            'supplier_id' => $supplier->id,
            'bank_account_id' => $this->bankAccount->id,
            'amount' => '500000.00',
            'cheque_number' => 'PSO-CHQ-1002',
            'cheque_date' => now()->toDateString(),
            'due_date' => $futureDate,
            'payee_name' => 'Pakistan State Oil',
            'notes' => 'Advance payment for high-speed diesel tanker',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('cheques', [
            'type' => Cheque::TYPE_ISSUED,
            'cheque_number' => 'PSO-CHQ-1002',
            'is_pdc' => true,
        ]);

        // Check PDC calendar view displays the future cheque
        $calendarResponse = $this->get(route('cheques.index', ['view_mode' => 'calendar']));
        $calendarResponse->assertOk();
        $calendarResponse->assertSee('PDC Schedule');
        $calendarResponse->assertSee('PSO-CHQ-1002');
    }

    /** 3. Staff & Payroll: Employee CRUD, Attendance, Advances, Monthly Salary Sheet, Payslip printing */

    public function test_employee_crud(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('employees.store'), [
            'name' => 'Muhammad Rashid',
            'designation' => 'Pump Attendant',
            'phone' => '0301-7654321',
            'cnic' => '35404-1234567-9',
            'joining_date' => '2026-01-01',
            'basic_salary' => '38000.00',
            'daily_wage' => '1266.67',
        ]);

        $response->assertRedirect(route('employees.index'));
        $employee = Employee::where('phone', '0301-7654321')->first();
        $this->assertNotNull($employee);
        $this->assertEquals('Muhammad Rashid', $employee->name);
        $this->assertEquals('38000.00', (string) $employee->basic_salary);

        // Edit employee
        $updateResponse = $this->put(route('employees.update', $employee), [
            'name' => 'Muhammad Rashid Senior',
            'designation' => 'Shift Supervisor',
            'phone' => '0301-7654321',
            'cnic' => '35404-1234567-9',
            'basic_salary' => '45000.00',
            'daily_wage' => '1500.00',
            'status' => 'ACTIVE',
        ]);
        $updateResponse->assertRedirect(route('employees.index'));
        $employee->refresh();
        $this->assertEquals('Shift Supervisor', $employee->designation);
        $this->assertEquals('45000.00', (string) $employee->basic_salary);
    }

    public function test_daily_attendance_and_payroll_generation(): void
    {
        $this->actingAs($this->admin);

        $emp1 = Employee::create([
            'branch_id' => $this->branch->id,
            'code' => 'EMP-01',
            'name' => 'Zahid Attendant',
            'designation' => 'Pump Attendant',
            'basic_salary' => '36000.00',
            'status' => 'ACTIVE',
        ]);

        $today = now()->format('Y-m-d');

        // Mark daily attendance
        $attResponse = $this->post(route('employees.attendance.store'), [
            'date' => $today,
            'attendance' => [
                $emp1->id => 'PRESENT',
            ],
        ]);
        $attResponse->assertRedirect();
        $this->assertDatabaseHas('employee_attendances', [
            'employee_id' => $emp1->id,
            'date' => $today,
            'status' => 'PRESENT',
        ]);

        // Issue staff advance loan
        $advResponse = $this->post(route('employees.advance.store', $emp1), [
            'amount' => '10000.00',
            'monthly_deduction' => '2500.00',
            'payment_method' => 'CASH',
            'reason' => 'House repair advance',
        ]);
        $advResponse->assertRedirect();
        $this->assertDatabaseHas('employee_advances', [
            'employee_id' => $emp1->id,
            'amount' => '10000.00',
            'balance' => '10000.00',
        ]);

        // Record overtime adjustment
        $month = now()->format('Y-m');
        $adjResponse = $this->post(route('employees.adjustment.store', $emp1), [
            'type' => 'OVERTIME',
            'amount' => '2000.00',
            'payroll_month' => $month,
            'reason' => '16 hours night shift overtime',
        ]);
        $adjResponse->assertRedirect();

        // Generate monthly payroll sheet
        $payrollGen = $this->post(route('employees.payroll.generate'), ['month' => $month]);
        $payrollGen->assertRedirect();

        $salary = EmployeeSalary::where('employee_id', $emp1->id)->where('month', $month)->first();
        $this->assertNotNull($salary);
        $this->assertEquals('2500.00', (string) $salary->advance_deduction);
        $this->assertEquals('2000.00', (string) $salary->overtime_amount);
        // Net: 36000 + 2000 - 2500 = 35500
        $this->assertEquals('35500.00', (string) $salary->net_salary);

        // Pay salary
        $payResponse = $this->post(route('employees.payroll.pay', $salary), [
            'payment_method' => 'BANK_TRANSFER',
            'bank_account_id' => $this->bankAccount->id,
            'notes' => 'Salary paid via bank transfer',
        ]);
        $payResponse->assertRedirect();
        $salary->refresh();
        $this->assertEquals(EmployeeSalary::STATUS_PAID, $salary->status);

        // Advance loan balance reduced
        $advance = EmployeeAdvance::where('employee_id', $emp1->id)->first();
        $this->assertEquals('7500.00', (string) $advance->balance);

        // Expense posted under salaries
        $this->assertDatabaseHas('expenses', [
            'title' => "Staff Salary: {$emp1->name} ({$month})",
            'amount' => '35500.00',
        ]);

        // View and verify printable payslip with Urdu words
        $payslipResponse = $this->get(route('employees.payslip', $salary));
        $payslipResponse->assertOk();
        $payslipResponse->assertSee('MEHAR FILLING STATION');
        $payslipResponse->assertSee('PAYSLIP');
        $payslipResponse->assertSee('Zahid Attendant');
        $payslipResponse->assertSee('Rs. 35,500.00');
        $payslipResponse->assertSee('روپے صرف');
    }

    /** 4. Expenses & Vouchers: photo attachment, shift/bank bindings */

    public function test_expenses_with_voucher_photo_attachment(): void
    {
        $this->actingAs($this->admin);

        $category = ExpenseCategory::firstOrCreate(
            ['code' => 'EXP-GEN'],
            ['name' => 'Generator Fuel & Oil', 'urdu_name' => 'جنریٹر ڈیزل', 'status' => 'ACTIVE']
        );

        $fakeReceipt = UploadedFile::fake()->image('generator_fuel_receipt.png', 500, 700);

        $response = $this->post(route('expenses.store'), [
            'category_id' => $category->id,
            'title' => 'Generator Mobil Oil 20W-50 (4 Litres)',
            'amount' => '6500.00',
            'payment_method' => 'CASH',
            'payee' => 'Total Lubes Sheikhupura',
            'receipt_number' => 'RCP-8821',
            'date' => now()->toDateString(),
            'notes' => 'Monthly generator maintenance',
            'attachment' => $fakeReceipt,
        ]);

        $response->assertRedirect(route('expenses.index'));
        $expense = Expense::where('receipt_number', 'RCP-8821')->first();
        $this->assertNotNull($expense);
        $this->assertEquals('6500.00', (string) $expense->amount);
        $this->assertNotNull($expense->attachment_path);
        Storage::disk('public')->assertExists($expense->attachment_path);

        // View expense voucher
        $showResponse = $this->get(route('expenses.show', $expense));
        $showResponse->assertOk();
        $showResponse->assertSee('Generator Mobil Oil');
        $showResponse->assertSee('Rs. 6,500.00');
        $showResponse->assertSee('روپے صرف');
    }

    /** 5. Approvals Centre: meter correction, cash shortage, WhatsApp link (0300-4342343), Approve/Reject */

    public function test_approvals_centre_with_owner_whatsapp_and_decision_workflow(): void
    {
        $this->actingAs($this->admin);

        // 1. Submit approval request
        $response = $this->post(route('approvals.store'), [
            'request_type' => 'CASH_SHORTAGE',
            'title' => 'Evening Shift Cash Shortage Waiver',
            'description' => 'Attendant Nasir reported counterfeit Rs. 5000 note deposited during heavy rush hour; requested management waiver',
            'amount' => '5000.00',
        ]);

        $response->assertRedirect(route('approvals.index'));
        $req = ApprovalRequest::where('title', 'Evening Shift Cash Shortage Waiver')->first();
        $this->assertNotNull($req);
        $this->assertEquals('PENDING', $req->status);

        // Verify WhatsApp URL links directly to owner phone 0300-4342343 (923004342343)
        $this->assertStringContainsString('923004342343', $req->whatsapp_url);
        $this->assertStringContainsString('CASH_SHORTAGE', $req->whatsapp_url);
        $this->assertStringContainsString('5,000.00', $req->whatsapp_url);

        // 2. Big Green Approve with mandatory reason
        $approveResponse = $this->post(route('approvals.approve', $req), [
            'reason' => 'Sanctioned by owner Sheikh Usman after CCTV verification of counterfeit note.',
        ]);
        $approveResponse->assertRedirect();
        $req->refresh();
        $this->assertEquals('APPROVED', $req->status);
        $this->assertEquals('Sanctioned by owner Sheikh Usman after CCTV verification of counterfeit note.', $req->action_reason);

        // 3. Reject workflow on another request
        $req2 = ApprovalRequest::create([
            'branch_id' => $this->branch->id,
            'request_type' => 'CREDIT_OVERRIDE',
            'title' => 'Credit Override for Tariq Freight',
            'description' => 'Request Rs. 200,000 credit limit extension',
            'amount' => '200000.00',
            'status' => 'PENDING',
            'requested_by' => $this->admin->id,
        ]);

        $rejectResponse = $this->post(route('approvals.reject', $req2), [
            'reason' => 'Previous overdue cheque pending clearance; override declined.',
        ]);
        $rejectResponse->assertRedirect();
        $req2->refresh();
        $this->assertEquals('REJECTED', $req2->status);
        $this->assertEquals('Previous overdue cheque pending clearance; override declined.', $req2->action_reason);
    }
}
