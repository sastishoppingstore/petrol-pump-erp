<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\FbrInvoice;
use App\Models\FuelProduct;
use App\Models\Nozzle;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\Compliance\FbrInvoiceService;
use App\Services\Sale\SaleService;
use App\Services\Shift\ShiftService;
use App\Services\System\SettingService;
use App\Support\Money;
use App\Support\PakistaniCurrency;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class PosController extends Controller
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly ShiftService $shifts,
        private readonly FbrInvoiceService $fbrInvoices,
        private readonly SettingService $settings,
    ) {
    }

    /**
     * The point-of-sale touch wizard screen.
     */
    public function index(Request $request): View
    {
        $branchId = (int) (session('active_branch_id') ?: $request->user()->defaultBranch()?->id);
        $shift = $this->shifts->activeShiftFor($request->user(), $branchId ?: null);

        $nozzles = $shift
            ? Nozzle::query()
                ->where('status', Nozzle::STATUS_ACTIVE)
                ->whereIn('id', $shift->nozzles->pluck('nozzle_id'))
                ->with(['dispenser', 'tank', 'fuelProduct'])
                ->get()
            : collect();

        // Fallback: show active nozzles for the branch
        if ($nozzles->isEmpty()) {
            $nozzles = Nozzle::query()
                ->where('branch_id', $branchId)
                ->where('status', Nozzle::STATUS_ACTIVE)
                ->with(['dispenser', 'tank', 'fuelProduct'])
                ->get();
        }

        $customers = Customer::query()
            ->where('status', Customer::STATUS_ACTIVE)
            ->with('vehicles')
            ->orderBy('name')
            ->limit(300)
            ->get();

        return view('pos.index', [
            'shift' => $shift,
            'branchId' => $branchId,
            'nozzles' => $nozzles,
            'customers' => $customers,
            'methods' => SalePayment::methods(),
        ]);
    }

    /**
     * Create the sale with double-submit protection, inventory check, and ledger update.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'request_token' => ['required', 'string', 'max:64'],
            'branch_id' => ['required', 'integer'],
            'shift_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
            'customer_name' => ['nullable', 'string', 'max:150'],
            'customer_phone' => ['nullable', 'string', 'max:30'],
            'vehicle_id' => ['nullable', 'integer'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:1000'],
            // [nozzle_id => "LITRES:25.125" | "AMOUNT:5000.00"]
            'quantities' => ['required', 'array', 'min:1'],
            'quantities.*' => ['required', 'string', 'max:64'],
            // [{method, amount, reference?}]
            'payments' => ['required', 'array', 'min:1'],
            'payments.*.method' => ['required', 'string'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.reference' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $request->user();

        if (! $user->canAccessBranch((int) $data['branch_id'])) {
            abort(403, 'You do not have access to that branch.');
        }

        // A credit sale without a customer would create uncollectable udhaar.
        $hasCredit = collect($data['payments'])->contains(fn ($p) => ($p['method'] ?? '') === SalePayment::METHOD_CREDIT);

        if ($hasCredit && empty($data['customer_id'])) {
            return back()->withErrors([
                'customer_id' => 'Select a customer for a credit (udhaar) sale.',
            ])->withInput();
        }

        try {
            $sale = $this->sales->create(
                actor: $user,
                branchId: (int) $data['branch_id'],
                requestToken: (string) $data['request_token'],
                quantities: $data['quantities'],
                payments: array_map(fn ($p) => [
                    'method' => $p['method'],
                    'amount' => (string) $p['amount'],
                    'reference' => $p['reference'] ?? null,
                ], $data['payments']),
                customerId: $data['customer_id'] ?? null,
                vehicleId: $data['vehicle_id'] ?? null,
                notes: (string) ($data['notes'] ?? ''),
                discount: (string) ($data['discount'] ?? '0'),
                shiftId: $data['shift_id'] ?? null,
                customerName: $data['customer_name'] ?? null,
                customerPhone: $data['customer_phone'] ?? null,
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->route('pos.success', $sale)
            ->with('success', "Sale {$sale->invoice_number} completed.");
    }

    /**
     * Animated green tick success screen with buttons for Thermal 80mm, A4, WhatsApp, New Sale, Home.
     */
    public function success(Request $request, Sale $sale): View
    {
        if (! $request->user()->canAccessBranch((int) $sale->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        $sale->load(['items.fuelProduct', 'payments', 'customer', 'vehicle', 'employee', 'shift', 'branch']);

        // Build WhatsApp text and link
        $phone = $sale->customer_phone ?: $sale->customer?->phone;
        $cleanPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '92' . substr($cleanPhone, 1);
        }

        $fuelSummary = $sale->items->map(fn($item) => "{$item->fuelProduct?->name}: {$item->litres} L @ Rs. {$item->rate} = Rs. " . number_format((float) $item->amount, 2))->implode("\n");
        $whatsappMessage = "⛽ *Mehar Filling Station (Vital Petroleum)*\n"
            . "Sheikhupura–Sharaqpur Road, Sheikhupura\n"
            . "──────────────────\n"
            . "🧾 *Invoice:* {$sale->invoice_number}\n"
            . "📅 *Date:* " . $sale->sale_date?->format('d M Y h:i A') . "\n"
            . "👤 *Customer:* " . ($sale->customer?->name ?: ($sale->customer_name ?: 'Walk-in Customer')) . "\n"
            . ($sale->vehicle ? "🚗 *Vehicle:* {$sale->vehicle->registration_number}\n" : '')
            . "──────────────────\n"
            . "{$fuelSummary}\n"
            . "──────────────────\n"
            . "💰 *Total Payable:* " . PakistaniCurrency::format($sale->total) . "\n"
            . "📝 (" . PakistaniCurrency::toWordsUrdu($sale->total) . ")\n"
            . "💳 *Paid via:* " . $sale->payments->pluck('method')->implode(', ') . "\n\n"
            . "Thank you for your patronage! Drive safely.\n"
            . "ہماری سروس استعمال کرنے کا شکریہ۔";

        $whatsappUrl = $cleanPhone
            ? "https://wa.me/{$cleanPhone}?text=" . rawurlencode($whatsappMessage)
            : "https://wa.me/?text=" . rawurlencode($whatsappMessage);

        return view('pos.success', [
            'sale' => $sale,
            'whatsappUrl' => $whatsappUrl,
            'phone' => $phone,
        ]);
    }

    /**
     * Standard A4 Tax Invoice / Receipt.
     */
    public function receipt(Request $request, Sale $sale): View
    {
        if (! $request->user()->canAccessBranch((int) $sale->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        $fbrInvoice = $this->fbrInvoices->forSale($sale);

        return view('pos.receipt', [
            'sale' => $sale->load(['items.fuelProduct', 'payments', 'customer', 'vehicle', 'employee', 'shift']),
            'fbrInvoice' => $fbrInvoice,
            'fbrQrSvg' => $this->fbrQrSvg($fbrInvoice, 96),
        ]);
    }

    /**
     * Thermal Receipt (58mm/80mm) optimized for POS slip printers.
     * Default paper width comes from the admin printing setting
     * (printing.thermal_paper_width, default 80); ?size= overrides it.
     */
    public function thermal(Request $request, Sale $sale): View
    {
        if (! $request->user()->canAccessBranch((int) $sale->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        $sale->load(['items.fuelProduct', 'payments', 'customer', 'vehicle', 'employee', 'shift']);

        $paperWidth = $this->thermalPaperWidth($request);
        $fbrInvoice = $this->fbrInvoices->forSale($sale);

        return view('pos.thermal', [
            'sale' => $sale,
            'paperWidth' => $paperWidth,
            'fbrInvoice' => $fbrInvoice,
            'fbrQrSvg' => $this->fbrQrSvg($fbrInvoice, $paperWidth === '58mm' ? 70 : 90),
        ]);
    }

    /**
     * Resolve the thermal paper width: explicit ?size= wins, otherwise the
     * admin's printing.thermal_paper_width setting (58 or 80, default 80).
     */
    private function thermalPaperWidth(Request $request): string
    {
        $size = $request->query('size');

        if (in_array($size, ['58mm', '80mm'], true)) {
            return $size;
        }

        return ((int) $this->settings->get('thermal_paper_width', '80')) === 58 ? '58mm' : '80mm';
    }

    /**
     * Build the FBR QR SVG from the fiscal record's payload (same pattern as
     * InvoiceService::generateQrCodeSvg). Empty string when the sale has not
     * been fiscalised, so simple prints never show an FBR block.
     */
    private function fbrQrSvg(?FbrInvoice $fbrInvoice, int $size): string
    {
        if (! $fbrInvoice || empty($fbrInvoice->fiscal_number)) {
            return '';
        }

        $payload = $fbrInvoice->qr_payload;
        $payloadString = is_array($payload) ? (string) json_encode($payload) : (string) ($payload ?? '');

        if ($payloadString === '') {
            return '';
        }

        try {
            $svg = (string) QrCode::size($size)->margin(1)->generate($payloadString);

            return (string) preg_replace('/<\?xml.*?\?>/', '', $svg);
        } catch (\Throwable) {
            return '';
        }
    }
}
