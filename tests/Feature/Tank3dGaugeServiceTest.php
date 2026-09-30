<?php

namespace Tests\Feature;

use App\Models\Tank;
use App\Models\Branch;
use App\Models\FuelProduct;
use App\Services\Visualization\Tank3dGaugeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Tank3dGaugeServiceTest extends TestCase
{
    use RefreshDatabase;

    private Tank3dGaugeService $gaugeService;
    private Tank $tank;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gaugeService = app(Tank3dGaugeService::class);

        // Create branch
        $branch = Branch::factory()->create();

        // Create fuel
        $fuel = FuelProduct::factory()->create([
            'name' => 'Petrol',
            'code' => 'PTL',
        ]);

        // Create tank
        $this->tank = Tank::factory()->create([
            'branch_id' => $branch->id,
            'fuel_product_id' => $fuel->id,
            'name' => 'Tank A',
            'capacity' => 5000,
            'current_stock' => 2500, // 50%
        ]);
    }

    /**
     * Test generate gauge data
     */
    public function test_generate_gauge_data()
    {
        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertArrayHasKey('tank_id', $gaugeData);
        $this->assertArrayHasKey('tank_name', $gaugeData);
        $this->assertArrayHasKey('current_stock', $gaugeData);
        $this->assertArrayHasKey('capacity', $gaugeData);
        $this->assertArrayHasKey('stock_percent', $gaugeData);
        $this->assertArrayHasKey('3d', $gaugeData);
        $this->assertArrayHasKey('wave', $gaugeData);
        $this->assertArrayHasKey('gradient', $gaugeData);
        $this->assertArrayHasKey('status', $gaugeData);
        $this->assertArrayHasKey('markings', $gaugeData);
    }

    /**
     * Test stock percentage calculation
     */
    public function test_stock_percent_calculation()
    {
        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        // 2500 / 5000 = 50%
        $this->assertEquals(50.0, $gaugeData['stock_percent']);
    }

    /**
     * Test color coding by stock level
     */
    public function test_color_green_for_high_stock()
    {
        $this->tank->update(['current_stock' => 4000]); // 80%

        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertEquals('#27ae60', $gaugeData['gradient']['stops'][0]['color']);
    }

    /**
     * Test color yellow for medium stock
     */
    public function test_color_yellow_for_medium_stock()
    {
        $this->tank->update(['current_stock' => 1500]); // 30%

        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertEquals('#f39c12', $gaugeData['gradient']['stops'][0]['color']);
    }

    /**
     * Test color red for low stock
     */
    public function test_color_red_for_low_stock()
    {
        $this->tank->update(['current_stock' => 500]); // 10%

        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertEquals('#e74c3c', $gaugeData['gradient']['stops'][0]['color']);
    }

    /**
     * Test level label generation
     */
    public function test_level_label_full()
    {
        $this->tank->update(['current_stock' => 4750]); // 95%
        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);
        $this->assertEquals('Full', $gaugeData['status']['level_label']);
    }

    /**
     * Test level label high
     */
    public function test_level_label_high()
    {
        $this->tank->update(['current_stock' => 3500]); // 70%
        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);
        $this->assertEquals('High', $gaugeData['status']['level_label']);
    }

    /**
     * Test critical low stock warning
     */
    public function test_critical_low_stock_warning()
    {
        $this->tank->update(['current_stock' => 400]); // 8%

        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertTrue($gaugeData['status']['critical']);
        $this->assertEquals('critical_low_stock', $gaugeData['status']['warning']);
    }

    /**
     * Test wave animation config
     */
    public function test_wave_animation_config()
    {
        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertTrue($gaugeData['wave']['enabled']);
        $this->assertArrayHasKey('amplitude', $gaugeData['wave']);
        $this->assertArrayHasKey('frequency', $gaugeData['wave']);
        $this->assertArrayHasKey('speed', $gaugeData['wave']);
    }

    /**
     * Test wave amplitude increases with stock level
     */
    public function test_wave_amplitude_dynamic()
    {
        $lowStockData = $this->gaugeService->generateGaugeData($this->tank); // 50%

        $this->tank->update(['current_stock' => 4000]); // 80%
        $highStockData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertLessThan(
            $highStockData['wave']['amplitude'],
            $lowStockData['wave']['amplitude']
        );
    }

    /**
     * Test markings generation
     */
    public function test_generate_markings()
    {
        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertCount(4, $gaugeData['markings']); // 25%, 50%, 75%, 100%
        $this->assertEquals(25, $gaugeData['markings'][0]['percent']);
        $this->assertEquals(1250, $gaugeData['markings'][0]['litres']);
    }

    /**
     * Test multiple gauges generation
     */
    public function test_generate_multiple_gauges()
    {
        $fuel = FuelProduct::factory()->create();
        $tank2 = Tank::factory()->create([
            'fuel_product_id' => $fuel->id,
            'capacity' => 3000,
            'current_stock' => 1500,
        ]);

        $tanks = collect([$this->tank, $tank2]);
        $gauges = $this->gaugeService->generateMultipleGauges($tanks);

        $this->assertCount(2, $gauges);
        $this->assertEquals($this->tank->id, $gauges[0]['tank_id']);
        $this->assertEquals($tank2->id, $gauges[1]['tank_id']);
    }

    /**
     * Test wave keyframes generation
     */
    public function test_get_wave_keyframes()
    {
        $keyframes = $this->gaugeService->getWaveKeyframes(2000);

        $this->assertCount(60, $keyframes);
        $this->assertEquals(0, $keyframes[0]['percent']);
        $this->assertEquals(100, $keyframes[59]['percent']);
    }

    /**
     * Test wave animation CSS generation
     */
    public function test_get_wave_animation_css()
    {
        $css = $this->gaugeService->getWaveAnimationCss('test-wave');

        $this->assertStringContainsString('@keyframes test-wave', $css);
        $this->assertStringContainsString('animation: test-wave 2s ease-in-out infinite', $css);
    }

    /**
     * Test chart.js compatible data
     */
    public function test_get_chartjs_data()
    {
        $fuel = FuelProduct::factory()->create();
        $tank2 = Tank::factory()->create([
            'fuel_product_id' => $fuel->id,
            'capacity' => 3000,
            'current_stock' => 2100, // 70%
        ]);

        $tanks = collect([$this->tank, $tank2]);
        $chartData = $this->gaugeService->getChartJsData($tanks);

        $this->assertArrayHasKey('labels', $chartData);
        $this->assertArrayHasKey('datasets', $chartData);
        $this->assertCount(2, $chartData['labels']);
        $this->assertEquals(50, $chartData['datasets'][0]['data'][0]);
        $this->assertEquals(70, $chartData['datasets'][0]['data'][1]);
    }

    /**
     * Test tooltip generation
     */
    public function test_generate_tooltip()
    {
        $tooltip = $this->gaugeService->generateTooltip($this->tank);

        $this->assertStringContainsString('Tank A', $tooltip);
        $this->assertStringContainsString('2500L', $tooltip);
        $this->assertStringContainsString('5000L', $tooltip);
        $this->assertStringContainsString('50%', $tooltip);
    }

    /**
     * Test status indicators
     */
    public function test_status_indicators()
    {
        $this->tank->update(['current_stock' => 2500]); // 50%
        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $this->assertFalse($gaugeData['status']['critical']);
        $this->assertFalse($gaugeData['status']['full']);
        $this->assertEquals('optimal', $gaugeData['status']['level_status']);
    }

    /**
     * Test gradient generation
     */
    public function test_gradient_generation()
    {
        $gaugeData = $this->gaugeService->generateGaugeData($this->tank);

        $gradient = $gaugeData['gradient'];
        $this->assertEquals('linear', $gradient['type']);
        $this->assertCount(4, $gradient['stops']);
    }
}
