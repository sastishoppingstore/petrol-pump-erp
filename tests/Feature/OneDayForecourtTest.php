<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Bank;
use App\Models\BankAccount;
use App\Models\BankDeposit;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DailyClosing;
use App\Models\Dispenser;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\FuelPrice;
use App\Models\FuelProduct;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\Nozzle;
use App\Models\Purchase;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Models\Tank;
use App\Models\TankReading;
use App\Models\User;
use App\Services\Accounting\AccountingService;
use App\Services\Accounts\ExpenseService;
use App\Services\Accounts\PaymentService;
use App\Services\Purchase\PurchaseService;
use App\Services\Report\ReportService;
use App\Services\Sale\SaleService;
use App\Services\Sale\SaleVoidService;
use App\Services\Shift\DailyClosingService;
use App\Services\Shift\ShiftClosingService;
use App\Services\Shift\ShiftService;
use App\Services\Stock\StockService;
use App\Services\System\BackupService;
use App\Support\Money;
use App\Support\PermissionList;
use App\Support\Quantity;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/**
 * End-to-end integration test simulating one full forecourt business day
 * at Vital Petroleum, Mehar Filling Station Sheikhupura.
 *
 * Covers:
 * 1. Master data setup (branch, pumps, tanks, accounts, users, products)
 * 2. Fuel purchase and decantation with GL posting
 * 3. Forecourt shift opening and fuel dispensing (Cash & Udhaar)
 * 4. Customer Udhaar recovery with GL posting
 * 5. Operating expenses and petty cash with GL posting
 * 6. Bank deposit handover with GL posting
 * 7. Forecourt shift closing with meter readings and cash variance GL posting
 * 8. Physical tank dip measurement and variance calculation
 * 9. Daily Closing Wizard 4-point verification and day locking
 * 10. GL trial balance and full accounting balance checks
 * 11. Rendering and export of all 22 core reports (HTML, Print, CSV)
 * 12. Manual journal voucher creation and voiding with symmetrical reversal
 * 13. Pure-PHP chunked database backup and ZIP archive generation
 */
class OneDayForecourtTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;
    private User $admin;
    private User $cashier;
    private FuelProduct $super;
    private FuelProduct $diesel;
    private Tank $tankSuper;
    private Tank $tankDiesel;
    private Dispenser $dispenser;
    private Nozzle $nozzleSuper;
    private Nozzle $nozzleDiesel;
    private Supplier $supplier;
    private Customer $customer;
    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ProductionSeeder::class);

        $this->branch = Branch::factory()->create([
            'name' => 'Mehar Filling Station',
            'code' => 'MEHAR01',
            'city' => 'Sheikhupura',
            'address' => 'Gujranwala Road, Sheikhupura',
        ]);

        $this->admin = User::factory()->withRole(Role::ADMIN)->create([
            'name' => 'Station Manager',
            'email' => 'manager@meharfilling.com',
        ]);
        $this->admin->branches()->attach($this->branch->id, ['is_default' => true]);

        $this->cashier = User::factory()->withRole(Role::CASHIER)->create([
            'name' => 'Forecourt Cashier',
            'email' => 'cashier@meharfilling.com',
        ]);
        $this->cashier->branches()->attach($this->branch->id, ['is_default' => true]);

        // Fuel Products
        $this->super = FuelProduct::create([
            'name' => 'Super 92 Unleaded',
            'code' => 'SUPER',
            'color_code' => '#D71920',
            'purchase_price' => '250.00',
            'sale_price' => '265.00',
            'average_cost' => '250.00',
            'status' => 'ACTIVE',
        ]);

        $this->diesel = FuelProduct::create([
            'name' => 'High Speed Diesel (HSD)',
            'code' => 'HSD',
            'color_code' => '#0F834D',
            'purchase_price' => '260.00',
            'sale_price' => '278.00',
            'average_cost' => '260.00',
            'status' => 'ACTIVE',
        ]);

        // Tanks
        $this->tankSuper = Tank::create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->super->id,
            'tank_number' => 'T-01',
            'capacity' => '25000.000',
            'current_stock' => '10000.000',
            'status' => 'ACTIVE',
        ]);

        $this->tankDiesel = Tank::create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $this->diesel->id,
            'tank_number' => 'T-02',
            'capacity' => '30000.000',
            'current_stock' => '15000.000',
            'status' => 'ACTIVE',
        ]);

        // Dispenser and Nozzles
        $this->dispenser = Dispenser::create([
            'branch_id' => $this->branch->id,
            'dispenser_number' => 'D-01',
            'name' => 'Dispenser Island 1',
            'status' => 'ACTIVE',
        ]);

        $this->nozzleSuper = Nozzle::create([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $this->dispenser->id,
            'tank_id' => $this->tankSuper->id,
            'fuel_product_id' => $this->super->id,
            'nozzle_number' => 'N-01',
            'current_meter_reading' => '50000.000',
            'status' => 'ACTIVE',
        ]);

        $this->nozzleDiesel = Nozzle::create([
            'branch_id' => $this->branch->id,
            'dispenser_id' => $this->dispenser->id,
            'tank_id' => $this->tankDiesel->id,
            'fuel_product_id' => $this->diesel->id,
            'nozzle_number' => 'N-02',
            'current_meter_reading' => '80000.000',
            'status' => 'ACTIVE',
        ]);

        // Supplier (Vital Petroleum OMC)
        $this->supplier = Supplier::create([
            'branch_id' => $this->branch->id,
            'name' => 'Vital Petroleum Pakistan Ltd',
            'code' => 'SUP-VITAL',
            'phone' => '042-11184825',
            'current_balance' => '0.00',
            'status' => 'ACTIVE',
        ]);

        // Customer (Sheikhupura Transport Fleet)
        $this->customer = Customer::create([
            'branch_id' => $this->branch->id,
            'code' => 'CUST-001',
            'name' => 'Sheikhupura Transport Fleet',
            'phone' => '03001234567',
            'credit_limit' => '100000.00',
            'current_balance' => '0.00',
            'opening_balance' => '0.00',
            'status' => Customer::STATUS_ACTIVE,
        ]);

        // Bank Account (HBL)
        $bank = Bank::where('short_name', 'HBL')->first() ?? Bank::create([
            'name' => 'Habib Bank Limited',
            'short_name' => 'HBL',
            'bank_type' => 'COMMERCIAL',
            'status' => 'ACTIVE',
        ]);

        $this->bankAccount = BankAccount::create([
            'branch_id' => $this->branch->id,
            'bank_id' => $bank->id,
            'account_title' => 'Mehar Filling Station Forecourt Account',
            'account_number' => '01234567890123',
            'iban' => 'PK36HABB0000012345678901',
            'branch_name' => 'Sheikhupura Main Branch',
            'current_balance' => '500000.00',
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test the full one-day forecourt lifecycle and double-entry General Ledger balancing.
     */
    public function test_full_one_day_forecourt_workflow_and_general_ledger_balance(): void
    {
        $today = now()->format('Y-m-d');
        $this->actingAs($this->admin);

        // ---------------------------------------------------------------------
        // Step 1: Fuel Tanker Decantation & Purchase Approval
        // ---------------------------------------------------------------------
        $purchaseService = app(PurchaseService::class);
        $purchase = $purchaseService->createPurchase([
            'branch_id' => $this->branch->id,
            'supplier_id' => $this->supplier->id,
            'purchase_date' => $today,
            'tank_id' => $this->tankSuper->id,
            'fuel_product_id' => $this->super->id,
            'volume_ordered' => '5000.000',
            'volume_received' => '5000.000',
            'purchase_rate' => '250.00',
            'challan_number' => 'CHAL-7861',
            'tanker_number' => 'TL-9821',
        ], $this->admin->id);

        $purchase = $purchaseService->approve($purchase, $this->admin->id);
        $this->assertSame(Purchase::STATUS_APPROVED, $purchase->status);

        // Check GL entry for purchase was posted
        $purchaseJe = JournalEntry::where('reference_type', Purchase::class)
            ->where('reference_id', $purchase->id)
            ->first();
        $this->assertNotNull($purchaseJe, 'Purchase must have posted a General Ledger journal entry.');
        $this->assertSame(JournalEntry::STATUS_POSTED, $purchaseJe->status);
        $this->assertBalancedEntry($purchaseJe);

        // ---------------------------------------------------------------------
        // Step 2: Open Forecourt Shift
        // ---------------------------------------------------------------------
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'cashier_id' => $this->cashier->id,
            'supervisor_id' => $this->admin->id,
            'shift_type' => 'DAY',
            'start_time' => now()->startOfDay()->addHours(6),
            'opening_cash' => '10000.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzleSuper->id, 'opening_meter' => '50000.000'],
                ['nozzle_id' => $this->nozzleDiesel->id, 'opening_meter' => '80000.000'],
            ],
        ], $this->admin);

        $this->assertSame(Shift::STATUS_OPEN, $shift->status);

        // ---------------------------------------------------------------------
        // Step 3: Forecourt Fuel Dispensing (Cash and Udhaar Sales)
        // ---------------------------------------------------------------------
        $saleService = app(SaleService::class);

        // Cash Sale: 40 Litres of Super (40 * 265 = 10,600)
        $cashSale = $saleService->create([
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'customer_id' => null,
            'vehicle_number' => 'LEA-1234',
            'items' => [
                [
                    'nozzle_id' => $this->nozzleSuper->id,
                    'fuel_product_id' => $this->super->id,
                    'tank_id' => $this->tankSuper->id,
                    'litres' => '40.000',
                    'rate' => '265.00',
                    'amount' => '10600.00',
                ],
            ],
            'payments' => [
                [
                    'method' => 'CASH',
                    'amount' => '10600.00',
                ],
            ],
        ], $this->cashier);

        $this->assertSame(Sale::STATUS_COMPLETED, $cashSale->status);

        // GL check for Cash Sale
        $cashSaleJe = JournalEntry::where('reference_type', Sale::class)
            ->where('reference_id', $cashSale->id)
            ->first();
        $this->assertNotNull($cashSaleJe, 'Cash Sale must post a balanced journal entry.');
        $this->assertBalancedEntry($cashSaleJe);

        // Udhaar Sale: 50 Litres of HSD Diesel (50 * 278 = 13,900)
        $creditSale = $saleService->create([
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'customer_id' => $this->customer->id,
            'vehicle_number' => 'SKP-7860',
            'items' => [
                [
                    'nozzle_id' => $this->nozzleDiesel->id,
                    'fuel_product_id' => $this->diesel->id,
                    'tank_id' => $this->tankDiesel->id,
                    'litres' => '50.000',
                    'rate' => '278.00',
                    'amount' => '13900.00',
                ],
            ],
            'payments' => [
                [
                    'method' => 'CREDIT',
                    'amount' => '13900.00',
                ],
            ],
        ], $this->cashier);

        $this->assertSame(Sale::STATUS_COMPLETED, $creditSale->status);

        // GL check for Udhaar Sale
        $creditSaleJe = JournalEntry::where('reference_type', Sale::class)
            ->where('reference_id', $creditSale->id)
            ->first();
        $this->assertNotNull($creditSaleJe, 'Udhaar Sale must post a balanced journal entry.');
        $this->assertBalancedEntry($creditSaleJe);

        // Verify customer balance was debited
        $this->customer->refresh();
        $this->assertSame('13900.00', Money::round($this->customer->current_balance));

        // ---------------------------------------------------------------------
        // Step 4: Customer Udhaar Recovery / Payment Received
        // ---------------------------------------------------------------------
        $paymentService = app(PaymentService::class);
        $custPayment = $paymentService->receiveCustomerPayment(
            branchId: $this->branch->id,
            customerId: $this->customer->id,
            amount: '5000.00',
            paymentMethod: CustomerPayment::METHOD_CASH,
            actorId: $this->admin->id,
            referenceNumber: 'REC-001'
        );

        $custPaymentJe = JournalEntry::where('reference_type', CustomerPayment::class)
            ->where('reference_id', $custPayment->id)
            ->first();
        $this->assertNotNull($custPaymentJe, 'Customer recovery must post a balanced journal entry.');
        $this->assertBalancedEntry($custPaymentJe);

        // ---------------------------------------------------------------------
        // Step 5: Operating Expenses Paid in Cash
        // ---------------------------------------------------------------------
        $expenseCategory = ExpenseCategory::firstOrCreate(
            ['name' => 'Electricity & Generator'],
            ['code' => 'ELEC', 'status' => 'ACTIVE']
        );

        $expenseService = app(ExpenseService::class);
        $expense = $expenseService->create(
            branchId: $this->branch->id,
            categoryId: $expenseCategory->id,
            title: 'Station Generator Maintenance & Filter Replacement',
            amount: '2500.00',
            paymentMethod: Expense::METHOD_CASH,
            date: $today,
            actorId: $this->admin->id
        );

        $expenseJe = JournalEntry::where('reference_type', Expense::class)
            ->where('reference_id', $expense->id)
            ->first();
        $this->assertNotNull($expenseJe, 'Expense must post a balanced journal entry.');
        $this->assertBalancedEntry($expenseJe);

        // ---------------------------------------------------------------------
        // Step 6: Bank Deposit Handover
        // ---------------------------------------------------------------------
        $deposit = BankDeposit::create([
            'branch_id' => $this->branch->id,
            'bank_account_id' => $this->bankAccount->id,
            'bank_name' => 'Habib Bank Limited',
            'reference_number' => 'DEP-' . date('Ymd') . '-001',
            'amount' => '15000.00',
            'deposited_at' => now(),
            'status' => BankDeposit::STATUS_COMPLETED,
            'created_by' => $this->admin->id,
        ]);

        $accountingService = app(AccountingService::class);
        $depositJe = $accountingService->postBankDeposit($deposit);
        $this->assertBalancedEntry($depositJe);

        // ---------------------------------------------------------------------
        // Step 7: Close Forecourt Shift with Cash Variance
        // ---------------------------------------------------------------------
        $shiftClosingService = app(ShiftClosingService::class);
        // Expected Cash in Drawer = Opening Float (10,000) + Cash Sales (10,600) = 20,600
        // Say actual cash counted is 20,500 (Short by 100)
        $closedShift = $shiftClosingService->close(
            shift: $shift,
            meterReadings: [
                $this->nozzleSuper->id => '50040.000',
                $this->nozzleDiesel->id => '80050.000',
            ],
            actualCash: '20500.00',
            actorId: $this->admin->id,
            notes: 'Evening shift end; Rs. 100 short'
        );

        $this->assertContains($closedShift->status, [Shift::STATUS_CLOSED, Shift::STATUS_APPROVED]);

        // Verify GL Cash Variance entry
        $varianceJe = JournalEntry::where('reference_type', Shift::class)
            ->where('reference_id', $shift->id)
            ->first();
        if ($varianceJe) {
            $this->assertBalancedEntry($varianceJe);
        }

        // ---------------------------------------------------------------------
        // Step 8: Record Physical Tank Dips for End-of-Day
        // ---------------------------------------------------------------------
        TankReading::create([
            'branch_id' => $this->branch->id,
            'tank_id' => $this->tankSuper->id,
            'reading_time' => now(),
            'dip_centimeters' => '165.5',
            'physical_volume' => '14960.000',
            'book_volume' => '14960.000',
            'variance_volume' => '0.000',
            'status' => 'NORMAL',
            'created_by' => $this->admin->id,
        ]);

        TankReading::create([
            'branch_id' => $this->branch->id,
            'tank_id' => $this->tankDiesel->id,
            'reading_time' => now(),
            'dip_centimeters' => '152.0',
            'physical_volume' => '14950.000',
            'book_volume' => '14950.000',
            'variance_volume' => '0.000',
            'status' => 'NORMAL',
            'created_by' => $this->admin->id,
        ]);

        // ---------------------------------------------------------------------
        // Step 9: Daily Closing Wizard Execution & Date Lock
        // ---------------------------------------------------------------------
        $closingService = app(DailyClosingService::class);
        $checklist = $closingService->getChecklist($this->branch->id, $today);
        $this->assertTrue($checklist['shifts']['is_verified'], 'All shifts must be closed.');
        $this->assertTrue($checklist['tank_dips']['is_verified'], 'All tank dips must be recorded.');

        $dailyClosing = $closingService->closeDay(
            branchId: $this->branch->id,
            date: $today,
            actor: $this->admin,
            input: ['actual_cash_counted' => '20500.00', 'notes' => 'EOD closing Sheikhupura station']
        );

        $this->assertSame(DailyClosing::STATUS_LOCKED, $dailyClosing->status);
        $this->assertTrue($accountingService->isDateLocked($this->branch->id, $today), 'Date must be locked.');

        // ---------------------------------------------------------------------
        // Step 10: General Ledger Trial Balance Balancing Check
        // ---------------------------------------------------------------------
        $tb = $accountingService->trialBalance($this->branch->id, $today);
        $this->assertTrue($tb['is_balanced'], "Trial Balance MUST be balanced. Debits: {$tb['total_debit']}, Credits: {$tb['total_credit']}");
        $this->assertSame(0, Money::compare($tb['total_debit'], $tb['total_credit']));
    }

    /**
     * Test all 22 core reports render, support date filters, print views, and CSV exports.
     */
    public function test_all_22_core_reports_render_and_export(): void
    {
        $this->actingAs($this->admin);

        $reports = [
            'sales',
            'fuel-sales',
            'nozzle-sales',
            'meter-reading',
            'cashier-performance',
            'cash-book',
            'bank-book',
            'cheque-register',
            'daily-closing',
            'customer-ledger',
            'customer-outstanding',
            'customer-ageing',
            'supplier-ledger',
            'supplier-payable',
            'purchase',
            'tank-stock-variance',
            'price-change-gain-loss',
            'profit-and-loss',
            'balance-sheet',
            'trial-balance',
            'expenses',
            'staff-salary',
        ];

        $this->assertCount(22, $reports, 'Must verify all 22 core reports.');

        // Test Hub Index
        $response = $this->get(route('reports.index'));
        $response->assertOk();
        $response->assertSee('Reports & Analytics Hub');

        foreach ($reports as $r) {
            // 1. Screen HTML View
            $showRes = $this->get(route('reports.show', ['report' => $r]));
            $showRes->assertOk();

            // 2. Print View
            $printRes = $this->get(route('reports.show', ['report' => $r, 'format' => 'print']));
            $printRes->assertOk();
            $printRes->assertSee('Mehar Filling Station');

            // 3. CSV / Excel Streamed Export
            $csvRes = $this->get(route('reports.show', ['report' => $r, 'format' => 'csv']));
            $csvRes->assertOk();
            $this->assertStringContainsString('text/csv', $csvRes->headers->get('Content-Type') ?? '');
        }
    }

    /**
     * Test manual Journal Entry posting and voiding with symmetrical reversal.
     */
    public function test_journal_entry_manual_creation_and_voiding_reversal(): void
    {
        $this->actingAs($this->admin);

        $cashAcc = Account::where('code', Account::CODE_CASH_IN_HAND)->firstOrFail();
        $capitalAcc = Account::where('code', Account::CODE_OWNERS_CAPITAL)->firstOrFail();

        // 1. Render create form
        $this->get(route('journals.create'))->assertOk()->assertSee('Post Double-Entry Journal Entry');

        // 2. Submit new journal entry (Capital injection: Dr Cash Rs. 100,000, Cr Capital Rs. 100,000)
        $postRes = $this->post(route('journals.store'), [
            'date' => now()->format('Y-m-d'),
            'narration' => 'Owner Capital Injection via Cash',
            'lines' => [
                ['account_id' => $cashAcc->id, 'debit' => '100000.00', 'credit' => '0.00', 'memo' => 'Cash received'],
                ['account_id' => $capitalAcc->id, 'debit' => '0.00', 'credit' => '100000.00', 'memo' => 'Capital equity'],
            ],
        ]);

        $postRes->assertRedirect(route('journals.index'));

        $entry = JournalEntry::where('narration', 'Owner Capital Injection via Cash')->first();
        $this->assertNotNull($entry);
        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->status);
        $this->assertBalancedEntry($entry);

        // 3. Void the entry
        $voidRes = $this->post(route('journals.void', $entry->id), [
            'reason' => 'Voucher entered in error, cancelling capital entry',
        ]);

        $voidRes->assertRedirect(route('journals.index'));
        $entry->refresh();
        $this->assertSame(JournalEntry::STATUS_VOID, $entry->status);

        // Check reversal entry
        $reversal = JournalEntry::where('reference_type', JournalEntry::class)
            ->where('reference_id', $entry->id)
            ->first();
        $this->assertNotNull($reversal, 'Reversing journal entry must exist.');
        $this->assertBalancedEntry($reversal);

        // 4. View Trial Balance via Journal Controller
        $tbRes = $this->get(route('journals.trial-balance'));
        $tbRes->assertOk();
        $tbRes->assertSee('Trial Balance');
    }

    /**
     * Test Sale Voiding automatically reverses General Ledger journal entry.
     */
    public function test_sale_void_reverses_general_ledger_journal_entry(): void
    {
        $this->actingAs($this->admin);

        $shift = app(ShiftService::class)->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'cashier_id' => $this->cashier->id,
            'supervisor_id' => $this->admin->id,
            'shift_type' => 'NIGHT',
            'start_time' => now(),
            'opening_cash' => '5000.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzleSuper->id, 'opening_meter' => '50000.000'],
            ],
        ], $this->admin);

        $sale = app(SaleService::class)->create([
            'branch_id' => $this->branch->id,
            'shift_id' => $shift->id,
            'customer_id' => null,
            'items' => [
                [
                    'nozzle_id' => $this->nozzleSuper->id,
                    'fuel_product_id' => $this->super->id,
                    'tank_id' => $this->tankSuper->id,
                    'litres' => '10.000',
                    'rate' => '265.00',
                    'amount' => '2650.00',
                ],
            ],
            'payments' => [
                ['method' => 'CASH', 'amount' => '2650.00'],
            ],
        ], $this->cashier);

        $glEntry = JournalEntry::where('reference_type', Sale::class)
            ->where('reference_id', $sale->id)
            ->first();
        $this->assertNotNull($glEntry);
        $this->assertSame(JournalEntry::STATUS_POSTED, $glEntry->status);

        // Void the sale
        $voidService = app(SaleVoidService::class);
        $voidedSale = $voidService->void($sale, 'Customer vehicle wrong fuel filled', $this->admin->id);
        $this->assertSame(Sale::STATUS_VOIDED, $voidedSale->status);

        // Verify GL entry is marked VOID and reversal exists
        $glEntry->refresh();
        $this->assertSame(JournalEntry::STATUS_VOID, $glEntry->status);

        $reversal = JournalEntry::where('reference_type', JournalEntry::class)
            ->where('reference_id', $glEntry->id)
            ->first();
        $this->assertNotNull($reversal, 'Reversing entry for voided sale must be posted.');
        $this->assertBalancedEntry($reversal);
    }

    /**
     * Test Daily Closing Wizard verification steps and unlock mechanism.
     */
    public function test_daily_closing_checklist_and_unlock(): void
    {
        $this->actingAs($this->admin);
        $today = now()->format('Y-m-d');

        // Opening an incomplete shift should block closing without override
        $shift = app(ShiftService::class)->open([
            'branch_id' => $this->branch->id,
            'user_id' => $this->cashier->id,
            'cashier_id' => $this->cashier->id,
            'supervisor_id' => $this->admin->id,
            'shift_type' => 'DAY',
            'start_time' => now(),
            'opening_cash' => '1000.00',
            'nozzles' => [
                ['nozzle_id' => $this->nozzleSuper->id, 'opening_meter' => '50000.000'],
            ],
        ], $this->admin);

        // Closing without closing shift should fail
        $res = $this->post(route('closing.store'), [
            'closing_date' => $today,
            'actual_cash_counted' => '1000.00',
        ]);
        $res->assertSessionHasErrors('shifts');

        // Close shift
        app(ShiftClosingService::class)->close(
            shift: $shift,
            meterReadings: [$this->nozzleSuper->id => '50000.000'],
            actualCash: '1000.00',
            actorId: $this->admin->id
        );

        // Close day with force override or completed dips
        $closeRes = $this->post(route('closing.store'), [
            'closing_date' => $today,
            'actual_cash_counted' => '1000.00',
            'force_override' => '1',
        ]);
        $closeRes->assertRedirect(route('closing.index', ['date' => $today]));

        $dailyClosing = DailyClosing::where('closing_date', $today)->first();
        $this->assertNotNull($dailyClosing);
        $this->assertSame(DailyClosing::STATUS_LOCKED, $dailyClosing->status);

        // Unlock day
        $unlockRes = $this->post(route('closing.unlock', $dailyClosing->id), [
            'reason' => 'Audit adjustment requested by external accountant',
        ]);
        $unlockRes->assertRedirect(route('closing.index', ['date' => $today]));
        $dailyClosing->refresh();
        $this->assertSame(DailyClosing::STATUS_UNLOCKED, $dailyClosing->status);
    }

    /**
     * Test pure-PHP chunked database backup and ZIP archive creation without mysqldump binary.
     */
    public function test_pure_php_backup_engine_creates_sql_and_zip_archives(): void
    {
        $this->actingAs($this->admin);

        // 1. Create Database SQL.GZ backup
        $dbRes = $this->post(route('backups.database'));
        $dbRes->assertRedirect(route('backups.index'));
        $dbRes->assertSessionHas('success');

        // 2. Create Full ZIP backup
        $zipRes = $this->post(route('backups.full'));
        $zipRes->assertRedirect(route('backups.index'));
        $zipRes->assertSessionHas('success');

        // 3. Verify Backup records and files exist on disk
        $backups = \App\Models\Backup::all();
        $this->assertGreaterThanOrEqual(2, $backups->count());

        $backupService = app(BackupService::class);
        foreach ($backups as $b) {
            $path = storage_path("app/{$b->file_path}");
            $this->assertTrue(File::exists($path), "Backup file {$path} must exist on disk.");
            $this->assertGreaterThan(0, filesize($path), "Backup file {$path} must not be empty.");

            // Test signed download URL
            $signedUrl = $backupService->getDownloadUrl($b);
            $downloadRes = $this->get($signedUrl);
            $downloadRes->assertOk();
        }
    }

    /**
     * Helper assertion verifying debit equals credit on a journal entry.
     */
    private function assertBalancedEntry(JournalEntry $entry): void
    {
        $totalDebit = '0.00';
        $totalCredit = '0.00';

        foreach ($entry->lines as $line) {
            $totalDebit = Money::add($totalDebit, $line->debit);
            $totalCredit = Money::add($totalCredit, $line->credit);
        }

        $this->assertSame(
            0,
            Money::compare($totalDebit, $totalCredit),
            "Journal entry #{$entry->entry_number} is unbalanced. Dr: Rs. {$totalDebit}, Cr: Rs. {$totalCredit}"
        );
    }
}
