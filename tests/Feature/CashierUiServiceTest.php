<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Shift;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Nozzle;
use App\Models\Dispenser;
use App\Models\Tank;
use App\Models\FuelProduct;
use App\Models\Sale;
use App\Services\Pos\CashierUiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashierUiServiceTest extends TestCase
{
    use RefreshDatabase;

    private CashierUiService $uiService;
    private Shift $shift;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->uiService = app(CashierUiService::class);

        // Create branch
        $this->branch = Branch::factory()->create([
            'name' => 'Main Branch',
            'code' => 'BRN-001',
        ]);

        // Create employee
        $employee = Employee::factory()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Cashier 1',
        ]);

        // Create shift
        $this->shift = Shift::factory()->create([
            'branch_id' => $this->branch->id,
            'employee_id' => $employee->id,
            'status' => 'OPEN',
            'opening_cash' => 5000,
        ]);

        // Create fuel and tank
        $fuel = FuelProduct::factory()->create([
            'name' => 'Petrol',
            'code' => 'PTL',
        ]);

        $tank = Tank::factory()->create([
            'branch_id' => $this->branch->id,
            'fuel_product_id' => $fuel->id,
            'capacity' => 5000,
            'current_stock' => 3000,
        ]);

        // Create dispenser and nozzle
        $dispenser = Dispenser::factory()->create([
            'branch_id' => $this->branch->id,
            'name' => 'Dispenser 1',
        ]);

        $nozzle = Nozzle::factory()->create([
            'dispenser_id' => $dispenser->id,
            'tank_id' => $tank->id,
            'fuel_product_id' => $fuel->id,
            'nozzle_number' => 1,
        ]);

        // Assign nozzle to shift
        $this->shift->nozzles()->attach($nozzle);
    }

    /**
     * Test get dashboard data
     */
    public function test_get_dashboard_data()
    {
        $dashboardData = $this->uiService->getDashboardData($this->shift);

        $this->assertArrayHasKey('tiles', $dashboardData);
        $this->assertArrayHasKey('shift_info', $dashboardData);
        $this->assertArrayHasKey('timestamp', $dashboardData);

        $tiles = $dashboardData['tiles'];
        $this->assertArrayHasKey('sales_today', $tiles);
        $this->assertArrayHasKey('fuel_sold', $tiles);
        $this->assertArrayHasKey('collections', $tiles);
        $this->assertArrayHasKey('margin', $tiles);
    }

    /**
     * Test dashboard tiles contain correct structure
     */
    public function test_dashboard_tiles_structure()
    {
        $dashboardData = $this->uiService->getDashboardData($this->shift);
        $tile = $dashboardData['tiles']['sales_today'];

        $this->assertArrayHasKey('label', $tile);
        $this->assertArrayHasKey('value', $tile);
        $this->assertArrayHasKey('icon', $tile);
        $this->assertArrayHasKey('color', $tile);
    }

    /**
     * Test get nozzle status
     */
    public function test_get_nozzle_status()
    {
        $nozzleStatus = $this->uiService->getNozzleStatus($this->shift);

        $this->assertIsArray($nozzleStatus);
        $this->assertNotEmpty($nozzleStatus);

        $nozzle = $nozzleStatus[0];
        $this->assertArrayHasKey('id', $nozzle);
        $this->assertArrayHasKey('nozzle_number', $nozzle);
        $this->assertArrayHasKey('fuel', $nozzle);
        $this->assertArrayHasKey('current_meter', $nozzle);
        $this->assertArrayHasKey('tank_stock', $nozzle);
        $this->assertArrayHasKey('stock_percent', $nozzle);
    }

    /**
     * Test keypad configuration
     */
    public function test_get_keypad_config()
    {
        $keypadConfig = $this->uiService->getKeypadConfig();

        $this->assertArrayHasKey('numbers', $keypadConfig);
        $this->assertArrayHasKey('operations', $keypadConfig);
        $this->assertArrayHasKey('layout', $keypadConfig);

        $this->assertCount(12, $keypadConfig['numbers']);
        $this->assertCount(4, $keypadConfig['operations']);
    }

    /**
     * Test banknote denominations
     */
    public function test_get_banknote_denominations()
    {
        $denominations = $this->uiService->getBanknoteDenominations();

        $this->assertCount(5, $denominations);
        $this->assertEquals(100, $denominations[0]['denomination']);
        $this->assertEquals(500, $denominations[1]['denomination']);
        $this->assertEquals(1000, $denominations[2]['denomination']);
        $this->assertEquals(5000, $denominations[3]['denomination']);
        $this->assertEquals(10000, $denominations[4]['denomination']);
    }

    /**
     * Test calculate banknote total
     */
    public function test_calculate_banknote_total()
    {
        $denominations = [
            ['denomination' => 100, 'count' => 5],
            ['denomination' => 500, 'count' => 2],
            ['denomination' => 1000, 'count' => 3],
        ];

        $total = $this->uiService->calculateBanknoteTotal($denominations);

        // (100 * 5) + (500 * 2) + (1000 * 3) = 500 + 1000 + 3000 = 4500
        $this->assertEquals(4500, $total);
    }

    /**
     * Test get payment methods
     */
    public function test_get_payment_methods()
    {
        $methods = $this->uiService->getPaymentMethods();

        $this->assertCount(5, $methods);
        $this->assertEquals('CASH', $methods[0]['method']);
        $this->assertEquals('CARD', $methods[1]['method']);
        $this->assertEquals('BANK_TRANSFER', $methods[2]['method']);
        $this->assertEquals('MOBILE_WALLET', $methods[3]['method']);
        $this->assertEquals('CREDIT', $methods[4]['method']);
    }

    /**
     * Test signature capture config
     */
    public function test_get_signature_capture_config()
    {
        $config = $this->uiService->getSignatureCaptureConfig();

        $this->assertArrayHasKey('enabled', $config);
        $this->assertArrayHasKey('required_for_credit', $config);
        $this->assertArrayHasKey('canvas_width', $config);
        $this->assertArrayHasKey('canvas_height', $config);
        $this->assertTrue($config['required_for_credit']);
    }

    /**
     * Test theme configuration
     */
    public function test_get_theme_config()
    {
        $theme = $this->uiService->getThemeConfig();

        $this->assertArrayHasKey('primary_color', $theme);
        $this->assertArrayHasKey('secondary_color', $theme);
        $this->assertArrayHasKey('accent_color', $theme);
        $this->assertArrayHasKey('button_height', $theme);
        $this->assertArrayHasKey('font_size_large', $theme);

        $this->assertEquals('#cc0000', $theme['primary_color']);
        $this->assertEquals('#ffffff', $theme['secondary_color']);
    }

    /**
     * Test dashboard caching
     */
    public function test_dashboard_caching()
    {
        $data1 = $this->uiService->getDashboardData($this->shift, 5);
        $data2 = $this->uiService->getDashboardData($this->shift, 5);

        // Both calls should return identical data (from cache)
        $this->assertEquals($data1, $data2);
    }

    /**
     * Test invalidate dashboard cache
     */
    public function test_invalidate_dashboard_cache()
    {
        $cacheKey = "cashier_dashboard_{$this->shift->id}_" . now()->format('YmdH');

        // Generate to populate cache
        $this->uiService->getDashboardData($this->shift, 5);
        $this->assertTrue(\Illuminate\Support\Facades\Cache::has($cacheKey));

        // Invalidate
        $this->uiService->invalidateDashboardCache($this->shift);
        $this->assertFalse(\Illuminate\Support\Facades\Cache::has($cacheKey));
    }

    /**
     * Test shift info in dashboard
     */
    public function test_shift_info_in_dashboard()
    {
        $dashboardData = $this->uiService->getDashboardData($this->shift);
        $shiftInfo = $dashboardData['shift_info'];

        $this->assertNotNull($shiftInfo['shift_number']);
        $this->assertNotNull($shiftInfo['opened_at']);
        $this->assertEquals(5000, $shiftInfo['opening_cash']);
    }

    /**
     * Test tile color indicates margin quality
     */
    public function test_margin_tile_color_by_quality()
    {
        $dashboardData = $this->uiService->getDashboardData($this->shift);
        $marginTile = $dashboardData['tiles']['margin'];

        // Default (no sales) should have a reasonable color
        $this->assertArrayHasKey('color', $marginTile);
        $this->assertStringStartsWith('#', $marginTile['color']);
    }
}
