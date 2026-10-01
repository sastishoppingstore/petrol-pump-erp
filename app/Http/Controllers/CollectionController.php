<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\Customer\CustomerLedgerService;
use App\Services\Notifications\SmsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Collection / Wasooli screen — DigiKhata-style follow-up list for
 * customers with outstanding udhaar: ageing buckets, one-tap call /
 * WhatsApp / SMS reminders and bulk SMS reminders.
 */
class CollectionController extends Controller
{
    public function __construct(
        protected CustomerLedgerService $ledgerService,
        protected SmsService $sms,
    ) {}

    public function index(Request $request): View
    {
        $branchId = session('active_branch_id');
        $data = $this->ledgerService->getCollectionList($branchId ? (int) $branchId : null);

        return view('customers.collection', [
            'rows' => $data['rows'],
            'totals' => $data['totals'],
            'overdueCount' => $data['overdue_count'],
        ]);
    }

    /**
     * Send an SMS reminder to one or more selected customers.
     * Each send is isolated: one failure never blocks the rest, and
     * the flash reports exactly how many went out / failed (the SMS
     * gateway may be disabled or unconfigured — that counts as a
     * failure and is logged by SmsService).
     */
    public function bulkSms(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'customer_ids' => ['required', 'array', 'min:1'],
            'customer_ids.*' => ['integer'],
        ]);

        $customers = Customer::query()
            ->whereIn('id', $validated['customer_ids'])
            ->where('status', Customer::STATUS_ACTIVE)
            ->get();

        $sent = 0;
        $failed = 0;

        foreach ($customers as $customer) {
            try {
                if (empty($customer->phone)) {
                    $failed++;
                    continue;
                }

                $ok = $this->sms->send($customer->phone, $this->ledgerService->smsReminderMessage($customer));
                $ok ? $sent++ : $failed++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        return back()->with('status', __('sales.collection.sms_result', [
            'sent' => $sent,
            'failed' => $failed,
        ]));
    }
}
