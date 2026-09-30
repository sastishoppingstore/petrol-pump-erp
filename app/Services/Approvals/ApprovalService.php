<?php

namespace App\Services\Approvals;

use App\Models\ApprovalRequest;
use App\Models\MeterReading;
use App\Models\Nozzle;
use App\Models\StockAdjustment;
use App\Models\User;
use App\Services\Audit\AuditLogService;
use App\Services\Stock\StockAdjustmentService;
use App\Support\Decimal;
use App\Support\Money;
use App\Support\Quantity;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalService
{
    public function __construct(
        private readonly AuditLogService $audit,
    ) {
    }

    /**
     * Submit an approval request to the queue.
     */
    public function request(
        User $requester,
        int $branchId,
        string $type,
        string $title,
        string $description,
        ?string $amount = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        array $payload = [],
    ): ApprovalRequest {
        $amount = $amount ? Money::round(Money::n($amount)) : null;

        $request = new ApprovalRequest([
            'branch_id' => $branchId,
            'request_type' => $type,
            'title' => $title,
            'description' => $description,
            'amount' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'payload' => $payload,
            'status' => ApprovalRequest::STATUS_PENDING,
            'requested_by' => $requester->id,
        ]);

        $request->whatsapp_url = $request->generateWhatsAppUrl();
        $request->save();

        $this->audit->record(
            userId: $requester->id,
            action: 'approval_requested',
            module: 'approvals',
            referenceType: ApprovalRequest::class,
            referenceId: $request->id,
            newData: [
                'type' => $type,
                'title' => $title,
                'amount' => $amount,
            ],
        );

        return $request;
    }

    /**
     * Big Green Approve: requires mandatory reason and executes the underlying change.
     */
    public function approve(User $approver, ApprovalRequest $request, string $reason): bool
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A mandatory reason is required to approve this request.']);
        }

        if (! $request->isPending()) {
            throw ValidationException::withMessages(['status' => "Request #{$request->id} has already been {$request->status}."]);
        }

        return DB::transaction(function () use ($approver, $request, $reason) {
            $locked = ApprovalRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['status' => "Request #{$locked->id} has already been {$locked->status}."]);
            }

            // 1. Execute underlying action based on type
            $this->executeApproval($approver, $locked, $reason);

            // 2. Mark request as approved
            $locked->update([
                'status' => ApprovalRequest::STATUS_APPROVED,
                'actioned_by' => $approver->id,
                'actioned_at' => now(),
                'action_reason' => $reason,
            ]);

            $this->audit->record(
                userId: $approver->id,
                action: 'approval_granted',
                module: 'approvals',
                referenceType: ApprovalRequest::class,
                referenceId: $locked->id,
                newData: [
                    'status' => ApprovalRequest::STATUS_APPROVED,
                    'reason' => $reason,
                ],
            );

            return true;
        });
    }

    /**
     * Big Red Reject: requires mandatory reason.
     */
    public function reject(User $approver, ApprovalRequest $request, string $reason): bool
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw ValidationException::withMessages(['reason' => 'A mandatory reason is required to reject this request.']);
        }

        if (! $request->isPending()) {
            throw ValidationException::withMessages(['status' => "Request #{$request->id} has already been {$request->status}."]);
        }

        return DB::transaction(function () use ($approver, $request, $reason) {
            $locked = ApprovalRequest::query()->whereKey($request->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isPending()) {
                throw ValidationException::withMessages(['status' => "Request #{$locked->id} has already been {$locked->status}."]);
            }

            // If Stock Adjustment, reject the stock adjustment record
            if ($locked->request_type === ApprovalRequest::TYPE_STOCK_ADJUSTMENT && $locked->reference_id) {
                $adj = StockAdjustment::find($locked->reference_id);
                if ($adj && $adj->isPending()) {
                    $adj->update([
                        'status' => StockAdjustment::STATUS_REJECTED,
                        'approved_by' => $approver->id,
                        'approved_at' => now(),
                        'rejection_reason' => $reason,
                    ]);
                }
            }

            $locked->update([
                'status' => ApprovalRequest::STATUS_REJECTED,
                'actioned_by' => $approver->id,
                'actioned_at' => now(),
                'action_reason' => $reason,
            ]);

            $this->audit->record(
                userId: $approver->id,
                action: 'approval_rejected',
                module: 'approvals',
                referenceType: ApprovalRequest::class,
                referenceId: $locked->id,
                newData: [
                    'status' => ApprovalRequest::STATUS_REJECTED,
                    'reason' => $reason,
                ],
            );

            return true;
        });
    }

    /**
     * Execute underlying action when approved.
     */
    protected function executeApproval(User $approver, ApprovalRequest $request, string $reason): void
    {
        switch ($request->request_type) {
            case ApprovalRequest::TYPE_STOCK_ADJUSTMENT:
                if ($request->reference_id) {
                    $adjustment = StockAdjustment::find($request->reference_id);
                    if ($adjustment && $adjustment->isPending()) {
                        app(StockAdjustmentService::class)->approve($adjustment, $approver->id);
                    }
                }
                break;

            case ApprovalRequest::TYPE_METER_CORRECTION:
                $payload = $request->payload ?? [];
                if (! empty($payload['nozzle_id']) && isset($payload['new_meter'])) {
                    $nozzle = Nozzle::findOrFail($payload['nozzle_id']);
                    $oldMeter = Quantity::n($nozzle->current_meter);
                    $newMeter = Quantity::round(Quantity::n($payload['new_meter']));

                    // Write audited meter correction reading
                    MeterReading::create([
                        'branch_id' => $nozzle->branch_id,
                        'nozzle_id' => $nozzle->id,
                        'shift_id' => $payload['shift_id'] ?? null,
                        'reading_type' => 'CORRECTION',
                        'opening_reading' => $oldMeter,
                        'closing_reading' => $newMeter,
                        'litres_sold' => Quantity::subtract($newMeter, $oldMeter),
                        'recorded_by' => $approver->id,
                        'recorded_at' => now(),
                        'notes' => "Approved meter correction: {$reason}",
                    ]);

                    $nozzle->update(['current_meter' => $newMeter]);
                }
                break;

            case ApprovalRequest::TYPE_CASH_SHORTAGE:
                // Shift cash shortage waiver approved
                break;

            case ApprovalRequest::TYPE_CREDIT_OVERRIDE:
                // Credit limit override approved
                break;

            case ApprovalRequest::TYPE_LARGE_CASHOUT:
            case ApprovalRequest::TYPE_EXPENSE:
                if ($request->reference_id) {
                    $expense = \App\Models\Expense::find($request->reference_id);
                    if ($expense && $expense->status === \App\Models\Expense::STATUS_PENDING) {
                        $expense->update([
                            'status' => \App\Models\Expense::STATUS_PAID,
                            'approved_by' => $approver->id,
                        ]);
                    }
                }
                break;

            case ApprovalRequest::TYPE_ADVANCE:
                if ($request->reference_id) {
                    $advance = \App\Models\EmployeeAdvance::find($request->reference_id);
                    if ($advance) {
                        $advance->update(['approved_by' => $approver->id]);
                    }
                }
                break;
        }
    }

    /**
     * Get pending approval requests.
     */
    public function getPendingRequests(?int $branchId = null): Collection
    {
        return ApprovalRequest::query()
            ->with(['requester', 'branch'])
            ->where('status', ApprovalRequest::STATUS_PENDING)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderByDesc('created_at')
            ->get();
    }
}
