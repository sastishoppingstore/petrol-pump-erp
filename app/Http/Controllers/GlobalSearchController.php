<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerVehicle;
use App\Models\Invoice;
use App\Support\PermissionList;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Command palette (Ctrl+K) ke liye entity search.
 *
 * Sirf wohi sections wapas aate hain jin ki permission user ke paas hai,
 * aur tamam results active branch scope me rehte hain — palette kabhi
 * doosri branch ka khata ya bill nahi dikhati.
 */
class GlobalSearchController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $empty = ['customers' => [], 'invoices' => [], 'vehicles' => []];

        $user = $request->user();
        $q = trim((string) $request->query('q', ''));

        if (! $user || mb_strlen($q) < 2) {
            return response()->json($empty);
        }

        $branchId = session('active_branch_id');
        $branchId = $branchId ? (int) $branchId : null;
        $like = '%' . $q . '%';
        $result = $empty;

        if ($user->hasPermission(PermissionList::CUSTOMER_VIEW)) {
            $result['customers'] = Customer::query()
                ->where('status', Customer::STATUS_ACTIVE)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where(function ($sub) use ($branchId) {
                        $sub->where('branch_id', $branchId)->orWhereNull('branch_id');
                    });
                })
                ->where(function ($sub) use ($like) {
                    $sub->where('name', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('code', 'like', $like);
                })
                ->orderBy('name')
                ->limit(6)
                ->get()
                ->map(fn (Customer $c) => [
                    'label' => $c->name,
                    'subtitle' => collect([$c->code, $c->phone])->filter()->implode(' · '),
                    'url' => route('customers.show', $c),
                    'icon' => '👥',
                ])
                ->values()
                ->all();

            $result['vehicles'] = CustomerVehicle::query()
                ->with('customer')
                ->where('registration_number', 'like', $like)
                ->when($branchId, function ($query) use ($branchId) {
                    $query->whereHas('customer', function ($sub) use ($branchId) {
                        $sub->where('branch_id', $branchId)->orWhereNull('branch_id');
                    });
                })
                ->limit(6)
                ->get()
                ->filter(fn (CustomerVehicle $v) => $v->customer !== null)
                ->map(fn (CustomerVehicle $v) => [
                    'label' => $v->registration_number,
                    'subtitle' => collect([
                        $v->customer->name,
                        collect([$v->make, $v->model])->filter()->implode(' '),
                    ])->filter()->implode(' · '),
                    'url' => route('customers.show', $v->customer),
                    'icon' => '🚗',
                ])
                ->values()
                ->all();
        }

        if ($user->hasPermission(PermissionList::SALES_VIEW)) {
            $result['invoices'] = Invoice::query()
                ->when($branchId, function ($query) use ($branchId) {
                    $query->where('branch_id', $branchId);
                })
                ->where('invoice_number', 'like', $like)
                ->latest('id')
                ->limit(6)
                ->get()
                ->map(fn (Invoice $inv) => [
                    'label' => $inv->invoice_number,
                    'subtitle' => collect([
                        $inv->invoice_date ? \Illuminate\Support\Carbon::parse($inv->invoice_date)->format('d M Y') : null,
                        'Rs. ' . number_format((float) $inv->total_amount, 2),
                        $inv->status,
                    ])->filter()->implode(' · '),
                    'url' => route('invoices.show', $inv),
                    'icon' => '🧾',
                ])
                ->values()
                ->all();
        }

        return response()->json($result);
    }
}
