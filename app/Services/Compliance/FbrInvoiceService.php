<?php

namespace App\Services\Compliance;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\FbrInvoice;
use App\Models\Sale;
use App\Services\Audit\AuditLogService;
use App\Services\System\SettingService;
use App\Support\Fbr;
use App\Support\Money;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * FBR digital invoicing (SRO 1006(I)/2021).
 *
 * Fiscalses a sale: allocates the fiscal invoice number, builds the QR
 * payload, and decides whether the buyer's CNIC/NTN must appear. One invoice
 * per sale, enforced by a unique index on sale_id semantics (one row per sale,
 * never re-issued).
 *
 * Transmission to FBR is NOT done here. Under Chapter XIV of the Sales Tax
 * Rules 2006 only an integrator holding a valid FBR licence may submit. The
 * row therefore starts as DRAFT and is handed to a licensed integrator; see
 * FbrIntegrationService.
 */
class FbrInvoiceService
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Fiscalise a completed sale.
     */
    public function fiscalise(Sale $sale, ?int $userId = null): FbrInvoice
    {
        if (! $sale->isCompleted()) {
            throw ValidationException::withMessages([
                'sale_id' => "Sale {$sale->invoice_number} is {$sale->status} and cannot be fiscalised.",
            ]);
        }

        if ($existing = $this->forSale($sale)) {
            // Fiscal numbers are never re-issued: return the existing document.
            return $existing;
        }

        $branch = Branch::findOrFail($sale->branch_id);
        $posCode = $this->posBranchCode($branch);
        $issuedAt = $sale->sale_date ?? now();

        $customer = $sale->customer;
        $taxLiable = (bool) ($customer?->is_tax_liable ?? false);
        $needsBuyer = Fbr::requiresBuyerDetails(Money::n($sale->total), $taxLiable);

        $company = $this->settings->company();

        // The unique index on fiscal_number is the real guarantee. If two
        // invoices are issued inside the same second the second one loses the
        // race, so it simply retries with the next sequence number rather than
        // failing the customer.
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                return $this->writeInvoice(
                    sale: $sale,
                    branch: $branch,
                    posCode: $posCode,
                    issuedAt: $issuedAt,
                    customer: $customer,
                    needsBuyer: $needsBuyer,
                    company: $company,
                    userId: $userId,
                    attempt: $attempt,
                );
            } catch (UniqueConstraintViolationException) {
                Log::warning('Fiscal number collision, retrying', [
                    'sale_id' => $sale->id,
                    'attempt' => $attempt,
                ]);
            } catch (ValidationException $e) {
                throw $e;
            } catch (Throwable $e) {
                Log::error('FBR fiscalisation failed', [
                    'sale_id' => $sale->id,
                    'error' => $e->getMessage(),
                ]);

                throw ValidationException::withMessages([
                    'sale_id' => 'Unable to fiscalise the sale. No changes were saved.',
                ]);
            }
        }

        Log::error('FBR fiscalisation gave up after retries', ['sale_id' => $sale->id]);

        throw ValidationException::withMessages([
            'sale_id' => 'Unable to allocate a fiscal invoice number. Please try again.',
        ]);
    }

    /**
     * @param  array<string, string|null>  $company
     */
    private function writeInvoice(
        Sale $sale,
        Branch $branch,
        string $posCode,
        \DateTimeInterface $issuedAt,
        ?Customer $customer,
        bool $needsBuyer,
        array $company,
        ?int $userId,
        int $attempt,
    ): FbrInvoice {
        return DB::transaction(function () use (
            $sale, $branch, $posCode, $issuedAt, $customer,
            $needsBuyer, $company, $userId, $attempt
        ) {
            // Serialise fiscalisation for this station so the sequence read and
            // the insert cannot interleave with another invoice for this branch.
            Branch::query()->whereKey($branch->id)->lockForUpdate()->first();

            $sequence = $this->nextSequence($posCode, $issuedAt) + ($attempt - 1);
            $fiscalNumber = Fbr::fiscalNumber($posCode, $issuedAt, $sequence);

            $invoice = FbrInvoice::create([
                    'branch_id' => $branch->id,
                    'sale_id' => $sale->id,
                    'pos_branch_code' => $posCode,
                    'fiscal_number' => $fiscalNumber,
                    'qr_payload' => Fbr::qrPayload(
                        fiscalNumber: $fiscalNumber,
                        totalAmount: $sale->total,
                        date: $issuedAt->format('d/m/Y h:i:s A'),
                        ntn: (string) ($company['ntn'] ?? ''),
                        strn: (string) ($company['strn'] ?? ''),
                    ),
                    'buyer_ntn' => $customer?->ntn_number,
                    'buyer_cnic' => $customer?->cnic,
                    'buyer_name' => $customer?->name,
                    'buyer_details_required' => $needsBuyer,
                    'total_amount' => $sale->total,
                    'tax_amount' => $sale->tax,
                    'discount_amount' => $sale->discount,
                    // Mandatory per-invoice charge, a separate printed line.
                    'pos_service_fee' => $this->settings->money('pos_service_fee'),
                    'status' => FbrInvoice::STATUS_DRAFT,
                    'attempts' => 0,
                    'created_by' => $userId,
                ]);

                $this->audit->record(
                    userId: $userId,
                    action: 'fbr_invoice_create',
                    module: 'compliance',
                    referenceType: FbrInvoice::class,
                    referenceId: $invoice->id,
                    newData: [
                        'fiscal_number' => $fiscalNumber,
                        'sale' => $sale->invoice_number,
                        'total' => $sale->total,
                        'buyer_details_required' => $needsBuyer,
                    ],
                );

                return $invoice;
            });
    }

    public function forSale(Sale $sale): ?FbrInvoice
    {
        return FbrInvoice::where('sale_id', $sale->id)->first();
    }

    /**
     * The six-character POS branch code for a station.
     *
     * Derived once from the branch code and stored on every invoice, because
     * the code is part of the fiscal number and must not drift.
     */
    private function posBranchCode(Branch $branch): string
    {
        $base = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $branch->code));

        return str_pad(substr($base ?: 'POS', 0, 6), 6, '0');
    }

    /**
     * Next per-second sequence for this POS code and timestamp.
     */
    private function nextSequence(string $posCode, \DateTimeInterface $at): int
    {
        $stamp = \Carbon\Carbon::instance($at)->format('dmYHis');

        $existing = FbrInvoice::query()
            ->where('pos_branch_code', $posCode)
            ->where('fiscal_number', 'like', "%-{$stamp}-%")
            ->count();

        return $existing + 1;
    }
}
