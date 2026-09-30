<?php

namespace Tests\Feature;

use App\Livewire\Dashboard\AppLauncher;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\CustomerVehicle;
use App\Models\Expense;
use App\Models\FuelProduct;
use App\Models\Role;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Shift;
use App\Models\Tank;
use App\Models\User;
use App\Services\Dashboard\DashboardMetricsService;
use App\Support\UrduNumber;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardAppLauncherTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $cashier;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create([
            'name' => 'Mehar Filling Station',
            'code' => 'MEHAR1',
            'status' => Branch::STATUS_ACTIVE,
        ]);

        $this->admin = User::factory()->withRole(Role::ADMIN)->create([
            'name' => 'Muhammad Rizwan Aslam',
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->admin->branches()->attach($this->branch->id, ['is_default' => true]);

        $this->cashier = User::factory()->withRole(Role::CASHIER)->create([
            'name' => 'Ali Raza Cashier',
            'employee_code' => 'EMP-007',
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->cashier->branches()->attach($this->branch->id, ['is_default' => true]);
        $this->cashier->setPin('4321');
        $this->cashier->save();
    }

    public function test_dashboard_renders_mobile_app_launcher_for_authenticated_user(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/dashboard');

        $response->assertOk();
        $response->assertSee('VITAL');
        $response->assertSee('مہر فلنگ اسٹیشن');
        $response->assertSee('کل سیل');
        $response->assertSee('کیش ہاتھ میں');
        $response->assertSee('منافع');
        $response->assertSee('میٹر ریڈنگ');
    }

    public function test_hero_card_calculates_today_sales_profit_and_cash_from_real_database(): void
    {
        // 1. Create a sale today for Rs. 4,50,000
        Sale::create([
            'branch_id' => $this->branch->id,
            'invoice_number' => 'INV-TEST-001',
            'sale_date' => Carbon::today(),
            'sale_mode' => 'LITRES',
            'subtotal' => '450000.00',
            'discount' => '0.00',
            'tax' => '0.00',
            'total' => '450000.00',
            'total_litres' => '1500.000',
            'total_cost' => '400000.00',
            'status' => 'COMPLETED',
        ]);

        // 2. Create yesterday's sale for Rs. 3,00,000
        Sale::create([
            'branch_id' => $this->branch->id,
            'invoice_number' => 'INV-TEST-002',
            'sale_date' => Carbon::yesterday(),
            'sale_mode' => 'LITRES',
            'subtotal' => '300000.00',
            'discount' => '0.00',
            'tax' => '0.00',
            'total' => '300000.00',
            'total_litres' => '1000.000',
            'total_cost' => '270000.00',
            'status' => 'COMPLETED',
        ]);

        $service = app(DashboardMetricsService::class);
        $metrics = $service->getMetrics($this->branch->id, $this->admin);

        $this->assertSame('450000.00', $metrics['hero']['today_sales']);
        $this->assertSame('Rs. 4,50,000', $metrics['hero']['today_sales_formatted']);
        $this->assertSame('چار لاکھ پچاس ہزار روپے صرف', $metrics['hero']['today_sales_words']);
        $this->assertSame('up', $metrics['hero']['sales_trend_dir']);
        $this->assertSame('+50%', $metrics['hero']['sales_trend']);
        // Profit: 450,000 - 400,000 = 50,000
        $this->assertSame('50000.00', $metrics['hero']['profit']);
        $this->assertSame('Rs. 50,000', $metrics['hero']['profit_formatted']);
    }

    public function test_active_shift_card_displays_live_shift_metrics(): void
    {
        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->cashier->id,
            'shift_number' => 'SH-2026-001',
            'opened_at' => now()->subHours(4),
            'opening_cash' => '25000.00',
            'total_sales' => '120000.00',
            'total_litres' => '400.000',
            'status' => Shift::STATUS_OPEN,
        ]);

        $service = app(DashboardMetricsService::class);
        $shiftInfo = $service->getCurrentShiftInfo($this->cashier, $this->branch->id);

        $this->assertNotNull($shiftInfo);
        $this->assertSame('SH-2026-001', $shiftInfo['shift_number']);
        $this->assertSame('Ali Raza Cashier', $shiftInfo['cashier_name']);
        $this->assertSame('400.000', $shiftInfo['litres_sold']);
        $this->assertSame(route('shifts.close.form', $shift->id), $shiftInfo['close_url']);
    }

    public function test_tanks_stock_visualization_returns_capacities_and_warning_levels(): void
    {
        $fuel = FuelProduct::create([
            'code' => 'HSD',
            'name' => 'High Speed Diesel',
            'unit' => 'LITRE',
            'color' => '#2563EB',
            'selling_price' => '300.00',
            'average_cost' => '280.00',
            'status' => 'ACTIVE',
        ]);

        $tank = Tank::create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $fuel->id,
            'tank_number' => 'T-02',
            'name' => 'ٹینک 2 ڈیزل',
            'capacity' => '30000.000',
            'current_stock' => '6600.000', // 22% -> Warning!
            'low_stock_threshold' => '7500.000',
            'status' => 'ACTIVE',
        ]);

        $service = app(DashboardMetricsService::class);
        $tanks = $service->getTankStockLevels($this->branch->id);

        $this->assertNotEmpty($tanks);
        $dieselTank = $tanks->firstWhere('id', $tank->id);
        $this->assertNotNull($dieselTank);
        $this->assertSame(22.0, $dieselTank['percentage']);
        $this->assertTrue($dieselTank['warning']);
        $this->assertSame('WARNING', $dieselTank['status']);
    }

    public function test_role_based_filtering_for_cashier_vs_owner(): void
    {
        // 1. As Cashier: only ~6 tiles, no owner summary card
        $this->actingAs($this->cashier);

        Livewire::test(AppLauncher::class)
            ->assertSee('میٹر ریڈنگ')
            ->assertSee('نیا بل')
            ->assertSee('گاہک / ادھار')
            ->assertSee('رقم آئی')
            ->assertSee('شفٹ')
            ->assertSee('رپورٹس')
            ->assertDontSee('مالک کا خلاصہ');

        // 2. As Admin/Owner: sees all 18 tiles and "مالک کا خلاصہ"
        $this->actingAs($this->admin);

        Livewire::test(AppLauncher::class)
            ->assertSee('مالک کا خلاصہ')
            ->assertSee('بینک')
            ->assertSee('تیل خریداری')
            ->assertSee('سپلائر')
            ->assertSee('اخراجات')
            ->assertSee('ترتیبات');
    }

    public function test_live_search_queries_customers_sales_and_vehicles(): void
    {
        $this->actingAs($this->admin);

        // 1. Customer
        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'code' => 'CUST-001',
            'name' => 'Tariq Mehmood Malik',
            'phone' => '03001234567',
            'current_balance' => '45000.00',
            'status' => 'ACTIVE',
        ]);

        // 2. Vehicle
        CustomerVehicle::create([
            'customer_id' => $customer->id,
            'registration_number' => 'LEA-7890',
            'make' => 'Toyota',
            'model' => 'Corolla',
            'colour' => 'White',
            'type' => 'Car',
            'status' => 'ACTIVE',
        ]);

        // 3. Sale
        Sale::create([
            'branch_id' => $this->branch->id,
            'invoice_number' => 'INV-777888',
            'sale_date' => Carbon::today(),
            'sale_mode' => 'LITRES',
            'subtotal' => '5000.00',
            'discount' => '0.00',
            'tax' => '0.00',
            'total' => '5000.00',
            'total_litres' => '17.500',
            'total_cost' => '4500.00',
            'status' => 'COMPLETED',
        ]);

        Livewire::test(AppLauncher::class)
            ->set('searchQuery', 'Tariq')
            ->assertSee('Tariq Mehmood Malik')
            ->set('searchQuery', 'LEA-78')
            ->assertSee('LEA-7890')
            ->set('searchQuery', 'INV-777')
            ->assertSee('INV-777888');
    }

    public function test_quick_action_cash_in(): void
    {
        $this->actingAs($this->admin);

        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->admin->id,
            'shift_number' => 'SH-QUICK',
            'opened_at' => now(),
            'opening_cash' => '10000.00',
            'status' => Shift::STATUS_OPEN,
        ]);

        Livewire::test(AppLauncher::class)
            ->call('openQuickAction', 'cash_in')
            ->set('quickAmount', '5000.00')
            ->set('quickNotes', 'Test Cash In')
            ->call('submitQuickAction')
            ->assertSee('رقم موصول درج ہوگئی');

        $shift->refresh();
        $this->assertSame('15000.00', $shift->opening_cash);
    }

    public function test_language_and_simple_mode_toggles(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AppLauncher::class)
            ->assertSet('lang', 'ur')
            ->call('toggleLanguage')
            ->assertSet('lang', 'en')
            ->assertSet('simpleMode', false)
            ->call('toggleSimpleMode')
            ->assertSet('simpleMode', true);
    }

    public function test_voice_search_handler_updates_search_query_and_returns_matches(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'code' => 'CUST-VOICE',
            'name' => 'Waseem Akram Transporter',
            'phone' => '03219876543',
            'current_balance' => '120000.00',
            'status' => 'ACTIVE',
        ]);

        Livewire::test(AppLauncher::class)
            ->call('handleVoiceSearch', 'Waseem')
            ->assertSet('searchQuery', 'Waseem')
            ->assertSee('Waseem Akram Transporter')
            ->assertSee('CUST-VOICE');
    }

    public function test_quick_action_customer_payment_via_customer_ledger_service(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'branch_id' => $this->branch->id,
            'code' => 'CUST-PAY-01',
            'name' => 'Bilal Goods Sheikhupura',
            'phone' => '03112233445',
            'current_balance' => '75000.00',
            'opening_balance' => '75000.00',
            'status' => 'ACTIVE',
        ]);

        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->admin->id,
            'shift_number' => 'SH-PAY-01',
            'opened_at' => now(),
            'opening_cash' => '10000.00',
            'status' => Shift::STATUS_OPEN,
        ]);

        Livewire::test(AppLauncher::class)
            ->call('openQuickAction', 'customer_payment')
            ->set('quickCustomerId', $customer->id)
            ->set('quickAmount', '25000.00')
            ->set('quickNotes', 'Part payment received at pump')
            ->call('submitQuickAction')
            ->assertSee('گاہک ادائیگی درج ہوگئی')
            ->assertHasNoErrors();

        $customer->refresh();
        $this->assertSame('50000.00', $customer->current_balance);

        $this->assertDatabaseHas('customer_payments', [
            'customer_id' => $customer->id,
            'amount' => '25000.00',
            'branch_id' => $this->branch->id,
        ]);
    }

    public function test_quick_action_expense_via_expense_service(): void
    {
        $this->actingAs($this->admin);

        $shift = Shift::create([
            'branch_id' => $this->branch->id,
            'employee_id' => $this->admin->id,
            'shift_number' => 'SH-EXP-01',
            'opened_at' => now(),
            'opening_cash' => '10000.00',
            'status' => Shift::STATUS_OPEN,
        ]);

        Livewire::test(AppLauncher::class)
            ->call('openQuickAction', 'expense')
            ->set('quickAmount', '1200.00')
            ->set('quickNotes', 'Forecourt generator diesel filter')
            ->call('submitQuickAction')
            ->assertSee('خرچہ درج ہوگیا')
            ->assertHasNoErrors();

        $shift->refresh();
        $this->assertSame('1200.00', $shift->expenses_total);

        $this->assertDatabaseHas('expenses', [
            'branch_id' => $this->branch->id,
            'title' => 'Forecourt generator diesel filter',
            'amount' => '1200.00',
        ]);
    }

    public function test_quick_action_validation_fails_on_zero_amount(): void
    {
        $this->actingAs($this->admin);

        Livewire::test(AppLauncher::class)
            ->call('openQuickAction', 'cash_in')
            ->set('quickAmount', '0')
            ->call('submitQuickAction')
            ->assertSee('درست رقم درج کریں');
    }

    public function test_pin_screen_lock_and_unlock(): void
    {
        $this->actingAs($this->cashier);

        Livewire::test(AppLauncher::class)
            ->assertSet('isLocked', false)
            ->call('lockScreen')
            ->assertSet('isLocked', true)
            // Enter wrong PIN first
            ->call('enterLockDigit', '9')
            ->call('enterLockDigit', '9')
            ->call('enterLockDigit', '9')
            ->call('enterLockDigit', '9')
            ->assertSet('isLocked', true)
            ->assertSee('غلط سیکیورٹی پن!')
            // Now enter correct cashier PIN (4321)
            ->call('enterLockDigit', '4')
            ->call('enterLockDigit', '3')
            ->call('enterLockDigit', '2')
            ->call('enterLockDigit', '1')
            ->assertSet('isLocked', false);
    }

    public function test_verify_pin_endpoint_validation(): void
    {
        $this->actingAs($this->cashier);

        // Correct PIN
        $response = $this->postJson('/user/verify-pin', ['pin' => '4321']);
        $response->assertOk()
            ->assertJson(['valid' => true]);

        // Incorrect PIN
        $response = $this->postJson('/user/verify-pin', ['pin' => '9999']);
        $response->assertStatus(422)
            ->assertJson(['valid' => false]);
    }

    public function test_desktop_container_centering_and_phone_frame_classes_present(): void
    {
        $this->actingAs($this->admin);

        $response = $this->get('/dashboard');

        $response->assertOk();
        // Desktop centering outer frame: 360-430px centered frame
        $response->assertSee('max-w-[430px]', false);
        $response->assertSee('mx-auto', false);
        $response->assertSee('sm:rounded-[36px]', false);
    }
}
