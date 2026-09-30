<?php

namespace App\Services\Sale;

use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Services\Audit\AuditLogService;
use App\Services\Fuel\MeterService;
use App\Services\Stock\StockService;
use App\Support\Money;
use App\Support\PermissionList;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Voiding a sale (spec section 4).
 *
 * A sale is NEVER deleted. Voiding marks it VOIDED and writes a full set of
 * reversing rows — stock, meter, cash — so every original figure keeps its
 * partner and the ledger still reconciles. Refunds behave the same way but
 * leave the sale visible as REFUNDED.
 */
class SaleVoidService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly MeterService $meters,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Void a sale and reverse everything it touched.
     */
    public function void(
        Sale $sale,
        string $reason,
        int $actorId,
        bool $asRefund = false,
    ): Sale {
        if ($reason === '' || trim($reason) === '') {
            throw ValidationException::withMessages([
                'reason' => 'A reason is required to void or refund a sale.',
            ]);
        }

        if (! $sale->isCompleted()) {
            throw ValidationException::withMessages([
                'status' => "Sale {$sale->invoice_number} is already {$sale->status}.",
            ]);
        }

        $actor = \App\Models\User::find($actorId);
        $permission = $asRefund ? PermissionList::SALES_REFUND : PermissionList::SALES_VOID;

        if (! $actor || ! $actor->hasPermission($permission)) {
            throw ValidationException::withMessages([
                'status' => 'You do not have permission to void or refund a sale.',
            ]);
        }

        try {
            return DB::transaction(function () use ($sale, $reason, $actorId, $asRefund) {
                $locked = Sale::query()->whereKey($sale->id)->lockForUpdate()->firstOrFail();

                if (! $locked->isCompleted()) {
                    throw ValidationException::withMessages([
                        'status' => "Sale {$locked->invoice_number} is already {$locked->status}.",
                    ]);
                }

                $status = $asRefund ? Sale::STATUS_REFUNDED : Sale::STATUS_VOIDED;

                /** @var SaleItem $item */
                foreach ($locked->items()->with('nozzle')->get() as $item) {
                    // 1. Give the fuel back to the tank, as a reversing movement.
                    if (! Quantity::isZero($item->litres)) {
                        $tank = \App\Models\Tank::query()->whereKey($item->tank_id)->firstOrFail();
                        $nozzle = Nozzle::query()->whereKey($item->nozzle_id)->lockForUpdate()->first();

                        $this->stock->move(
                            tank: $tank,
                            type: StockService::TYPE_CORRECTION,
                            // Signed positive: this puts the litres back.
                            quantity: $item->litres,
                            referenceType: SaleItem::class,
                            referenceId: $item->id,
                            reason: "Reversal of {$locked->invoice_number}: {$reason}",
                            userId: $actorId,
                        );

                        // 2. Roll the meter back, via an audited CORRECTION row
                        //    rather than a silent overwrite.
                        if ($nozzle) {
                            $this->meters->correct(
                                nozzle: $nozzle,
                                newMeter: $item->meter_start,
                                reason: "Sale {$locked->invoice_number} voided: {$reason}",
                                userId: $actorId,
                            );
                        }
                    }
                }

                $locked->update([
                    'status' => $status,
                    'void_reason' => $reason,
                    'voided_by' => $actorId,
                    'voided_at' => now(),
                ]);

                // 3. Cash leaves the till again: the reversing cash entry.
                $this->reverseCash($locked, $reason, $actorId);

                $this->audit->record(
                    userId: $actorId,
                    action: $asRefund ? 'sale_refund' : 'sale_void',
                    module: 'sales',
                    referenceType: Sale::class,
                    referenceId: $locked->id,
                    oldData: ['status' => Sale::STATUS_COMPLETED, 'total' => $locked->total],
                    newData: [
                        'status' => $status,
                        'reason' => $reason,
                        'litres_reversed' => $locked->total_litres,
                    ],
                );

                return $locked->fresh(['items', 'payments']);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            Log::error('Sale void failed', ['sale_id' => $sale->id, 'error' => $e->getMessage()]);

            throw ValidationException::withMessages([
                'reason' => 'Unable to void the sale. No changes were saved.',
            ]);
        }
    }

    /**
     * Post the reversing entry against the shift's cash session for every
     * non-credit tender the customer actually paid.
     */
    private function reverseCash(Sale $sale, string $reason, int $actorId): void
    {
        if (! $sale->shift_id || ! \Illuminate\Support\Facades\Schema::hasTable('shift_cash')) {
            return;
        }

        foreach ($sale->payments as $payment) {
            // Credit is settled through the customer ledger in Phase 6, not
            // through the cash drawer.
            if ($payment->method === \App\Models\SalePayment::METHOD_CREDIT) {
                continue;
            }

            DB::table('shift_cash')->insert([
                'shift_id' => $sale->shift_id,
                'entry_type' => 'SALE_VOID',
                'amount' => $payment->amount,
                'notes' => "Reversal of {$sale->invoice_number}: {$reason}",
                'user_id' => $actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
