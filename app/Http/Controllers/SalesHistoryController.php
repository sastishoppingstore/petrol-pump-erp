<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Services\Security\BranchScopeService;
use App\Support\PakistaniCurrency;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalesHistoryController extends Controller
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
    ) {
    }

    public function index(Request $request): View
    {
        $query = Sale::query()
            ->with(['customer', 'employee', 'shift'])
            ->withSum('items as litres', 'litres');

        $this->branchScope->apply($query, $request->user());

        $query
            ->when($request->filled('from'), fn ($q) => $q->whereDate('sale_date', '>=', $request->input('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('sale_date', '<=', $request->input('to')))
            ->when($request->filled('invoice'), fn ($q) => $q->where('invoice_number', 'like', '%'.$request->input('invoice').'%'))
            ->when($request->filled('customer'), fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('name', 'like', '%'.$request->input('customer').'%')))
            ->when($request->filled('employee'), fn ($q) => $q->where('employee_id', $request->input('employee')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->orderByDesc('sale_date')
            ->orderByDesc('id');

        return view('sales.index', [
            'sales' => $query->paginate(25)->withQueryString(),
            'employees' => \App\Models\User::orderBy('name')->get(),
        ]);
    }

    public function show(Request $request, Sale $sale): View
    {
        if (! $request->user()->canAccessBranch((int) $sale->branch_id)) {
            abort(403, 'You do not have access to that branch.');
        }

        $sale->load(['items.fuelProduct', 'items.nozzle', 'payments', 'customer', 'vehicle', 'employee', 'shift']);

        // WhatsApp share — pos/success wala hi pattern: customer ka
        // phone ho to us par, warna generic wa.me (share picker).
        $phone = $sale->customer_phone ?: $sale->customer?->phone;
        $cleanPhone = $phone ? preg_replace('/[^0-9]/', '', $phone) : '';
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '92' . substr($cleanPhone, 1);
        }

        $fuelSummary = $sale->items->map(fn ($item) => "{$item->fuelProduct?->name}: {$item->litres} L @ Rs. {$item->rate} = Rs. " . number_format((float) $item->amount, 2))->implode("\n");
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

        return view('sales.show', [
            'sale' => $sale,
            'whatsappUrl' => $whatsappUrl,
        ]);
    }
}
