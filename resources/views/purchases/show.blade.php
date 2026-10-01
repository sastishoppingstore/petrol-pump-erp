@extends('layouts.app')

@section('title', 'Purchase #' . $purchase->purchase_number . ' — Fuel Tanker Decantation')

@section('breadcrumb')
    <li class="flex items-center gap-1"><span>/</span><a href="{{ route('purchases.index') }}" class="hover:text-slate-700 dark:hover:text-slate-200">Purchases</a></li>
    <li class="flex items-center gap-1"><span>/</span><span class="text-slate-700 dark:text-slate-300">{{ $purchase->purchase_number }}</span></li>
@endsection

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    {{-- ================= Page Head (centered) ================= --}}
    <div class="page-head">
        <h1 class="font-mono">{{ $purchase->purchase_number }}</h1>
        <p>
            <span @class([
                'inline-flex items-center rounded-full px-2.5 py-0.5 align-middle text-xs font-bold',
                'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' => $purchase->status === 'RECEIVED',
                'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300' => $purchase->status === 'APPROVED',
                'bg-red-100 text-red-800 dark:bg-red-950/60 dark:text-red-300' => $purchase->status === 'VOID',
            ])>{{ $purchase->status }}</span>
        </p>
        <p>OMC Fuel Decantation at Mehar Filling Station (Vital Petroleum Franchise), Sheikhupura.</p>
        <div class="page-actions">
            <a href="{{ route('purchases.index') }}" class="btn-3d btn-3d-ghost">← All Purchases</a>
            @if(auth()->user()->hasPermission(\App\Support\PermissionList::PURCHASE_CREATE))
                <a href="{{ route('purchases.create') }}" class="btn-3d btn-3d-primary">+ New Decantation</a>
            @endif
        </div>
    </div>

    {{-- ================= Status Alerts ================= --}}
    @if($purchase->status === \App\Models\Purchase::STATUS_RECEIVED)
        <div class="glass-card border-2 border-amber-400 bg-amber-50/70 p-5 dark:border-amber-600 dark:bg-amber-950/20">
            <div class="flex flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                    <span class="text-3xl" aria-hidden="true">⏳</span>
                    <div>
                        <h3 class="text-sm font-bold text-amber-900 dark:text-amber-300">Pending Manager Approval</h3>
                        <p class="mt-0.5 text-xs text-amber-800 dark:text-amber-400">
                            Decanted volume has not yet been credited to Tank #{{ $purchase->tank?->tank_number ?? 'N/A' }} stock, and supplier ledger is not yet credited. Click approve to commit the stock and update weighted-average costing.
                        </p>
                    </div>
                </div>

                @if(auth()->user()->hasPermission(\App\Support\PermissionList::PURCHASE_APPROVE))
                    <form method="POST" action="{{ route('purchases.approve', $purchase) }}" onsubmit="return confirm('Confirm and approve fuel decantation into Tank #{{ $purchase->tank?->tank_number }}? This will commit physical inventory and credit the supplier ledger.')">
                        @csrf
                        <button type="submit" class="btn-3d btn-3d-success w-full text-xs font-extrabold uppercase tracking-wider sm:w-auto">
                            <span aria-hidden="true">✓</span> Approve &amp; Commit Stock
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @elseif($purchase->status === \App\Models\Purchase::STATUS_APPROVED)
        <div class="glass-card border border-emerald-300 bg-emerald-50/70 p-4 dark:border-emerald-800 dark:bg-emerald-950/20">
            <div class="flex items-center justify-center gap-3 text-center sm:justify-start sm:text-left">
                <span class="text-2xl text-emerald-600" aria-hidden="true">✓</span>
                <div>
                    <h3 class="text-xs font-bold uppercase tracking-wider text-emerald-900 dark:text-emerald-300">
                        Decantation Approved &amp; Finalized
                    </h3>
                    <p class="mt-0.5 text-xs text-emerald-800 dark:text-emerald-400">
                        Stock of <strong>{{ \App\Support\Quantity::format($purchase->volume_received) }} L</strong> added to Tank #{{ $purchase->tank?->tank_number }}. Approved by <strong>{{ $purchase->approver->name ?? 'Manager' }}</strong> on {{ $purchase->approved_at?->format('d/m/Y h:i A') ?? 'N/A' }}.
                    </p>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= Shortage Banner & Claim Resolution ================= --}}
    @if($purchase->shortage_claimed)
        <div class="glass-card border-2 border-red-500 bg-red-50/70 p-5 dark:border-red-600 dark:bg-red-950/20">
            <div class="flex flex-col items-center justify-between gap-4 text-center sm:flex-row sm:items-start sm:text-left">
                <div class="flex flex-col items-center gap-3 sm:flex-row sm:items-start">
                    <span class="text-3xl" aria-hidden="true">⚠️</span>
                    <div>
                        <div class="flex flex-wrap items-center justify-center gap-2 sm:justify-start">
                            <h3 class="text-sm font-bold text-red-900 dark:text-red-300">
                                Tanker Transit Shortage: {{ \App\Support\Quantity::format($purchase->shortage_litres) }} Litres
                            </h3>
                            <span class="rounded bg-red-200 px-2 py-0.5 text-[10px] font-bold text-red-800 dark:bg-red-900 dark:text-red-200">
                                Claim Status: {{ $purchase->shortage_claim_status }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-red-700 dark:text-red-400">
                            Challan: <strong>{{ \App\Support\Quantity::format($purchase->volume_ordered) }} L</strong> vs Decanted: <strong>{{ \App\Support\Quantity::format($purchase->volume_received) }} L</strong>. Shortage Amount: <strong>Rs. {{ \App\Support\Money::format($purchase->shortage_amount) }}</strong>.
                        </p>
                    </div>
                </div>

                {{-- Shortage Claim Resolution Form --}}
                @if(auth()->user()->hasPermission(\App\Support\PermissionList::PURCHASE_APPROVE) && $purchase->shortage_claim_status === 'PENDING')
                    <div class="glass-card p-3">
                        <form method="POST" action="{{ route('purchases.shortage-claim', $purchase) }}" class="space-y-2">
                            @csrf
                            <div class="text-center text-[11px] font-bold text-slate-700 dark:text-slate-300">Resolve Shortage Claim:</div>
                            <div class="flex items-center justify-center gap-2">
                                <select name="status" required class="input-3d px-2 py-1 text-center text-xs">
                                    <option value="APPROVED">Approve Claim</option>
                                    <option value="SETTLED">Settled with Transporter</option>
                                    <option value="REJECTED">Reject Claim</option>
                                </select>
                                <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm text-xs">
                                    Update
                                </button>
                            </div>
                            <label class="flex items-center justify-center gap-1.5 text-[11px] text-slate-600 dark:text-slate-400">
                                <input type="checkbox" name="debit_supplier" value="1" class="rounded border-slate-300 text-red-600 focus:ring-red-500">
                                <span>Debit supplier ledger (Issue debit note)</span>
                            </label>
                        </form>
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ================= Main Grid ================= --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        {{-- Card 1: Logistics & Tanker Details --}}
        <div class="glass-card card-3d p-5">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-700/60">
                🚛 Tanker &amp; Challan Logistics
            </h2>
            <dl class="mt-3 space-y-2.5 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Challan / Bilty #:</dt>
                    <dd class="font-mono font-bold text-slate-900 dark:text-white">{{ $purchase->challan_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Supplier Invoice #:</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $purchase->invoice_number ?? 'Not Provided' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Tanker / Bowser #:</dt>
                    <dd class="font-mono font-bold text-slate-900 dark:text-white">{{ $purchase->tanker_number }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Driver Name:</dt>
                    <dd class="text-slate-900 dark:text-white">{{ $purchase->driver_name }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Delivery Date:</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $purchase->purchase_date->format('d M Y') }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Recorded By:</dt>
                    <dd class="text-slate-900 dark:text-white">{{ $purchase->creator->name ?? 'System' }}</dd>
                </div>
                @if($purchase->density)
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Density:</dt>
                        <dd class="font-mono text-slate-900 dark:text-white">{{ $purchase->density }} kg/m³</dd>
                    </div>
                @endif
                @if($purchase->temperature)
                    <div class="flex justify-between">
                        <dt class="text-slate-500">Temperature:</dt>
                        <dd class="font-mono text-slate-900 dark:text-white">{{ $purchase->temperature }} °C</dd>
                    </div>
                @endif
            </dl>
        </div>

        {{-- Card 2: Destination Tank & Dips --}}
        <div class="glass-card card-3d p-5">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-700/60">
                🛢️ Destination Storage Tank
            </h2>
            <dl class="mt-3 space-y-2.5 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Tank Number:</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">
                        <a href="{{ route('tanks.calibration', $purchase->tank) }}" class="text-vital-primary hover:underline">
                            Tank #{{ $purchase->tank?->tank_number ?? 'N/A' }} ↗
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Fuel Grade:</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">{{ $purchase->fuelProduct?->name ?? 'Fuel' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Tank Capacity:</dt>
                    <dd class="tabular font-mono text-slate-900 dark:text-white">{{ \App\Support\Quantity::format($purchase->tank?->capacity ?? '0') }} L</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Current Stock in Tank:</dt>
                    <dd class="tabular font-mono font-bold text-slate-900 dark:text-white">{{ \App\Support\Quantity::format($purchase->tank?->current_stock ?? '0') }} L</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Dip Before Decantation:</dt>
                    <dd class="tabular font-mono text-slate-900 dark:text-white">{{ $purchase->dip_before ? $purchase->dip_before . ' cm' : 'Not recorded' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Dip After Decantation:</dt>
                    <dd class="tabular font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ $purchase->dip_after ? $purchase->dip_after . ' cm' : 'Not recorded' }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-200/70 pt-1 dark:border-slate-700/60">
                    <dt class="text-slate-500">Weighted Average Cost:</dt>
                    <dd class="tabular font-mono font-bold text-slate-900 dark:text-white">
                        Rs. {{ \App\Support\Money::format($purchase->fuelProduct?->average_cost ?? $purchase->purchase_rate) }} / L
                    </dd>
                </div>
            </dl>
        </div>

        {{-- Card 3: Supplier Creditor Info --}}
        <div class="glass-card card-3d p-5">
            <h2 class="border-b border-slate-200/70 pb-2 text-center text-xs font-bold uppercase tracking-wider text-slate-500 dark:border-slate-700/60">
                🏢 Supplier &amp; Creditor Details
            </h2>
            <dl class="mt-3 space-y-2.5 text-xs">
                <div class="flex justify-between">
                    <dt class="text-slate-500">Supplier Name:</dt>
                    <dd class="font-bold text-slate-900 dark:text-white">
                        <a href="{{ route('suppliers.show', $purchase->supplier) }}" class="text-vital-primary hover:underline">
                            {{ $purchase->supplier->name ?? 'N/A' }} ↗
                        </a>
                    </dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Supplier Code:</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $purchase->supplier->code ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">Phone:</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $purchase->supplier->phone ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-slate-500">NTN / STRN:</dt>
                    <dd class="font-mono text-slate-900 dark:text-white">{{ $purchase->supplier->ntn_number ?? 'N/A' }}</dd>
                </div>
                <div class="flex justify-between border-t border-slate-200/70 pt-1 dark:border-slate-700/60">
                    <dt class="text-slate-500">Current Payable Balance:</dt>
                    <dd class="tabular text-base font-bold text-red-600 dark:text-red-400">
                        {{ \App\Support\PakistaniCurrency::format($purchase->supplier->current_balance ?? '0') }}
                    </dd>
                </div>
                <div class="text-center">
                    <a href="{{ route('suppliers.statement', $purchase->supplier) }}" class="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-slate-700 hover:text-vital-primary dark:text-slate-300">
                        <span aria-hidden="true">📄</span> View Full Supplier Ledger Statement →
                    </a>
                </div>
            </dl>
        </div>
    </div>

    {{-- ================= Financial Breakdown ================= --}}
    <div class="glass-card overflow-hidden">
        <div class="border-b border-slate-200/70 p-4 text-center dark:border-slate-700/60">
            <h2 class="flex items-center justify-center gap-2 text-sm font-bold text-slate-900 dark:text-white">
                <span aria-hidden="true">💵</span> Financial Summary &amp; Invoice Cost Components
            </h2>
        </div>

        <div class="p-6">
            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div class="space-y-2 text-xs">
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="text-slate-500">Challan Ordered Volume:</span>
                        <span class="tabular font-mono font-bold text-slate-900 dark:text-white">{{ \App\Support\Quantity::format($purchase->volume_ordered) }} L</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="text-slate-500">Actual Decanted Volume:</span>
                        <span class="tabular font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ \App\Support\Quantity::format($purchase->volume_received) }} L</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="text-slate-500">Purchase Rate per Litre:</span>
                        <span class="tabular font-mono text-slate-900 dark:text-white">Rs. {{ \App\Support\Money::format($purchase->purchase_rate) }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="font-semibold text-slate-500">Fuel Subtotal (Decanted × Rate):</span>
                        <span class="tabular font-mono font-bold text-slate-900 dark:text-white">Rs. {{ \App\Support\Money::format($purchase->subtotal) }}</span>
                    </div>
                </div>

                <div class="space-y-2 text-xs">
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="text-slate-500">IFEM (Inland Freight Equalisation Margin):</span>
                        <span class="tabular font-mono text-slate-900 dark:text-white">Rs. {{ \App\Support\Money::format($purchase->ifem ?? '0') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="text-slate-500">Petroleum Levy (PL):</span>
                        <span class="tabular font-mono text-slate-900 dark:text-white">Rs. {{ \App\Support\Money::format($purchase->petroleum_levy ?? '0') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="text-slate-500">Freight Charges (Bowser Carriage):</span>
                        <span class="tabular font-mono text-slate-900 dark:text-white">Rs. {{ \App\Support\Money::format($purchase->freight_charges ?? '0') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="text-slate-500">Sales Tax / Provincial Tax:</span>
                        <span class="tabular font-mono text-slate-900 dark:text-white">Rs. {{ \App\Support\Money::format($purchase->tax_amount ?? '0') }}</span>
                    </div>
                    <div class="flex justify-between border-b border-slate-200/60 py-1.5 dark:border-slate-700/50">
                        <span class="text-slate-500">Other Charges / Tolls:</span>
                        <span class="tabular font-mono text-slate-900 dark:text-white">Rs. {{ \App\Support\Money::format($purchase->other_charges ?? '0') }}</span>
                    </div>
                </div>
            </div>

            {{-- Net Total Box --}}
            <div class="glass-card mt-6 border-2 border-red-600 bg-red-50/60 p-5 dark:bg-red-950/20">
                <div class="flex flex-col items-center justify-between gap-4 text-center sm:flex-row sm:text-left">
                    <div>
                        <div class="text-xs font-bold uppercase tracking-wider text-red-700 dark:text-red-400">Total Purchase Value</div>
                        <div class="tabular mt-1 font-mono text-3xl font-black text-red-600 dark:text-red-400">
                            {{ \App\Support\PakistaniCurrency::format($purchase->total_amount) }}
                        </div>
                    </div>

                    <div class="text-center sm:border-l sm:border-red-200 sm:pl-6 sm:text-right dark:border-red-900">
                        <div class="text-[11px] font-semibold text-slate-500">Amount in Words (اردو میں رقم):</div>
                        <div class="dir-rtl mt-0.5 text-base font-bold text-slate-900 dark:text-white">
                            {{ \App\Support\PakistaniCurrency::toUrduWords($purchase->total_amount) }}
                        </div>
                    </div>
                </div>
            </div>

            @if($purchase->notes)
                <div class="glass-card mt-4 p-3 text-center text-xs text-slate-600 dark:text-slate-300">
                    <strong>Decantation Notes:</strong> {{ $purchase->notes }}
                </div>
            @endif

            @if($purchase->bill_photo_path)
                <div class="mt-4 border-t border-slate-200/70 pt-4 text-center dark:border-slate-700/60">
                    <span class="text-xs font-bold text-slate-700 dark:text-slate-300">Challan / Bilty Photo:</span>
                    <div class="mt-2">
                        <a href="{{ asset('storage/' . $purchase->bill_photo_path) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm">
                            <span aria-hidden="true">📎</span> View Attached Delivery Docket Photo ↗
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
