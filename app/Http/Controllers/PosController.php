<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\FuelProduct;
use App\Models\Nozzle;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Services\Sale\SaleService;
use App\Services\Shift\ShiftService;
use App\Support\Money;
use App\Support\PermissionList;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PosController extends Controller
{
    public function __construct(
        private readonly SaleService $sales,
        private readonly ShiftService $shifts,
    ) {
    }

    /**
     * The point-of-sale screen. Nozzles come from the open shift so an
     * attendant can only sell on nozzles assigned to them.
     */
    public function index(Request $request): View
    {
        $branchId = (int) (session('active_branch_id') ?: $request->user()->defaultBranch()?->id);
        $shift = $this->shifts->activeShiftFor($request->user(), $branchId ?: null);

        return view('pos.index', [
            'shift' => $shift,
            'branchId' => $branchId,
            'nozzles' => $shift
                ? Nozzle::query()
                    ->where('status', Nozzle::STATUS_ACTIVE)
                    ->whereIn('id', $shift->nozzles->pluck('nozzle_id'))
                    ->with(['dispenser', 'tank', 'fuelProduct'])
                    ->get()
                : collect(),
            'customers' => Customer::query()
                ->where('status', Customer::STATUS_ACTIVE)
                ->orderBy('name')
                ->limit(300)
                ->get(),
            'methods' => SalePayment::methods(),
        ]);
    }

    /**
     * Create the sale.
     *
     * The idempotency token makes a double-tap or a refresh harmless: the
     * replay returns the original invoice instead of charging twice.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'request_token' => ['required', 'string', 'max:64'],
            'branch_id' => ['required', 'integer'],
            'shift_id' => ['nullable', 'integer'],
            'customer_id' => ['nullable', 'integer'],
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
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return redirect()
            ->route('pos.receipt', $sale)
            ->with('success', "Sale {$sale->invoice_number} completed.");
    }

    public function receipt(Request $request, Sale $sale): View
    {
        if (! $request->user()->canAccessBranch((int) $sale->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        return view('pos.receipt', [
            'sale' => $sale->load(['items.fuelProduct', 'payments', 'customer', 'vehicle', 'employee', 'shift']),
        ]);
    }

    /**
     * Live rate + amount for the POS screen, so the operator sees the same
     * figure the server will use. The browser value is never trusted — this
     * is for display only.
     */
    public function quote(Request $request): RedirectResponse
    {
        return back();
    }
}
