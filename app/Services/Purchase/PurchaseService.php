<?php

namespace App\Services\Purchase;

use App\Models\CashEntry;
use App\Models\FuelProduct;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockAdjustment;
use App\Models\Supplier;
use App\Models\SupplierLedger;
use App\Models\SupplierPayment;
use App\Models\Tank;
use App\Services\Stock\StockService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use App\Support\Quantity;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseService
{
    public function __construct(
        protected StockService $stockService
    ) {}

    /**
     * Create fuel purchase entry before decantation approval.
     */
    public function createPurchase(array $data, ?int $userId = null): Purchase
    {
        $ordered = Quantity::n($data['volume_ordered'] ?? '0');
        $received = Quantity::n($data['volume_received'] ?? $ordered);
        $rate = Money::n($data['purchase_rate'] ?? '0');

        if (Quantity::compare($ordered, '0') <= 0 && Quantity::compare($received, '0') <= 0) {
            throw ValidationException::withMessages([
                'volume_received' => 'Volume must be greater than zero.',
            ]);
        }

        if (Money::compare($rate, '0') <= 0) {
            throw ValidationException::withMessages([
                'purchase_rate' => 'Purchase rate per litre must be greater than zero.',
            ]);
        }

        // Subtotal = volume_received * purchase_rate
        $subtotal = Money::round(bcmul($received, $rate, 4));

        $ifem = Money::n($data['ifem'] ?? '0');
        $petroleumLevy = Money::n($data['petroleum_levy'] ?? '0');
        $freight = Money::n($data['freight_charges'] ?? '0');
        $other = Money::n($data['other_charges'] ?? '0');
        $tax = Money::n($data['tax_amount'] ?? '0');

        $totalAmount = Money::add(
            $subtotal,
            Money::add($ifem, Money::add($petroleumLevy, Money::add($freight, Money::add($other, $tax))))
        );

        // Shortage calculation (challan vs received dip)
        $shortageLitres = '0.000';
        $shortageAmount = '0.00';
        $shortageClaimed = false;
        $shortageStatus = 'NONE';

        if (Quantity::compare($ordered, $received) > 0) {
            $shortageLitres = Quantity::subtract($ordered, $received);
            $shortageAmount = Money::round(bcmul($shortageLitres, $rate, 4));
            $shortageClaimed = true;
            $shortageStatus = 'PENDING';
        }

        $purchaseNumber = $data['purchase_number'] ?? ('PUR-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));

        return DB::transaction(function () use (
            $data, $userId, $ordered, $received, $rate, $subtotal,
            $ifem, $petroleumLevy, $freight, $other, $tax, $totalAmount,
            $shortageLitres, $shortageAmount, $shortageClaimed, $shortageStatus, $purchaseNumber
        ) {
            $purchase = Purchase::create([
                'branch_id' => $data['branch_id'] ?? 1,
                'supplier_id' => $data['supplier_id'],
                'purchase_number' => $purchaseNumber,
                'invoice_number' => $data['invoice_number'] ?? null,
                'challan_number' => $data['challan_number'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? today()->toDateString(),
                'tank_id' => $data['tank_id'] ?? null,
                'fuel_product_id' => $data['fuel_product_id'] ?? null,
                'volume_ordered' => $ordered,
                'volume_received' => $received,
                'dip_before' => isset($data['dip_before']) ? Quantity::n($data['dip_before']) : null,
                'dip_after' => isset($data['dip_after']) ? Quantity::n($data['dip_after']) : null,
                'shortage_litres' => $shortageLitres,
                'shortage_amount' => $shortageAmount,
                'shortage_claimed' => $shortageClaimed,
                'shortage_claim_status' => $shortageStatus,
                'density' => isset($data['density']) ? (string) $data['density'] : null,
                'temperature' => isset($data['temperature']) ? (string) $data['temperature'] : null,
                'tanker_number' => $data['tanker_number'] ?? null,
                'driver_name' => $data['driver_name'] ?? null,
                'bill_photo_path' => $data['bill_photo_path'] ?? null,
                'purchase_rate' => $rate,
                'ifem' => $ifem,
                'petroleum_levy' => $petroleumLevy,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'freight_charges' => $freight,
                'other_charges' => $other,
                'total_amount' => $totalAmount,
                'paid_amount' => '0.00',
                'balance_amount' => $totalAmount,
                'payment_status' => Purchase::PAYMENT_UNPAID,
                'status' => Purchase::STATUS_RECEIVED,
                'created_by' => $userId ?? auth()->id(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Add purchase item line
            PurchaseItem::create([
                'purchase_id' => $purchase->id,
                'product_name' => $purchase->fuelProduct?->name ?? 'Fuel Tanker Decantation',
                'item_type' => 'FUEL',
                'fuel_product_id' => $purchase->fuel_product_id,
                'tank_id' => $purchase->tank_id,
                'quantity' => $received,
                'unit_price' => $rate,
                'total_amount' => $subtotal,
            ]);

            return $purchase;
        });
    }

    /**
     * Approve fuel purchase decantation:
     * 1. Validate destination tank capacity
     * 2. Stock movement 'PURCHASE' under row lock
     * 3. Update Weighted-Average Cost on Fuel Product
     * 4. Credit Supplier Ledger and update supplier balance
     * 5. Set status APPROVED
     */
    public function approve(Purchase $purchase, int $userId): Purchase
    {
        return DB::transaction(function () use ($purchase, $userId) {
            $lockedPurchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            if ($lockedPurchase->status === Purchase::STATUS_APPROVED) {
                return $lockedPurchase;
            }

            if ($lockedPurchase->status === Purchase::STATUS_VOID) {
                throw ValidationException::withMessages([
                    'status' => 'Cannot approve a voided purchase.',
                ]);
            }

            if (! $lockedPurchase->tank_id) {
                throw ValidationException::withMessages([
                    'tank_id' => 'Destination tank is required to approve fuel decantation.',
                ]);
            }

            $tank = Tank::query()->whereKey($lockedPurchase->tank_id)->lockForUpdate()->firstOrFail();
            $volumeReceived = Quantity::n($lockedPurchase->volume_received);

            // Tank capacity check
            $projectedStock = Quantity::add($tank->current_stock, $volumeReceived);
            if (Quantity::compare($projectedStock, Quantity::n($tank->capacity)) > 0) {
                throw ValidationException::withMessages([
                    'tank_id' => sprintf(
                        'Destination tank #%s capacity exceeded. Current: %s L, Decanting: %s L, Capacity: %s L (Max space available: %s L)',
                        $tank->tank_number,
                        Quantity::format($tank->current_stock),
                        Quantity::format($volumeReceived),
                        Quantity::format($tank->capacity),
                        Quantity::format(Quantity::subtract($tank->capacity, $tank->current_stock))
                    ),
                ]);
            }

            // Capture tank stock before movement for weighted-average cost computation
            $stockBefore = Quantity::n($tank->current_stock);

            // 1. Inbound stock movement
            $this->stockService->move(
                tank: $tank,
                type: StockService::TYPE_PURCHASE,
                quantity: $volumeReceived,
                referenceType: Purchase::class,
                referenceId: $lockedPurchase->id,
                reason: sprintf('Decantation Challan #%s, Tanker #%s', $lockedPurchase->challan_number ?? $lockedPurchase->purchase_number, $lockedPurchase->tanker_number ?? 'N/A'),
                unitCost: $lockedPurchase->purchase_rate,
                userId: $userId,
            );

            // 2. Weighted-Average Costing update on fuel product
            $fuelProduct = $lockedPurchase->fuelProduct ?? FuelProduct::find($tank->fuel_product_id);
            if ($fuelProduct) {
                $this->updateWeightedAverageCost($fuelProduct, $stockBefore, $volumeReceived, $lockedPurchase->purchase_rate);
            }

            // 3. Supplier Ledger Credit
            $supplier = Supplier::query()->whereKey($lockedPurchase->supplier_id)->lockForUpdate()->firstOrFail();
            $this->appendSupplierEntry(
                supplier: $supplier,
                debit: '0.00',
                credit: Money::n($lockedPurchase->total_amount),
                description: sprintf('Fuel Purchase #%s (Challan #%s, Tanker %s)', $lockedPurchase->purchase_number, $lockedPurchase->challan_number ?? 'N/A', $lockedPurchase->tanker_number ?? 'N/A'),
                referenceType: Purchase::class,
                referenceId: $lockedPurchase->id,
                date: $lockedPurchase->purchase_date->toDateString(),
                branchId: $lockedPurchase->branch_id,
            );

            // 4. Mark purchase approved
            $lockedPurchase->forceFill([
                'status' => Purchase::STATUS_APPROVED,
                'approved_by' => $userId,
                'approved_at' => now(),
            ])->save();

            // 5. Post to General Ledger (Fuel Inventory Dr, Accounts Payable Cr)
            try {
                app(\App\Services\Accounting\AccountingService::class)->postPurchase($lockedPurchase);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('GL postPurchase failed: ' . $e->getMessage());
            }

            return $lockedPurchase;
        });
    }

    /**
     * Compute and update weighted-average cost for a fuel product.
     * Formula: ((StockBefore * OldCost) + (VolumeReceived * PurchaseRate)) / (StockBefore + VolumeReceived)
     */
    public function updateWeightedAverageCost(
        FuelProduct $product,
        string $stockBefore,
        string $volumeReceived,
        string $purchaseRate,
    ): string {
        $oldCost = Money::n($product->average_cost ?? $purchaseRate);

        if (Quantity::compare($stockBefore, '0.000') <= 0) {
            $newCost = Money::round($purchaseRate);
        } else {
            // (stockBefore * oldCost) + (volumeReceived * purchaseRate)
            $existingValue = bcmul($stockBefore, $oldCost, 6);
            $receivedValue = bcmul($volumeReceived, $purchaseRate, 6);
            $totalValue = bcadd($existingValue, $receivedValue, 6);

            $totalStock = bcadd($stockBefore, $volumeReceived, 6);
            $newCost = Money::round(bcdiv($totalValue, $totalStock, 4));
        }

        $product->update([
            'average_cost' => $newCost,
        ]);

        return $newCost;
    }

    /**
     * Record a payment to supplier (Cash, Bank, Cheque).
     */
    public function recordSupplierPayment(Supplier $supplier, array $data, ?int $userId = null): SupplierPayment
    {
        $amount = Money::round(Money::n($data['amount'] ?? '0'));
        if (Money::compare($amount, '0.00') <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payment amount must be greater than zero.',
            ]);
        }

        $method = $data['payment_method'] ?? SupplierPayment::METHOD_CASH;
        $date = $data['payment_date'] ?? today()->toDateString();
        $branchId = $data['branch_id'] ?? $supplier->branch_id ?? 1;

        return DB::transaction(function () use ($supplier, $data, $amount, $method, $date, $branchId, $userId) {
            $paymentNumber = $data['payment_number'] ?? ('SPAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)));

            $payment = SupplierPayment::create([
                'branch_id' => $branchId,
                'supplier_id' => $supplier->id,
                'purchase_id' => $data['purchase_id'] ?? null,
                'payment_number' => $paymentNumber,
                'payment_date' => $date,
                'payment_method' => $method,
                'bank_account_id' => $data['bank_account_id'] ?? null,
                'cheque_number' => $data['cheque_number'] ?? null,
                'cheque_date' => $data['cheque_date'] ?? null,
                'cheque_status' => $data['cheque_status'] ?? ($method === SupplierPayment::METHOD_CHEQUE ? 'PENDING' : null),
                'amount' => $amount,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId ?? auth()->id(),
            ]);

            // Supplier ledger debit (reduces payable balance)
            $description = sprintf('Supplier Payment [%s] #%s', $method, $paymentNumber);
            if (! empty($data['notes'])) {
                $description .= ' - ' . $data['notes'];
            }

            $this->appendSupplierEntry(
                supplier: $supplier,
                debit: $amount,
                credit: '0.00',
                description: $description,
                referenceType: SupplierPayment::class,
                referenceId: $payment->id,
                date: $date,
                branchId: $branchId,
            );

            // If cash payment, record in cash_entries as CASH_OUT
            if ($method === SupplierPayment::METHOD_CASH) {
                CashEntry::create([
                    'branch_id' => $branchId,
                    'shift_id' => $data['shift_id'] ?? session('active_shift_id'),
                    'voucher_number' => 'CV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
                    'type' => CashEntry::TYPE_CASH_OUT,
                    'category' => 'SUPPLIER_PAYMENT',
                    'amount' => $amount,
                    'person_name' => $supplier->name,
                    'reference_no' => $paymentNumber,
                    'notes' => 'Supplier Payment - ' . $supplier->name,
                    'user_id' => $userId ?? auth()->id() ?? 1,
                    'status' => CashEntry::STATUS_APPROVED,
                    'approved_by' => $userId ?? auth()->id() ?? 1,
                    'entry_date' => Carbon::parse($date),
                ]);
            }

            return $payment;
        });
    }

    /**
     * Resolve a shortage claim on a purchase (APPROVED, SETTLED, REJECTED).
     */
    public function resolveShortageClaim(
        Purchase $purchase,
        string $status,
        ?string $notes = null,
        bool $debitSupplier = false,
        ?int $userId = null,
    ): Purchase {
        return DB::transaction(function () use ($purchase, $status, $notes, $debitSupplier, $userId) {
            $lockedPurchase = Purchase::query()->whereKey($purchase->id)->lockForUpdate()->firstOrFail();

            $lockedPurchase->update([
                'shortage_claim_status' => $status,
                'notes' => trim(($lockedPurchase->notes ?? '') . "\nShortage Claim [{$status}]: " . ($notes ?? '')),
            ]);

            // If shortage claim approved and debitSupplier is requested, debit the supplier ledger for shortage amount
            if ($status === 'APPROVED' && $debitSupplier && Money::compare($lockedPurchase->shortage_amount, '0.00') > 0) {
                $supplier = Supplier::query()->whereKey($lockedPurchase->supplier_id)->lockForUpdate()->firstOrFail();
                $this->appendSupplierEntry(
                    supplier: $supplier,
                    debit: Money::n($lockedPurchase->shortage_amount),
                    credit: '0.00',
                    description: sprintf('Shortage Claim Credit Note - Purchase #%s (%s L short)', $lockedPurchase->purchase_number, $lockedPurchase->shortage_litres),
                    referenceType: Purchase::class,
                    referenceId: $lockedPurchase->id,
                    date: today()->toDateString(),
                    branchId: $lockedPurchase->branch_id,
                );
            }

            return $lockedPurchase;
        });
    }

    /**
     * Append entry to supplier ledger with running balance.
     */
    public function appendSupplierEntry(
        Supplier $supplier,
        string $debit,
        string $credit,
        string $description,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?string $date = null,
        ?int $branchId = null,
    ): SupplierLedger {
        return DB::transaction(function () use ($supplier, $debit, $credit, $description, $referenceType, $referenceId, $date, $branchId) {
            $locked = Supplier::query()->whereKey($supplier->id)->lockForUpdate()->firstOrFail();

            $lastEntry = SupplierLedger::query()
                ->where('supplier_id', $locked->id)
                ->orderByDesc('id')
                ->first();

            $prevBalance = $lastEntry
                ? Money::n($lastEntry->running_balance)
                : Money::n($locked->opening_balance);

            // Supplier ledger: Credit increases payable, Debit decreases payable
            $newBalance = Money::subtract(Money::add($prevBalance, $credit), $debit);

            $entry = SupplierLedger::create([
                'branch_id' => $branchId ?? $locked->branch_id ?? 1,
                'supplier_id' => $locked->id,
                'date' => $date ?? today()->toDateString(),
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'description' => $description,
                'debit' => $debit,
                'credit' => $credit,
                'running_balance' => $newBalance,
            ]);

            $locked->forceFill(['current_balance' => $newBalance])->save();

            return $entry;
        });
    }

    /**
     * Generate printable statement data for supplier.
     */
    public function generateSupplierStatement(Supplier $supplier, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ?? today()->startOfMonth()->toDateString();
        $endDate = $endDate ?? today()->toDateString();

        $priorCredits = SupplierLedger::query()
            ->where('supplier_id', $supplier->id)
            ->where('date', '<', $startDate)
            ->sum('credit');

        $priorDebits = SupplierLedger::query()
            ->where('supplier_id', $supplier->id)
            ->where('date', '<', $startDate)
            ->sum('debit');

        $openingBalance = Money::add(
            Money::n($supplier->opening_balance),
            Money::subtract(Money::n($priorCredits), Money::n($priorDebits))
        );

        $transactions = SupplierLedger::query()
            ->where('supplier_id', $supplier->id)
            ->whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $running = $openingBalance;
        $items = [];
        $totalDebits = '0.00';
        $totalCredits = '0.00';

        foreach ($transactions as $txn) {
            $running = Money::subtract(Money::add($running, Money::n($txn->credit)), Money::n($txn->debit));
            $totalDebits = Money::add($totalDebits, Money::n($txn->debit));
            $totalCredits = Money::add($totalCredits, Money::n($txn->credit));

            $items[] = [
                'id' => $txn->id,
                'date' => $txn->date->format('d/m/Y'),
                'description' => $txn->description,
                'reference_type' => $txn->reference_type,
                'reference_id' => $txn->reference_id,
                'debit' => $txn->debit,
                'credit' => $txn->credit,
                'balance' => $running,
            ];
        }

        $closingBalance = $running;

        return [
            'supplier' => $supplier,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'total_debits' => $totalDebits,
            'total_credits' => $totalCredits,
            'in_words_urdu' => PakistaniCurrency::toUrduWords($closingBalance),
            'formatted_closing' => PakistaniCurrency::format($closingBalance),
            'items' => $items,
        ];
    }
}
