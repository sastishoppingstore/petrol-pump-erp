<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Services\System\SettingService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoiceVerificationController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
    ) {
    }

    /**
     * Public verification endpoint accessible by scanning invoice QR code without authentication.
     */
    public function verify(Request $request, string $hash): View|Response
    {
        $invoice = Invoice::query()
            ->where('hash', $hash)
            ->with(['items.fuelProduct', 'customer', 'vehicle', 'branch', 'snapshot', 'template'])
            ->first();

        if (! $invoice) {
            return response()->view('invoices.verify', [
                'verified' => false,
                'hash' => $hash,
                'invoice' => null,
                'station' => $this->settingService->stationIdentity(),
            ], 404);
        }

        $snapshot = $invoice->snapshot;
        $snapshotVerified = $snapshot ? $snapshot->verifyIntegrity() : true;
        $station = $snapshot?->station_snapshot ?? $this->settingService->stationIdentity();
        $customer = $snapshot?->customer_snapshot ?? [
            'name' => $invoice->customer?->name ?? 'Walk-in Customer / کیش کسٹمر',
            'phone' => $invoice->customer?->phone,
            'ntn' => $invoice->customer?->ntn_number,
            'vehicle_number' => $invoice->vehicle?->registration_number,
        ];

        return response()->view('invoices.verify', [
            'verified' => true,
            'hash' => $hash,
            'invoice' => $invoice,
            'snapshot' => $snapshot,
            'snapshotVerified' => $snapshotVerified,
            'station' => $station,
            'customer' => $customer,
        ]);
    }
}
