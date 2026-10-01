<?php

namespace App\Services\Sale;

use App\Models\FuelProduct;
use App\Models\Nozzle;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleRequest;
use App\Models\Shift;
use App\Models\ShiftNozzle;
use App\Models\Tank;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Compliance\FbrInvoiceService;
use App\Services\Fuel\FuelPriceService;
use App\Services\Fuel\MeterService;
use App\Services\Shift\ShiftService;
use App\Services\Stock\StockService;
use App\Services\System\NumberSequenceService;
use App\Services\System\SettingService;
use App\Support\Money;
use App\Support\PermissionList;
use App\Support\Quantity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * The sale transaction (spec section 4).
 *
 * Runs inside one database transaction. Any failure rolls the whole thing back
 * — nothing is saved, so stock, meters, ledger and cash can never end up
 * disagreeing with each other.
 */
class SaleService
{
    public function __construct(
        private readonly StockService $stock,
        private readonly MeterService $meters,
        private readonly ShiftService $shifts,
        private readonly FuelPriceService $prices,
        private readonly NumberSequenceService $sequences,
        private readonly AuditLogService $audit,
        private readonly InvoiceService $invoices,
        private readonly FbrInvoiceService $fbrInvoices,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * A fresh idempotency token for the POS form.
     */
    public function newRequestToken(): string
    {
        return (string) Str::uuid();
    }

    /**
     * @param  array<string, string>  $quantities  keyed by nozzle id. Either
     *         "LITRES:25.125" or "AMOUNT:500.00" (see spec section 3).
     * @param  array<int, array{amount: string, reference?: string|null}>  $payments
     */
    public function create(
        User $actor,
        int $branchId,
        string $requestToken,
        array $quantities,
        array $payments,
        ?int $customerId = null,
        ?int $vehicleId = null,
        string $notes = '',
        string $discount = '0.00',
        ?int $shiftId = null,
        ?string $customerName = null,
        ?string $customerPhone = null,
    ): Sale {
        // Step 1 — permission and branch access.
        if (! $actor->hasPermission(PermissionList::SALES_CREATE)) {
            throw new \Illuminate\Auth\Access\AuthorizationException(
                'You do not have permission to create a sale.'
            );
        }

        if (! $actor->canAccessBranch($branchId)) {
            throw ValidationException::withMessages([
                'branch_id' => 'You do not have access to that branch.',
            ]);
        }

        // Step 2 — idempotency. A double click or refresh replays the same
        // token and must NOT create a second sale.
        if ($replayed = $this->findCompletedByToken($requestToken)) {
            // Self-healing: if the original completion was interrupted
            // before its documents were generated, generate them now.
            $this->ensureSalesDocuments($replayed, $actor);

            return $replayed;
        }

        // A FAILED token may be replayed — the first attempt saved nothing, so
        // the user must be able to retry. A PROCESSING or COMPLETED one may not.
        if (SaleRequest::where('request_token', $requestToken)
            ->whereIn('status', [SaleRequest::STATUS_PROCESSING, SaleRequest::STATUS_COMPLETED])
            ->exists()) {
            throw ValidationException::withMessages([
                'general' => 'This sale is already being processed. Please wait.',
            ]);
        }

        // Retrying a failed token: clear the dead record first.
        SaleRequest::where('request_token', $requestToken)
            ->where('status', SaleRequest::STATUS_FAILED)
            ->delete();

        try {
            SaleRequest::create([
                'request_token' => $requestToken,
                'user_id' => $actor->id,
                'status' => SaleRequest::STATUS_PROCESSING,
                'ip_address' => request()->ip(),
            ]);
        } catch (Throwable $e) {
            // Unique index hit: a concurrent duplicate won the race.
            if ($replayed = $this->findCompletedByToken($requestToken)) {
                $this->ensureSalesDocuments($replayed, $actor);

                return $replayed;
            }

            throw ValidationException::withMessages([
                'general' => 'This sale is already being processed. Please wait.',
            ]);
        }

        try {
            $sale = DB::transaction(function () use (
                $actor, $branchId, $requestToken, $quantities, $payments,
                $customerId, $vehicleId, $notes, $discount, $shiftId,
                $customerName, $customerPhone
            ) {
                // Step 3 — an OPEN shift, and the nozzles belong to it.
                $shift = $this->resolveShift($actor, $branchId, $shiftId);

                if ($quantities === []) {
                    throw ValidationException::withMessages([
                        'quantities' => 'At least one nozzle sale is required.',
                    ]);
                }

                // Step 10 (validated early) — a credit sale may not push the
                // customer past their limit. This must be checked against the
                // balance BEFORE this sale exists, otherwise the sale's own
                // amount would be counted twice.
                $creditAmount = $this->creditTotal($payments);

                if (Money::compare($creditAmount, "0") > 0 && $customerId) {
                    $this->assertWithinCreditLimit($customerId, $creditAmount);
                }

                // Step 4 — lock the nozzle and tank rows before reading them.
                $nozzles = Nozzle::query()
                    ->whereIn('id', array_map('intval', array_keys($quantities)))
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                if ($nozzles->count() !== count($quantities)) {
                    throw ValidationException::withMessages([
                        'nozzles' => 'One or more selected nozzles do not exist.',
                    ]);
                }

                $tanks = Tank::query()
                    ->whereIn('id', $nozzles->pluck('tank_id')->unique())
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $this->assertNozzlesOnShift($shift, $nozzles);

                // Step 7 — invoice number from the row-locked sequence.
                $invoiceNumber = $this->sequences->next('invoice');

                $sale = new Sale([
                    'branch_id' => $branchId,
                    'shift_id' => $shift?->id,
                    'invoice_number' => $invoiceNumber,
                    'customer_id' => $customerId,
                    'customer_name' => $customerName,
                    'customer_phone' => $customerPhone,
                    'vehicle_id' => $vehicleId,
                    'employee_id' => $actor->id,
                    'sale_date' => now(),
                    'sale_mode' => Sale::MODE_LITRES,
                    'status' => Sale::STATUS_COMPLETED,
                    'notes' => $notes !== '' ? $notes : null,
                ]);

                $subtotal = '0.00';
                $totalLitres = '0.000';
                $totalCost = '0.00';
                $mode = Sale::MODE_LITRES;

                foreach ($nozzles as $nozzleId => $nozzle) {
                    $spec = $quantities[(string) $nozzleId] ?? $quantities[$nozzleId];

                    [$itemMode, $rawQuantity] = $this->parseQuantitySpec($spec);
                    $mode = $itemMode;

                    // Step 5 — compute litres/amount. The rate is resolved
                    // SERVER SIDE; a rate sent by the browser is ignored.
                    $fuel = FuelProduct::findOrFail($nozzle->fuel_product_id);
                    $rate = $this->prices->rateFor($fuel, $branchId);

                    [$litres, $amount] = $this->compute($itemMode, $rawQuantity, $rate);

                    if (Quantity::isNegative($litres) || Money::isNegative($amount)) {
                        throw ValidationException::withMessages([
                            "quantities.{$nozzleId}" => 'Quantity must be zero or greater.',
                        ]);
                    }

                    // Step 8 — the sale header, so items can reference it.
                    $sale->save();

                    $meterStart = \App\Support\Money::n($nozzle->current_meter);

                    // Step 9 — stock and meter, both under the lock taken above.
                    if (! Quantity::isZero($litres)) {
                        $tank = $tanks->get($nozzle->tank_id);

                        if (! $tank) {
                            throw ValidationException::withMessages([
                                "quantities.{$nozzleId}" => 'The nozzle has no valid tank.',
                            ]);
                        }

                        $this->stock->move(
                            tank: $tank,
                            type: StockService::TYPE_SALE,
                            quantity: $litres,
                            referenceType: SaleItem::class,
                            referenceId: null,
                            userId: $actor->id,
                        );
                    }

                    $meter = $this->meters->advance(
                        nozzle: $nozzle,
                        litres: $litres,
                        saleId: $sale->id,
                        shiftId: $shift?->id,
                    );

                    // Step 8 (cont.) — the item, with the historical cost rate.
                    $costRate = Money::n($fuel->average_cost);
                    $costAmount = Money::multiply($litres, $costRate);

                    SaleItem::create([
                        'sale_id' => $sale->id,
                        'branch_id' => $branchId,
                        'fuel_product_id' => $fuel->id,
                        'tank_id' => $nozzle->tank_id,
                        'dispenser_id' => $nozzle->dispenser_id,
                        'nozzle_id' => $nozzle->id,
                        'litres' => $litres,
                        'rate' => $rate,
                        'cost_rate' => $costRate,
                        'amount' => $amount,
                        'cost_amount' => $costAmount,
                        'meter_start' => $meterStart,
                        'meter_end' => $meter['current'],
                    ]);

                    $subtotal = Money::add($subtotal, $amount);
                    $totalLitres = Quantity::add($totalLitres, $litres);
                    $totalCost = Money::add($totalCost, $costAmount);
                }

                $discount = Money::round($discount);
                $total = Money::subtract($subtotal, $discount);

                // Step 5 (cont.) — payments must add up to the total.
                $this->assertPaymentsTotal($payments, $total);

                $sale->fill([
                    'sale_mode' => $mode,
                    'subtotal' => $subtotal,
                    'discount' => $discount,
                    'tax' => '0.00',
                    'total' => $total,
                    'total_litres' => $totalLitres,
                    'total_cost' => $totalCost,
                ])->save();

                // Step 8 (cont.) — the payment rows.
                foreach ($payments as $payment) {
                    SalePayment::create([
                        'sale_id' => $sale->id,
                        'method' => $payment['method'],
                        'amount' => Money::round($payment['amount']),
                        'reference' => $payment['reference'] ?? null,
                    ]);
                }

                // Step 10 — credit sales debit the customer ledger.
                $creditAmount = $this->creditTotal($payments);
                if (! Money::isZero($creditAmount) && $customerId) {
                    $cust = \App\Models\Customer::whereKey($customerId)->lockForUpdate()->first();
                    if ($cust) {
                        $newCustBal = Money::add(Money::n($cust->current_balance), $creditAmount);
                        $cust->update(['current_balance' => $newCustBal]);
                        if (\Illuminate\Support\Facades\Schema::hasTable('customer_ledger') && class_exists(\App\Models\CustomerLedger::class)) {
                            \App\Models\CustomerLedger::create([
                                'branch_id' => $branchId,
                                'customer_id' => $customerId,
                                'date' => now()->format('Y-m-d'),
                                'reference_type' => Sale::class,
                                'reference_id' => $sale->id,
                                'description' => "Credit Sale #{$sale->invoice_number}",
                                'debit' => $creditAmount,
                                'credit' => '0.00',
                                'running_balance' => $newCustBal,
                            ]);
                        }
                    }
                }

                try {
                    app(\App\Services\Accounting\AccountingService::class)->postSale($sale);
                } catch (\Throwable $e) {
                    Log::warning('GL postSale: ' . $e->getMessage());
                }

                // Track shift throughput for the active-shift screen.
                if ($shift) {
                    $this->addShiftThroughput($shift, $totalLitres);
                }

                // Step 13 — audit.
                $this->audit->record(
                    userId: $actor->id,
                    action: 'sale_create',
                    module: 'sales',
                    referenceType: Sale::class,
                    referenceId: $sale->id,
                    newData: [
                        'invoice_number' => $sale->invoice_number,
                        'total' => $sale->total,
                        'litres' => $sale->total_litres,
                        'nozzles' => array_keys($quantities),
                    ],
                );

                // Step 2 (cont.) — close out the idempotency record.
                SaleRequest::where('request_token', $requestToken)->update([
                    'status' => SaleRequest::STATUS_COMPLETED,
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                ]);

                return $sale->load(['items', 'payments']);
            });
        } catch (ValidationException $e) {
            SaleRequest::where('request_token', $requestToken)
                ->update(['status' => SaleRequest::STATUS_FAILED]);

            throw $e;
        } catch (Throwable $e) {
            SaleRequest::where('request_token', $requestToken)
                ->update(['status' => SaleRequest::STATUS_FAILED]);

            Log::error('Sale creation failed', [
                'user_id' => $actor->id,
                'error' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'general' => 'Unable to complete the transaction. No changes were saved.',
            ]);
        }

        // Step 15 — sales documents. The sale itself is committed and is a
        // financial fact; the printable Invoice (with its immutable
        // snapshot) and — when FBR invoicing is enabled — the PENDING FBR
        // fiscal record are derived from it here, at the single completion
        // choke point every POS/cashier flow passes through.
        $this->ensureSalesDocuments($sale, $actor);

        return $sale;
    }

    /**
     * Generate the documents a completed sale must have: its official
     * Invoice and, when tax.fbr_invoicing_enabled is on, its FBR fiscal
     * record (status PENDING — queued for the licensed integrator, never
     * transmitted by this system).
     *
     * Idempotent on both branches: InvoiceService::invoiceForSale() returns
     * the existing invoice and FbrInvoiceService::fiscalise() returns the
     * existing fiscal record, so replays never duplicate either document.
     *
     * Failures are logged loudly but never thrown: the sale has already
     * been paid for and committed, and a document problem must not tell
     * the cashier the transaction failed. The replay path above re-runs
     * this method, so a missed document is healed on the next submission
     * of the same token (and can be regenerated from the designer screen).
     */
    private function ensureSalesDocuments(Sale $sale, User $actor): void
    {
        try {
            $this->invoices->invoiceForSale($sale);
        } catch (Throwable $e) {
            Log::error('Invoice generation failed for completed sale', [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            // Number-type settings come back as decimal strings ("1.0000"),
            // so the toggle is compared numerically, never === '1'.
            $fbrEnabled = (float) ($this->settings->get('fbr_invoicing_enabled') ?? '0') > 0;

            if ($fbrEnabled) {
                $this->fbrInvoices->fiscalise($sale, $actor->id);
            }
        } catch (Throwable $e) {
            Log::error('FBR fiscalisation failed for completed sale', [
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Litres mode: amount = ROUND(litres x rate, 2).
     * Amount mode: litres = ROUND(amount / rate, 3), and the entered amount
     * stays the final amount (spec section 3).
     *
     * @return array{0: string, 1: string} [litres, amount]
     */
    public function compute(string $mode, string $input, string $rate): array
    {
        if ($mode === Sale::MODE_AMOUNT) {
            $amount = Money::round($input);

            if (Money::compare($amount, '0') < 0) {
                throw ValidationException::withMessages(['amount' => 'Amount cannot be negative.']);
            }

            $litres = Money::litresForAmount($amount, $rate);

            // The customer paid exactly what was typed.
            return [$litres, $amount];
        }

        $litres = Quantity::round($input);

        if (Quantity::compare($litres, '0') < 0) {
            throw ValidationException::withMessages(['litres' => 'Litres cannot be negative.']);
        }

        return [$litres, Money::amountForLitres($litres, $rate)];
    }

    /**
     * Parse "LITRES:25.125" / "AMOUNT:500.00".
     *
     * @return array{0: string, 1: string}
     */
    private function parseQuantitySpec(string $spec): array
    {
        if (! str_contains($spec, ':')) {
            // Default to litres mode for a bare number.
            return [Sale::MODE_LITRES, $spec];
        }

        [$mode, $value] = explode(':', $spec, 2);

        $mode = strtoupper(trim($mode));

        if (! in_array($mode, [Sale::MODE_LITRES, Sale::MODE_AMOUNT], true)) {
            throw ValidationException::withMessages([
                'quantities' => "Unknown sale mode '{$mode}'.",
            ]);
        }

        return [$mode, $value];
    }

    private function resolveShift(User $actor, int $branchId, ?int $shiftId): ?Shift
    {
        if ($shiftId) {
            $shift = Shift::query()
                ->whereKey($shiftId)
                ->where('branch_id', $branchId)
                ->where('employee_id', $actor->id)
                ->where('status', Shift::STATUS_OPEN)
                ->first();

            if (! $shift) {
                throw ValidationException::withMessages([
                    'shift_id' => 'The selected shift is not open or does not belong to you.',
                ]);
            }

            return $shift;
        }

        return $this->shifts->activeShiftFor($actor, $branchId);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Nozzle>  $nozzles
     */
    private function assertNozzlesOnShift(?Shift $shift, $nozzles): void
    {
        if (! $shift) {
            return;
        }

        $assigned = ShiftNozzle::query()
            ->where('shift_id', $shift->id)
            ->pluck('nozzle_id')
            ->all();

        $notAssigned = $nozzles->keys()
            ->reject(fn ($id) => in_array($id, $assigned, false))
            ->values()
            ->all();

        if ($notAssigned !== []) {
            throw ValidationException::withMessages([
                'nozzles' => 'These nozzles are not assigned to your shift: '
                    .Nozzle::whereIn('id', $notAssigned)->pluck('nozzle_number')->implode(', ').'.',
            ]);
        }
    }

    /**
     * Payment splits must add up to the sale total (spec section 4, step 5).
     */
    private function assertPaymentsTotal(array $payments, string $total): void
    {
        if ($payments === []) {
            throw ValidationException::withMessages([
                'payments' => 'At least one payment is required.',
            ]);
        }

        $paid = '0.00';

        foreach ($payments as $payment) {
            $paid = Money::add($paid, Money::n($payment['amount']));
        }

        if (Money::compare(Money::round($paid), $total) !== 0) {
            throw ValidationException::withMessages([
                'payments' => sprintf(
                    'Payments total %s but the sale total is %s. They must match exactly.',
                    Money::format(Money::round($paid)),
                    Money::format($total),
                ),
            ]);
        }
    }

    private function creditTotal(array $payments): string
    {
        $total = '0.00';

        foreach ($payments as $payment) {
            if (($payment['method'] ?? null) === SalePayment::METHOD_CREDIT) {
                $total = Money::add($total, Money::n($payment['amount']));
            }
        }

        return $total;
    }

    /**
     * A credit sale may not push the customer past their limit.
     */
    private function assertWithinCreditLimit(int $customerId, string $amount): void
    {
        if (! \Illuminate\Support\Facades\Schema::hasTable('customers')) {
            return;
        }

        $customer = \App\Models\Customer::find($customerId);

        if (! $customer) {
            return;
        }

        if ($customer->creditLimitIsUnlimited()) {
            return;
        }

        $outstanding = $customer->outstandingBalance();

        if (Money::compare(
            Money::add($outstanding, $amount),
            Money::n($customer->credit_limit)
        ) > 0) {
            throw ValidationException::withMessages([
                'customer_id' => sprintf(
                    'This sale would exceed %s\'s credit limit. Outstanding: %s, limit: %s.',
                    $customer->name,
                    Money::format($outstanding),
                    Money::format(Money::n($customer->credit_limit)),
                ),
            ]);
        }
    }

    private function addShiftThroughput(Shift $shift, string $litres): void
    {
        $assignmentTotal = ShiftNozzle::query()
            ->where('shift_id', $shift->id)
            ->sum('system_litres');

        ShiftNozzle::query()
            ->where('shift_id', $shift->id)
            ->update([
                'system_litres' => \App\Support\Decimal::add(
                    (string) $assignmentTotal,
                    $litres,
                    Quantity::SCALE
                ),
            ]);
    }

    private function findCompletedByToken(string $token): ?Sale
    {
        $request = SaleRequest::where('request_token', $token)
            ->where('status', SaleRequest::STATUS_COMPLETED)
            ->first();

        return $request?->sale;
    }
}
