<?php

namespace App\Services\Fuel;

use App\Models\Branch;
use App\Models\Dispenser;
use App\Models\Nozzle;
use App\Models\Tank;
use App\Services\Audit\AuditLogService;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class NozzleService
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Create a nozzle.
     *
     * The hard rule from spec section 6: a nozzle's fuel must equal its tank's
     * fuel. A nozzle drawing Diesel out of a Petrol tank is a mis-dispensing
     * station, so it is rejected outright rather than warned about.
     */
    public function create(
        Branch $branch,
        Dispenser $dispenser,
        Tank $tank,
        int $fuelProductId,
        string $nozzleNumber,
        string $openingMeter = '0',
        string $meterMultiplier = '1',
        ?string $notes = null,
    ): Nozzle {
        $this->assertFuelMatchesTank($tank, $fuelProductId);
        $this->assertSameBranch($branch, $dispenser, $tank);
        $this->assertNumberFree($dispenser, $nozzleNumber);

        try {
            return DB::transaction(function () use ($branch, $dispenser, $tank, $fuelProductId, $nozzleNumber, $openingMeter, $meterMultiplier, $notes) {
                $nozzle = new Nozzle([
                    'branch_id' => $branch->id,
                    'dispenser_id' => $dispenser->id,
                    'tank_id' => $tank->id,
                    'fuel_product_id' => $fuelProductId,
                    'nozzle_number' => $nozzleNumber,
                    'opening_meter' => Quantity::round($openingMeter),
                    'meter_multiplier' => Money::n($meterMultiplier),
                    'status' => Nozzle::STATUS_ACTIVE,
                    'notes' => $notes,
                ]);

                $nozzle->save();

                // current_meter is deliberately not mass-assignable, so a new
                // nozzle is seeded explicitly. A nozzle starts exactly at its
                // opening meter and the two can never silently diverge.
                $nozzle->forceFill([
                    'current_meter' => Quantity::round($openingMeter),
                ])->save();

                return $nozzle;
            });
        } catch (Throwable $e) {
            Log::error('Nozzle creation failed', ['error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'nozzle_number' => 'Unable to create the nozzle. No changes were saved.',
            ]);
        }
    }

    public function update(Nozzle $nozzle, array $data): Nozzle
    {
        if (isset($data['tank_id'], $data['fuel_product_id'])) {
            $this->assertFuelMatchesTank(Tank::findOrFail($data['tank_id']), (int) $data['fuel_product_id']);
        } elseif (isset($data['tank_id'])) {
            $this->assertFuelMatchesTank(Tank::findOrFail($data['tank_id']), $nozzle->fuel_product_id);
        } elseif (isset($data['fuel_product_id'])) {
            $this->assertFuelMatchesTank($nozzle->tank, (int) $data['fuel_product_id']);
        }

        if (isset($data['nozzle_number']) && $data['nozzle_number'] !== $nozzle->nozzle_number) {
            $this->assertNumberFree($nozzle->dispenser, (string) $data['nozzle_number'], $nozzle->id);
        }

        $nozzle->fill($data)->save();

        return $nozzle;
    }

    /**
     * A nozzle drawing from a tank of a different fuel is rejected.
     */
    private function assertFuelMatchesTank(Tank $tank, int $fuelProductId): void
    {
        if ((int) $tank->fuel_product_id !== $fuelProductId) {
            throw ValidationException::withMessages([
                'fuel_product_id' => sprintf(
                    'Nozzle fuel must match the tank fuel. Tank "%s" holds %s, but %s was selected.',
                    $tank->tank_number,
                    $tank->fuelProduct?->name ?? 'a different fuel',
                    \App\Models\FuelProduct::find($fuelProductId)?->name ?? 'the selected fuel',
                ),
            ]);
        }
    }

    private function assertSameBranch(Branch $branch, Dispenser $dispenser, Tank $tank): void
    {
        if ((int) $dispenser->branch_id !== $branch->id) {
            throw ValidationException::withMessages([
                'dispenser_id' => 'The dispenser belongs to a different branch.',
            ]);
        }

        if ((int) $tank->branch_id !== $branch->id) {
            throw ValidationException::withMessages([
                'tank_id' => 'The tank belongs to a different branch.',
            ]);
        }
    }

    private function assertNumberFree(Dispenser $dispenser, string $number, ?int $ignoreId = null): void
    {
        $exists = Nozzle::query()
            ->where('dispenser_id', $dispenser->id)
            ->where('nozzle_number', $number)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'nozzle_number' => "Nozzle number '{$number}' already exists on this dispenser.",
            ]);
        }
    }

    /**
     * A nozzle that has dispensed fuel is never hard-deleted; it is deactivated.
     */
    public function retire(Nozzle $nozzle): void
    {
        $nozzle->update(['status' => Nozzle::STATUS_INACTIVE]);

        $this->audit->record(
            userId: auth()->id(),
            action: 'nozzle_retire',
            module: 'fuel',
            referenceType: Nozzle::class,
            referenceId: $nozzle->id,
            newData: ['status' => Nozzle::STATUS_INACTIVE],
        );
    }
}
