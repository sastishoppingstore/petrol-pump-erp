<?php

namespace Tests\Feature;

use App\Models\Tank;
use App\Models\FuelProduct;
use App\Models\InternalFuelConsumption;
use App\Models\TankMovement;
use App\Services\Fuel\GeneratorFuelService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GeneratorFuelServiceTest extends TestCase
{
    use RefreshDatabase;

    private GeneratorFuelService $fuelService;
    private Tank $tank;
    private FuelProduct $fuel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fuelService = app(GeneratorFuelService::class);

        // Create test fuel
        $this->fuel = FuelProduct::factory()->create([
            'name' => 'Diesel',
            'code' => 'DSL',
        ]);

        // Create test tank
        $this->tank = Tank::factory()->create([
            'name' => 'Tank A',
            'fuel_product_id' => $this->fuel->id,
            'capacity' => 5000,
            'current_stock' => 3000,
            'status' => 'ACTIVE',
        ]);
    }

    /**
     * Test recording generator fuel consumption
     */
    public function test_record_generator_consumption()
    {
        $branchId = 1;
        $litres = 50.5;
        $date = now()->subDay();

        $consumption = $this->fuelService->recordConsumption(
            $this->tank,
            'GENERATOR',
            $litres,
            $date,
            'Daily generator operation',
            $branchId
        );

        $this->assertNotNull($consumption->id);
        $this->assertEquals('GENERATOR', $consumption->consumption_type);
        $this->assertEquals($litres, $consumption->litres_consumed);
        $this->assertDatabaseHas('internal_fuel_consumptions', [
            'tank_id' => $this->tank->id,
            'consumption_type' => 'GENERATOR',
            'litres_consumed' => $litres,
        ]);

        // Verify tank stock decreased
        $this->tank->refresh();
        $this->assertEquals(3000 - $litres, $this->tank->current_stock);

        // Verify movement created
        $this->assertDatabaseHas('tank_movements', [
            'tank_id' => $this->tank->id,
            'type' => 'LOSS',
            'reference_type' => 'internal_fuel_consumption',
            'reference_id' => $consumption->id,
        ]);
    }

    /**
     * Test recording fails if insufficient stock
     */
    public function test_insufficient_stock_throws_error()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Insufficient stock');

        $this->fuelService->recordConsumption(
            $this->tank,
            'GENERATOR',
            4000, // More than available (3000)
            now(),
            'Test',
            1
        );
    }

    /**
     * Test different consumption types
     */
    public function test_all_consumption_types()
    {
        $types = ['GENERATOR', 'STATION_VEHICLE', 'TESTING', 'CLEANING'];

        foreach ($types as $type) {
            // Reset stock
            $this->tank->update(['current_stock' => 3000]);

            $consumption = $this->fuelService->recordConsumption(
                $this->tank,
                $type,
                10,
                now(),
                "Test $type",
                1
            );

            $this->assertEquals($type, $consumption->consumption_type);
        }
    }

    /**
     * Test daily consumption summary
     */
    public function test_daily_consumption_summary()
    {
        $date = now()->toDateString();

        // Record multiple consumptions
        $this->fuelService->recordConsumption(
            $this->tank,
            'GENERATOR',
            100,
            new \DateTime($date),
            'Morning generator',
            1
        );

        $this->fuelService->recordConsumption(
            $this->tank,
            'TESTING',
            20,
            new \DateTime($date),
            'Quality test',
            1
        );

        $summary = $this->fuelService->getDailyConsumptionSummary(
            new \DateTime($date),
            1
        );

        $this->assertEquals(120, $summary['total_litres']);
        $this->assertEquals(100, $summary['by_type']['GENERATOR']);
        $this->assertEquals(20, $summary['by_type']['TESTING']);
    }

    /**
     * Test consumption history retrieval
     */
    public function test_consumption_history()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        // Record multiple consumptions
        for ($i = 1; $i <= 3; $i++) {
            $this->fuelService->recordConsumption(
                $this->tank,
                'GENERATOR',
                50,
                $startDate->copy()->addDays($i),
                "Generator run $i",
                1
            );
        }

        $history = $this->fuelService->getConsumptionHistory(
            $startDate,
            $endDate,
            $this->tank->id
        );

        $this->assertEquals(3, $history->count());
    }

    /**
     * Test generator fuel cost calculation
     */
    public function test_generator_fuel_cost()
    {
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();

        $this->fuelService->recordConsumption(
            $this->tank,
            'GENERATOR',
            100,
            $startDate,
            'Generator',
            1
        );

        $cost = $this->fuelService->getGeneratorFuelCost(
            $startDate,
            $endDate,
            1
        );

        // Cost should be litres * average cost per litre
        $this->assertGreaterThan(0, $cost);
    }

    /**
     * Test monthly generator report
     */
    public function test_monthly_generator_report()
    {
        $year = now()->year;
        $month = now()->month;
        $startDate = Carbon::create($year, $month, 1);

        // Record consumptions across the month
        for ($day = 1; $day <= 10; $day++) {
            $this->tank->update(['current_stock' => 3000]); // Reset
            $this->fuelService->recordConsumption(
                $this->tank,
                'GENERATOR',
                50,
                $startDate->copy()->addDays($day - 1),
                "Generator day $day",
                1
            );
        }

        $report = $this->fuelService->getMonthlyGeneratorReport($year, $month, 1);

        $this->assertArrayHasKey('total_litres', $report);
        $this->assertArrayHasKey('total_cost', $report);
        $this->assertArrayHasKey('average_daily_litres', $report);
        $this->assertGreaterThan(0, $report['total_litres']);
    }

    /**
     * Test budget estimate
     */
    public function test_budget_estimate()
    {
        $estimate = $this->fuelService->estimateBudget(3, 1);

        $this->assertArrayHasKey('average_monthly_litres', $estimate);
        $this->assertArrayHasKey('average_monthly_cost', $estimate);
        $this->assertArrayHasKey('estimated_next_month_litres', $estimate);
        $this->assertArrayHasKey('estimated_next_month_cost', $estimate);
    }

    /**
     * Test reversing consumption
     */
    public function test_reverse_consumption()
    {
        $branchId = 1;
        $litres = 50;

        // Record consumption
        $consumption = $this->fuelService->recordConsumption(
            $this->tank,
            'GENERATOR',
            $litres,
            now(),
            'Generator run',
            $branchId
        );

        $stockAfterConsumption = $this->tank->fresh()->current_stock;

        // Reverse it
        $reversal = $this->fuelService->reverseConsumption($consumption, 'Accidental entry');

        $stockAfterReversal = $this->tank->fresh()->current_stock;

        // Stock should be restored
        $this->assertEquals(3000, $stockAfterReversal);
        $this->assertTrue($reversal->isReversal());
    }

    /**
     * Test valid consumption type validation
     */
    public function test_is_valid_consumption_type()
    {
        $this->assertTrue($this->fuelService->isValidConsumptionType('GENERATOR'));
        $this->assertTrue($this->fuelService->isValidConsumptionType('STATION_VEHICLE'));
        $this->assertTrue($this->fuelService->isValidConsumptionType('TESTING'));
        $this->assertTrue($this->fuelService->isValidConsumptionType('CLEANING'));
        $this->assertFalse($this->fuelService->isValidConsumptionType('INVALID'));
    }

    /**
     * Test get valid consumption types
     */
    public function test_get_valid_consumption_types()
    {
        $types = $this->fuelService->getValidConsumptionTypes();

        $this->assertContains('GENERATOR', $types);
        $this->assertContains('STATION_VEHICLE', $types);
        $this->assertContains('TESTING', $types);
        $this->assertContains('CLEANING', $types);
        $this->assertCount(4, $types);
    }
}
