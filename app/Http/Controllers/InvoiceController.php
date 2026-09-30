<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\InvoiceTemplate;
use App\Services\Sale\InvoiceService;
use App\Services\Security\BranchScopeService;
use App\Services\System\SettingService;
use App\Support\PermissionList;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $invoiceService,
        private readonly SettingService $settingService,
        private readonly BranchScopeService $branchScope,
    ) {
    }

    /**
     * List invoices with search, filters, pagination, and summary metrics.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyPermission([PermissionList::SALES_VIEW, PermissionList::SALES_CREATE])) {
            abort(403, 'Unauthorized to view invoices.');
        }

        $query = Invoice::query()->with(['customer', 'vehicle', 'branch', 'user']);

        // Branch filtering
        if ($user && ! $user->isSuperAdmin()) {
            $accessible = $user->accessibleBranchIds();
            if (! empty($accessible)) {
                $query->whereIn('branch_id', $accessible);
            }
        } elseif ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        } elseif (session('active_branch_id')) {
            $query->where('branch_id', session('active_branch_id'));
        }

        // Search query
        if ($search = trim((string) $request->input('search'))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                    ->orWhere('hash', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('vehicle', function ($vq) use ($search) {
                        $vq->where('registration_number', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        // Filter by payment method
        if ($paymentMethod = $request->input('payment_method')) {
            $query->where('payment_method', $paymentMethod);
        }

        // Filter by date range
        if ($fromDate = $request->input('from_date')) {
            $query->whereDate('invoice_date', '>=', $fromDate);
        }
        if ($toDate = $request->input('to_date')) {
            $query->whereDate('invoice_date', '<=', $toDate);
        }

        // Summary aggregates
        $totalsQuery = clone $query;
        $totalInvoiced = $totalsQuery->sum('total_amount');
        $totalPaid = $totalsQuery->sum('paid_amount');
        $totalBalanceDue = $totalsQuery->sum('balance_due');
        $totalCount = $totalsQuery->count();

        $invoices = $query->orderByDesc('invoice_date')->orderByDesc('id')->paginate(20)->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'totalInvoiced' => $totalInvoiced,
            'totalPaid' => $totalPaid,
            'totalBalanceDue' => $totalBalanceDue,
            'totalCount' => $totalCount,
            'search' => $search,
            'status' => $status,
            'paymentMethod' => $paymentMethod,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
        ]);
    }

    /**
     * Show invoice details, snapshot verification, and action buttons.
     */
    public function show(Request $request, Invoice $invoice): View
    {
        $this->authorizeInvoice($request, $invoice);

        $invoice->loadMissing(['items.fuelProduct', 'customer', 'vehicle', 'user', 'branch', 'snapshot', 'template']);

        $station = $invoice->snapshot?->station_snapshot ?? $this->settingService->stationIdentity();
        $customer = $invoice->snapshot?->customer_snapshot ?? [];
        $theme = $invoice->snapshot?->theme_snapshot ?? ($invoice->template?->toArray() ?? []);
        $qrSvg = $this->invoiceService->generateQrCodeSvg($invoice->verification_url, 110);
        $snapshotVerified = $invoice->snapshot ? $invoice->snapshot->verifyIntegrity() : false;

        return view('invoices.show', [
            'invoice' => $invoice,
            'station' => $station,
            'customer' => $customer,
            'theme' => $theme,
            'qrSvg' => $qrSvg,
            'snapshotVerified' => $snapshotVerified,
        ]);
    }

    /**
     * Display standalone A4 Modern Red Band printable invoice.
     */
    public function a4(Request $request, Invoice $invoice): View
    {
        $this->authorizeInvoice($request, $invoice);

        $invoice->loadMissing(['items.fuelProduct', 'customer', 'vehicle', 'user', 'branch', 'snapshot', 'template']);

        $station = $invoice->snapshot?->station_snapshot ?? $this->settingService->stationIdentity();
        $customer = $invoice->snapshot?->customer_snapshot ?? [];
        $theme = $invoice->snapshot?->theme_snapshot ?? ($invoice->template?->toArray() ?? []);
        $qrSvg = $this->invoiceService->generateQrCodeSvg($invoice->verification_url, 110);

        return view('invoices.a4', [
            'invoice' => $invoice,
            'station' => $station,
            'customer' => $customer,
            'theme' => $theme,
            'qrSvg' => $qrSvg,
        ]);
    }

    /**
     * Display 80mm or 58mm Thermal receipt.
     */
    public function thermal(Request $request, Invoice $invoice): View
    {
        $this->authorizeInvoice($request, $invoice);

        $invoice->loadMissing(['items.fuelProduct', 'customer', 'vehicle', 'user', 'branch', 'snapshot', 'template']);

        $paperWidth = $request->query('size', '80mm');
        if (! in_array($paperWidth, ['80mm', '58mm'], true)) {
            $paperWidth = '80mm';
        }

        $station = $invoice->snapshot?->station_snapshot ?? $this->settingService->stationIdentity();
        $customer = $invoice->snapshot?->customer_snapshot ?? [];
        $qrSvg = $this->invoiceService->generateQrCodeSvg($invoice->verification_url, 95);

        return view('invoices.thermal', [
            'invoice' => $invoice,
            'paperWidth' => $paperWidth,
            'station' => $station,
            'customer' => $customer,
            'qrSvg' => $qrSvg,
        ]);
    }

    /**
     * Stream or download generated A4 DomPDF document.
     */
    public function pdf(Request $request, Invoice $invoice): SymfonyResponse
    {
        $this->authorizeInvoice($request, $invoice);

        $pdf = $this->invoiceService->generatePdf($invoice);

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }

    /**
     * Ensure current user can access the invoice.
     */
    private function authorizeInvoice(Request $request, Invoice $invoice): void
    {
        $user = $request->user();
        if ($user && ! $user->hasAnyPermission([PermissionList::SALES_VIEW, PermissionList::SALES_CREATE])) {
            abort(403, 'Unauthorized to access this invoice.');
        }

        if ($user && $invoice->branch_id && ! $user->canAccessBranch((int) $invoice->branch_id)) {
            abort(403, 'You do not have access to this branch invoice.');
        }
    }
}
