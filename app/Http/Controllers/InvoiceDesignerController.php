<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\InvoiceSnapshot;
use App\Models\DigitalSignature;
use App\Services\Invoicing\InvoiceDesignerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceDesignerController extends Controller
{
    private InvoiceDesignerService $designerService;

    public function __construct(InvoiceDesignerService $designerService)
    {
        $this->designerService = $designerService;
        $this->middleware('auth');
        $this->middleware('verified');
    }

    /**
     * Initialize default templates
     */
    public function initializeTemplates(): JsonResponse
    {
        $this->authorize('create', InvoiceSnapshot::class);

        try {
            $this->designerService->initializeDefaultTemplates();

            return response()->json([
                'success' => true,
                'message' => 'Invoice templates initialized successfully',
            ]);
        } catch (\Exception $e) {
            \Log::error('Template initialization failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to initialize templates',
            ], 400);
        }
    }

    /**
     * Get available layouts and paper sizes
     */
    public function getDesignOptions(): JsonResponse
    {
        $this->authorize('view', InvoiceSnapshot::class);

        return response()->json([
            'success' => true,
            'layouts' => $this->designerService->getAvailableLayouts(),
            'paper_sizes' => $this->designerService->getAvailablePaperSizes(),
        ]);
    }

    /**
     * Generate invoice snapshot
     */
    public function generateSnapshot(Sale $sale, Request $request): JsonResponse
    {
        $this->authorize('create', InvoiceSnapshot::class);

        $validated = $request->validate([
            'layout' => 'required|in:MODERN_RED_BAND,CLASSIC,MINIMAL',
        ]);

        try {
            $snapshot = $this->designerService->generateSnapshot(
                $sale,
                $validated['layout']
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice snapshot generated successfully',
                'snapshot_id' => $snapshot->id,
                'layout' => $validated['layout'],
            ]);
        } catch (\Exception $e) {
            \Log::error('Snapshot generation failed', [
                'error' => $e->getMessage(),
                'sale_id' => $sale->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to generate invoice snapshot',
            ], 400);
        }
    }

    /**
     * View invoice snapshot
     */
    public function viewSnapshot(Sale $sale): View
    {
        $this->authorize('view', $sale);

        $snapshot = $this->designerService->getSnapshot($sale);

        if (!$snapshot) {
            // Auto-generate if not exists. Generation depends on invoice
            // templates being initialized; if it fails the page must still
            // render (with the sale's own data) instead of a 500.
            try {
                $snapshot = $this->designerService->generateSnapshot($sale);
            } catch (\Throwable $e) {
                \Log::error('Snapshot auto-generation failed', [
                    'error' => $e->getMessage(),
                    'sale_id' => $sale->id,
                ]);
                $snapshot = null;
            }
        }

        $designSnapshot = [];
        $invoiceData = [];

        if ($snapshot) {
            // design_snapshot / invoice_data are JSON payloads on the
            // snapshot record; decode defensively so a missing or legacy
            // payload renders as an empty array, never an error.
            $designSnapshot = json_decode($snapshot->design_snapshot ?? '', true) ?: [];
            $invoiceData = json_decode($snapshot->invoice_data ?? '', true) ?: [];

            if ($invoiceData === [] && !empty($snapshot->raw_snapshot)) {
                $invoiceData = is_array($snapshot->raw_snapshot) ? $snapshot->raw_snapshot : [];
            }
        }

        return view('invoicing.snapshot', compact('snapshot', 'designSnapshot', 'invoiceData', 'sale'));
    }

    /**
     * Add digital signature to invoice
     */
    public function addSignature(Sale $sale, Request $request): JsonResponse
    {
        $this->authorize('create', DigitalSignature::class);

        $validated = $request->validate([
            'signature_svg' => 'required|string',
            'signer_name' => 'required|string|max:100',
            'signer_phone' => 'nullable|string|max:20',
        ]);

        try {
            $signature = $this->designerService->addSignature(
                $sale,
                $validated['signature_svg'],
                $validated['signer_name'],
                $validated['signer_phone'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Digital signature added successfully',
                'signature_id' => $signature->id,
                'signed_at' => $signature->signed_at,
            ]);
        } catch (\Exception $e) {
            \Log::error('Signature addition failed', [
                'error' => $e->getMessage(),
                'sale_id' => $sale->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to add signature',
            ], 400);
        }
    }

    /**
     * Export invoice as PDF
     */
    public function exportPdf(Sale $sale, Request $request): JsonResponse
    {
        $this->authorize('view', $sale);

        $validated = $request->validate([
            'layout' => 'required|in:MODERN_RED_BAND,CLASSIC,MINIMAL',
        ]);

        try {
            $filename = $this->designerService->exportPdf($sale, $validated['layout']);

            return response()->json([
                'success' => true,
                'message' => 'PDF exported successfully',
                'filename' => $filename,
            ]);
        } catch (\Exception $e) {
            \Log::error('PDF export failed', [
                'error' => $e->getMessage(),
                'sale_id' => $sale->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to export PDF',
            ], 400);
        }
    }

    /**
     * Regenerate snapshot (for layout changes)
     */
    public function regenerateSnapshot(Sale $sale, Request $request): JsonResponse
    {
        $this->authorize('update', InvoiceSnapshot::class);

        $validated = $request->validate([
            'layout' => 'required|in:MODERN_RED_BAND,CLASSIC,MINIMAL',
        ]);

        try {
            $snapshot = $this->designerService->regenerateSnapshot(
                $sale,
                $validated['layout']
            );

            return response()->json([
                'success' => true,
                'message' => 'Invoice snapshot regenerated successfully',
                'snapshot_id' => $snapshot->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Snapshot regeneration failed', [
                'error' => $e->getMessage(),
                'sale_id' => $sale->id,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to regenerate snapshot',
            ], 400);
        }
    }

    /**
     * Get invoice signature
     */
    public function getSignature(Sale $sale): JsonResponse
    {
        $this->authorize('view', $sale);

        $signature = DigitalSignature::where('sale_id', $sale->id)->latest()->first();

        if (!$signature) {
            return response()->json([
                'success' => false,
                'message' => 'No signature found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'signature' => [
                'id' => $signature->id,
                'signer_name' => $signature->signer_name,
                'signed_at' => $signature->signed_at,
                'is_valid' => $signature->isValid(),
            ],
        ]);
    }
}
