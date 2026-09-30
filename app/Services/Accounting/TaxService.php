<?php

namespace App\Services\Accounting;

use App\Models\Branch;
use App\Models\FuelProduct;
use App\Models\Province;
use App\Models\TaxRate;
use App\Support\Decimal;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Provincial sales tax (Pakistan).
 *
 * Sales tax in Pakistan is levied by the provincial authority, not FBR, and
 * fuel is taxed differently in each province: PRA (Punjab), SRB (Sindh),
 * KPRA (Khyber Pakhtunkhwa) and BRA (Balochistan). A station's province
 * therefore decides both which authority it reports to and which rate applies.
 *
 * The rate is always resolved server-side from the tax_rates history, never
 * from a browser-supplied value.
 */
class TaxService
{
    /**
     * The tax rate percentage for a branch and fuel at a point in time.
     *
     * A fuel-specific rate wins over the province default.
     */
    public function rateFor(
        Branch $branch,
        ?FuelProduct $fuel = null,
        ?\DateTimeInterface $at = null,
    ): string {
        if (! $branch->province_id) {
            throw ValidationException::withMessages([
                'province_id' => "Branch '{$branch->name}' has no province set, so its sales tax cannot be determined.",
            ]);
        }

        $at ??= now();

        $base = fn () => TaxRate::query()
            ->where('province_id', $branch->province_id)
            ->where('effective_from', '<=', $at)
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhere('effective_to', '>', $at))
            ->orderByRaw('fuel_product_id IS NULL')   // fuel-specific rate wins
            ->orderByDesc('effective_from');

        // A fuel-specific rate wins; otherwise the province default applies.
        // The base builder is cloned each time because Eloquent builders are
        // mutable — reusing one instance would AND the two lookups together and
        // find nothing.
        $rate = $fuel
            ? ($base()->where('fuel_product_id', $fuel->id)->first()
                ?? $base()->whereNull('fuel_product_id')->first())
            : $base()->whereNull('fuel_product_id')->first();

        if (! $rate) {
            throw ValidationException::withMessages([
                'tax_rate' => sprintf(
                    'No sales tax rate is configured for %s. Set one before selling fuel there.',
                    $branch->province?->tax_authority ?? 'this province',
                ),
            ]);
        }

        return $rate->rate;
    }

    /**
     * Tax on a net amount: ROUND(amount x rate / 100, 2).
     *
     * Returned as a Money-scale string, never a float.
     */
    public function taxOn(string $netAmount, string $ratePercent): string
    {
        return Money::round(
            Decimal::divide(Decimal::multiply($netAmount, $ratePercent, Money::SCALE + 4), '100', Money::SCALE)
        );
    }

    /**
     * Net of a tax-inclusive amount: ROUND(amount x 100 / (100 + rate), 2).
     *
     * Fuel prices are quoted to the public tax-inclusive, so a sale often
     * starts from the gross figure.
     */
    public function netOf(string $grossAmount, string $ratePercent): string
    {
        $divisor = Decimal::add('100', $ratePercent, 4);

        return Money::round(
            Decimal::divide(Decimal::multiply($grossAmount, '100', Money::SCALE + 4), $divisor, Money::SCALE)
        );
    }

    /**
     * Split a tax-inclusive total into its net and tax parts.
     *
     * The tax component is derived by subtraction so the two always add back
     * to exactly the gross figure — no lost or invented paisa.
     *
     * @return array{net: string, tax: string}
     */
    public function splitInclusive(string $grossAmount, string $ratePercent): array
    {
        $net = $this->netOf($grossAmount, $ratePercent);
        $tax = Money::subtract(Money::round($grossAmount), $net);

        return ['net' => $net, 'tax' => $tax];
    }

    /**
     * The tax authority a branch reports to, e.g. PRA.
     */
    public function authorityFor(Branch $branch): ?string
    {
        return $branch->province?->tax_authority;
    }
}
