<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\FuelProduct;
use App\Models\Province;
use App\Models\TaxRate;
use App\Services\Accounting\TaxService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * Provincial sales tax.
 *
 * Pakistan levies sales tax through the provincial body (PRA/SRB/KPRA/BRA),
 * not FBR, and fuel is taxed differently in each province. The rate must come
 * from the server-side history, never the browser.
 */
class ProvincialTaxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ProvinceSeeder::class);
    }

    private function province(string $code): Province
    {
        return Province::where('code', $code)->firstOrFail();
    }

    private function branchIn(Province $province, string $code = 'BR-01'): Branch
    {
        return Branch::factory()->create(['code' => $code, 'province_id' => $province->id]);
    }

    private function setRate(Province $province, string $rate, ?FuelProduct $fuel = null, ?\DateTimeInterface $from = null): TaxRate
    {
        return TaxRate::create([
            'province_id' => $province->id,
            'fuel_product_id' => $fuel?->id,
            'rate' => $rate,
            'effective_from' => $from ?? now()->subDay(),
            'effective_to' => null,
            'source' => 'Test',
        ]);
    }

    public function test_each_province_maps_to_its_tax_authority(): void
    {
        $this->assertSame(Province::PRA, $this->province('PB')->tax_authority);
        $this->assertSame(Province::SRB, $this->province('SD')->tax_authority);
        $this->assertSame(Province::KPRA, $this->province('KPK')->tax_authority);
        $this->assertSame(Province::BRA, $this->province('BA')->tax_authority);
    }

    public function test_the_rate_is_resolved_from_the_branches_province(): void
    {
        $punjab = $this->province('PB');
        $sindh = $this->province('SD');

        $this->setRate($punjab, '16.00');
        $this->setRate($sindh, '18.00');

        $service = app(TaxService::class);

        $this->assertSame('16.00', $service->rateFor($this->branchIn($punjab, 'P1')));
        $this->assertSame('18.00', $service->rateFor($this->branchIn($sindh, 'S1')));
    }

    public function test_a_fuel_specific_rate_beats_the_province_default(): void
    {
        $province = $this->province('PB');
        $petrol = FuelProduct::factory()->create(['name' => 'Petrol']);
        $diesel = FuelProduct::factory()->create(['name' => 'Diesel']);

        $this->setRate($province, '16.00');                 // default
        $this->setRate($province, '10.00', $petrol);        // petrol override

        $branch = $this->branchIn($province, 'P2');
        $service = app(TaxService::class);

        $this->assertSame('10.00', $service->rateFor($branch, $petrol), 'Petrol uses its own rate.');
        $this->assertSame('16.00', $service->rateFor($branch, $diesel), 'Diesel falls back to the default.');
    }

    public function test_the_rate_in_force_at_the_time_of_sale_is_used(): void
    {
        $province = $this->province('PB');
        $branch = $this->branchIn($province, 'P3');
        $service = app(TaxService::class);

        $this->setRate($province, '16.00', null, now()->subMonths(2));
        TaxRate::where('province_id', $province->id)->update(['effective_to' => now()->subDay()]);
        $this->setRate($province, '18.00', null, now()->subDay());

        $this->assertSame('16.00', $service->rateFor($branch, null, now()->subMonths(1)));
        $this->assertSame('18.00', $service->rateFor($branch));
    }

    public function test_a_branch_without_a_province_is_refused(): void
    {
        $branch = Branch::factory()->create(['code' => 'NOPROV', 'province_id' => null]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/no province set/');

        app(TaxService::class)->rateFor($branch);
    }

    public function test_a_province_without_a_rate_is_refused(): void
    {
        $branch = $this->branchIn($this->province('BA'), 'B1');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessageMatches('/No sales tax rate is configured/');

        app(TaxService::class)->rateFor($branch);
    }

    public function test_tax_on_a_net_amount(): void
    {
        $service = app(TaxService::class);

        $this->assertSame('400.00', $service->taxOn('2500.00', '16.00'));
        $this->assertSame('450.00', $service->taxOn('2500.00', '18.00'));
        $this->assertSame('0.00', $service->taxOn('2500.00', '0.00'));
    }

    public function test_tax_is_rounded_to_two_places(): void
    {
        $service = app(TaxService::class);

        // 1234.56 x 16% = 197.5296 -> 197.53
        $this->assertSame('197.53', $service->taxOn('1234.56', '16.00'));
        // 0.05 x 18% = 0.009 -> 0.01
        $this->assertSame('0.01', $service->taxOn('0.05', '18.00'));
    }

    public function test_net_of_a_tax_inclusive_amount(): void
    {
        $service = app(TaxService::class);

        // 2900 incl 16% -> 2500.00
        $this->assertSame('2500.00', $service->netOf('2900.00', '16.00'));
        // 2950 incl 18% -> 2500.00
        $this->assertSame('2500.00', $service->netOf('2950.00', '18.00'));
    }

    /**
     * The split must always add back to the gross figure exactly. If it does
     * not, the invoice will not foot.
     */
    public function test_split_inclusive_always_foots_to_the_gross_figure(): void
    {
        $service = app(TaxService::class);

        foreach ([
            ['2900.00', '16.00'],
            ['2950.00', '18.00'],
            ['1234.56', '16.00'],
            ['1.00', '18.00'],
            ['0.01', '5.00'],
            ['99999.99', '16.00'],
        ] as [$gross, $rate]) {
            $split = $service->splitInclusive($gross, $rate);

            $this->assertSame(
                $gross,
                \App\Support\Money::add($split['net'], $split['tax']),
                "Split of {$gross} at {$rate}% does not foot."
            );
        }
    }

    public function test_split_inclusive_derives_tax_by_subtraction(): void
    {
        $service = app(TaxService::class);

        $split = $service->splitInclusive('2900.00', '16.00');

        $this->assertSame('2500.00', $split['net']);
        $this->assertSame('400.00', $split['tax']);
    }

    public function test_authority_is_resolved_from_the_branch(): void
    {
        $service = app(TaxService::class);

        $this->assertSame(
            Province::PRA,
            $service->authorityFor($this->branchIn($this->province('PB'), 'A1')),
        );

        $this->assertSame(
            Province::KPRA,
            $service->authorityFor($this->branchIn($this->province('KPK'), 'A2')),
        );
    }

    public function test_tax_rates_are_never_deleted_only_superseded(): void
    {
        $province = $this->province('PB');
        $this->setRate($province, '16.00');

        $new = $this->setRate($province, '18.00');

        TaxRate::where('province_id', $province->id)
            ->where('id', '!=', $new->id)
            ->update(['effective_to' => $new->effective_from]);

        // Both rows survive: history is closed off, never rewritten.
        $this->assertSame(2, TaxRate::where('province_id', $province->id)->count());
        $this->assertSame('18.00', app(TaxService::class)->rateFor($this->branchIn($province, 'A3')));
    }
}
