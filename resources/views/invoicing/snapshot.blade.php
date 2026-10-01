@extends('layouts.app')

@section('title', {{ __('sales.snapshot.title') }})
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('invoices.index') }}" class="hover:text-vital-primary">{{ __('sales.snapshot.breadcrumb_invoices') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('sales.snapshot.breadcrumb_snapshot') }}</li>
@endsection

{{--
    Invoice Snapshot viewer (invoices.snapshot).
    Data InvoiceDesignerController@viewSnapshot se aata hai:
    $invoiceData (seller/buyer/items/totals/payment/metadata/fbr) aur
    $designSnapshot (layout/paper). Dono payloads defensive render
    hote hain — snapshot na mile to sale ki asal maloomat dikhti hai,
    page kabhi error nahi deti.
--}}
@section('content')
    @php
        $seller = $invoiceData['seller'] ?? [];
        $buyer = $invoiceData['buyer'] ?? [];
        $totals = $invoiceData['totals'] ?? [];
        $payment = $invoiceData['payment'] ?? [];
        $meta = $invoiceData['metadata'] ?? [];
        $fbr = $invoiceData['fbr'] ?? null;
        $items = $invoiceData['items'] ?? [];
        if (empty($items)) {
            $items = $sale->items->map(fn ($item) => [
                'description' => $item->fuelProduct?->name ?? 'Fuel',
                'quantity' => $item->litres,
                'unit' => 'Litres',
                'rate' => $item->rate,
                'amount' => $item->amount,
            ])->toArray();
        }
    @endphp

    <div class="page-head">
        <h1>🧾 {{ __('sales.snapshot.heading') }}</h1>
        <p>{{ $invoiceData['invoice_number'] ?? $sale->invoice_number }} {{ __('sales.snapshot.frozen_note') }}</p>
        <div class="page-actions">
            <a href="{{ route('invoices.index') }}" class="btn-3d btn-3d-ghost">← {{ __('sales.snapshot.invoices_link') }}</a>
            <button type="button" onclick="window.print()" class="btn-3d btn-3d-navy">🖨 {{ __('ui.actions.print') }}</button>
            @if (!empty($designSnapshot['layout']))
                <span class="pill-status pill-active">{{ $designSnapshot['layout'] }}@if (!empty($designSnapshot['paper_size'])) · {{ $designSnapshot['paper_size'] }} @endif</span>
            @endif
        </div>
    </div>

    @if (empty($invoiceData))
        <div class="glass-card mb-5 p-5 text-center text-sm font-semibold text-amber-600 dark:text-amber-300">
            ⚠ {{ __('sales.snapshot.warn_line1') }}
            {{ __('sales.snapshot.warn_line2') }}
        </div>
    @endif

    <div class="glass-card mx-auto w-full max-w-3xl p-7">
        {{-- Seller / invoice meta --}}
        <div class="mb-6 flex flex-wrap items-start justify-between gap-4 border-b border-slate-200/70 pb-5 dark:border-slate-700/60">
            <div>
                <div class="text-xl font-black text-slate-800 dark:text-white">{{ $seller['name'] ?? 'Fuel Station' }}</div>
                @if (!empty($seller['address'])) <div class="text-xs text-slate-400">{{ $seller['address'] }}</div> @endif
                @if (!empty($seller['phone'])) <div class="text-xs text-slate-400">📞 {{ $seller['phone'] }}</div> @endif
                @if (!empty($seller['ntn'])) <div class="text-xs text-slate-400">NTN: {{ $seller['ntn'] }} @if (!empty($seller['strn'])) · STRN: {{ $seller['strn'] }} @endif</div> @endif
            </div>
            <div class="text-right text-xs text-slate-500 dark:text-slate-400">
                <div class="text-base font-black text-slate-800 dark:text-white">{{ $invoiceData['invoice_number'] ?? $sale->invoice_number }}</div>
                <div>{{ $invoiceData['invoice_date'] ?? $sale->sale_date?->format('Y-m-d') }} {{ $invoiceData['invoice_time'] ?? '' }}</div>
                @if (!empty($invoiceData['invoice_type'])) <div>{{ $invoiceData['invoice_type'] }}</div> @endif
                <div>{{ __('sales.snapshot.status') }}: {{ $payment['status'] ?? $sale->status }}</div>
            </div>
        </div>

        {{-- Buyer --}}
        <div class="mb-6 rounded-2xl bg-slate-500/5 p-4 text-sm">
            <span class="font-black text-slate-700 dark:text-slate-200">{{ __('sales.snapshot.customer') }}</span>
            {{ $buyer['name'] ?? $sale->customer_name ?? 'Walk-in Customer' }}
            @if (!empty($buyer['phone'] ?? $sale->customer_phone)) · {{ $buyer['phone'] ?? $sale->customer_phone }} @endif
            @if (!empty($meta['vehicle']) && $meta['vehicle'] !== 'N/A') · 🚗 {{ $meta['vehicle'] }} @endif
            @if (!empty($meta['attendant'])) <div class="mt-1 text-xs text-slate-400">{{ __('sales.snapshot.attendant') }}: {{ $meta['attendant'] }} @if (!empty($meta['shift'])) · {{ __('sales.snapshot.shift') }} {{ $meta['shift'] }} @endif @if (!empty($meta['branch'])) · {{ $meta['branch'] }} @endif</div> @endif
            @if (!empty($meta['meter_readings']['start']) || !empty($meta['meter_readings']['end']))
                <div class="text-xs text-slate-400">{{ __('sales.snapshot.meter') }}: {{ $meta['meter_readings']['start'] ?? '—' }} → {{ $meta['meter_readings']['end'] ?? '—' }}</div>
            @endif
        </div>

        {{-- Items --}}
        <div class="table-3d mb-6">
            <table>
                <thead>
                    <tr><th>{{ __('sales.snapshot.item') }}</th><th>{{ __('sales.snapshot.qty') }}</th><th>{{ __('sales.snapshot.rate') }}</th><th>{{ __('sales.snapshot.amount') }}</th></tr>
                </thead>
                <tbody>
                    @forelse ($items as $item)
                        <tr>
                            <td class="font-semibold">{{ $item['description'] ?? 'Fuel' }}</td>
                            <td class="tabular text-xs">{{ number_format((float) ($item['quantity'] ?? 0), 3) }} {{ $item['unit'] ?? '' }}</td>
                            <td class="tabular text-xs">Rs. {{ number_format((float) ($item['rate'] ?? 0), 2) }}</td>
                            <td class="tabular text-xs font-black text-slate-800 dark:text-white">Rs. {{ number_format((float) ($item['amount'] ?? 0), 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-6 text-slate-400">{{ __('sales.snapshot.no_items') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Totals --}}
        <div class="ml-auto w-full max-w-xs space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-500">{{ __('sales.snapshot.subtotal') }}</span><span class="tabular font-semibold">Rs. {{ number_format((float) ($totals['subtotal'] ?? $sale->subtotal), 2) }}</span></div>
            @if (!empty($totals['discount'])) <div class="flex justify-between"><span class="text-slate-500">{{ __('sales.snapshot.discount') }}</span><span class="tabular font-semibold">− Rs. {{ number_format((float) $totals['discount'], 2) }}</span></div> @endif
            @if (!empty($totals['sales_tax'])) <div class="flex justify-between"><span class="text-slate-500">{{ __('sales.snapshot.sales_tax') }}</span><span class="tabular font-semibold">Rs. {{ number_format((float) $totals['sales_tax'], 2) }}</span></div> @endif
            @if (!empty($totals['petroleum_levy'])) <div class="flex justify-between"><span class="text-slate-500">{{ __('sales.snapshot.petroleum_levy') }}</span><span class="tabular font-semibold">Rs. {{ number_format((float) $totals['petroleum_levy'], 2) }}</span></div> @endif
            <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-black text-slate-800 dark:border-slate-700 dark:text-white">
                <span>{{ __('sales.snapshot.total') }}</span><span class="tabular">Rs. {{ number_format((float) ($totals['total'] ?? $sale->total), 2) }}</span>
            </div>
            <div class="flex justify-between text-xs text-slate-400">
                <span>{{ __('sales.snapshot.payment') }}</span><span>{{ $payment['method'] ?? 'CASH' }}</span>
            </div>
        </div>

        {{-- FBR block --}}
        @if (!empty($fbr))
            <div class="mt-6 rounded-2xl border border-emerald-300/50 bg-emerald-500/5 p-4 text-center text-xs text-slate-500 dark:border-emerald-700/40 dark:text-slate-400">
                <div class="mb-1 font-black text-emerald-600 dark:text-emerald-400">{{ __('sales.snapshot.fbr_heading') }}</div>
                <div>{{ __('sales.snapshot.invoice_label') }}: {{ $fbr['invoice_number'] ?? '' }} · {{ $fbr['invoice_date'] ?? '' }} · NTN: {{ $fbr['ntn'] ?? '' }}</div>
                @if (!empty($fbr['certificate_serial'])) <div>{{ __('sales.snapshot.certificate') }}: {{ $fbr['certificate_serial'] }}</div> @endif
                @if (!empty($fbr['qr_payload'])) <div class="mt-1 break-all font-mono text-[10px]">{{ $fbr['qr_payload'] }}</div> @endif
            </div>
        @endif

        @if ($snapshot)
            <div class="mt-6 text-center text-[11px] text-slate-400">
                {{ __('sales.snapshot.snapshot_word') }} #{{ $snapshot->id }} · {{ __('sales.snapshot.generated') }} {{ $snapshot->created_at?->format('d M Y H:i') }}
            </div>
        @endif
    </div>
@endsection
