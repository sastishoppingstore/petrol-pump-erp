@extends('layouts.app')

@section('title', 'Approvals Centre / منظوری مرکز')
@section('breadcrumb')
    <li class="text-slate-500">Approvals</li>
@endsection

@section('content')
<div x-data="{ approveModal: false, rejectModal: false, createModal: false, activeReqId: null, activeTitle: '' }" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Approvals Centre / منظوری مرکز</h1>
                @if ($pendingCount > 0)
                    <span class="animate-pulse rounded-full bg-red-100 px-3 py-0.5 text-xs font-bold text-red-800 dark:bg-red-900/40 dark:text-red-300">
                        {{ $pendingCount }} Action Required
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-500">
                Owner &amp; Manager authorisation for meter corrections, credit overrides, shift shortages &amp; stock adjustments.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button @click="createModal = true" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Submit Approval Request
            </button>
            <a href="https://wa.me/923004342343?text={{ urlencode('السلام علیکم صاحب! مہر فلنگ اسٹیشن (وائٹل پیٹرولیم) سے زیرِ التواء منظوریوں کے لیے رابطہ کیا جا رہا ہے۔') }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                WhatsApp Owner ({{ $ownerPhone }})
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-amber-600">Pending Approvals</span>
            <div class="mt-2 text-2xl font-black text-amber-600">{{ $pendingCount }}</div>
            <div class="mt-0.5 text-xs text-slate-400">Awaiting owner sign-off</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-emerald-600">Approved Today</span>
            <div class="mt-2 text-2xl font-black text-emerald-600">{{ $approvedToday }}</div>
            <div class="mt-0.5 text-xs text-slate-400">Processed with audit log</div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <span class="text-xs font-semibold uppercase text-red-600">Rejected Today</span>
            <div class="mt-2 text-2xl font-black text-red-600">{{ $rejectedToday }}</div>
            <div class="mt-0.5 text-xs text-slate-400">Declined requests</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <div class="flex items-center gap-2">
            @foreach (['PENDING' => 'Pending Queue', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'ALL' => 'All Requests'] as $stKey => $stLabel)
                <a href="{{ route('approvals.index', ['status' => $stKey, 'type' => request('type')]) }}"
                   class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ $status === $stKey ? 'bg-red-600 text-white' : 'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-800' }}">
                    {{ $stLabel }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('approvals.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <select name="type" onchange="this.form.submit()" class="rounded-lg border-slate-300 text-xs dark:border-slate-700 dark:bg-slate-800">
                <option value="">All Request Types</option>
                <option value="METER_CORRECTION" @selected(request('type') === 'METER_CORRECTION')>Meter Correction</option>
                <option value="CREDIT_OVERRIDE" @selected(request('type') === 'CREDIT_OVERRIDE')>Credit Override</option>
                <option value="CASH_SHORTAGE" @selected(request('type') === 'CASH_SHORTAGE')>Cash Shortage Waiver</option>
                <option value="LARGE_CASHOUT" @selected(request('type') === 'LARGE_CASHOUT')>Large Cash-Out</option>
                <option value="STOCK_ADJUSTMENT" @selected(request('type') === 'STOCK_ADJUSTMENT')>Stock Adjustment</option>
                <option value="EXPENSE" @selected(request('type') === 'EXPENSE')>Expense Authorization</option>
                <option value="ADVANCE" @selected(request('type') === 'ADVANCE')>Staff Advance Loan</option>
            </select>
        </form>
    </div>

    {{-- Requests Table / Cards --}}
    <div class="space-y-4">
        @forelse ($requests as $req)
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span @class([
                                'rounded px-2.5 py-0.5 text-xs font-bold uppercase tracking-wider',
                                'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' => $req->request_type === 'METER_CORRECTION',
                                'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' => $req->request_type === 'CREDIT_OVERRIDE',
                                'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' => $req->request_type === 'CASH_SHORTAGE',
                                'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' => $req->request_type === 'STOCK_ADJUSTMENT',
                                'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300' => in_array($req->request_type, ['LARGE_CASHOUT', 'EXPENSE', 'ADVANCE']),
                            ])>
                                {{ str_replace('_', ' ', $req->request_type) }}
                            </span>

                            <span @class([
                                'rounded px-2 py-0.5 text-[11px] font-semibold',
                                'bg-amber-100 text-amber-800' => $req->status === 'PENDING',
                                'bg-emerald-100 text-emerald-800' => $req->status === 'APPROVED',
                                'bg-red-100 text-red-800' => $req->status === 'REJECTED',
                            ])>{{ $req->status }}</span>

                            <span class="text-xs text-slate-400">#{{ $req->id }}</span>
                        </div>

                        <h2 class="text-base font-bold text-slate-900 dark:text-white">{{ $req->title }}</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $req->description }}</p>

                        @if ($req->amount)
                            <div class="mt-2 font-mono text-base font-bold text-red-600">
                                Rs. {{ number_format((float) $req->amount, 2) }}
                                <span class="text-xs text-slate-400 font-normal">({{ \App\Support\PakistaniCurrency::toUrduWords((string) $req->amount) }})</span>
                            </div>
                        @endif

                        <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-slate-400">
                            <span>Requested by: <strong class="text-slate-700 dark:text-slate-300">{{ $req->requester?->name ?? 'Staff' }}</strong></span>
                            <span>&bull;</span>
                            <span>{{ $req->created_at?->diffForHumans() }} ({{ $req->created_at?->format('d M Y, h:i A') }})</span>
                            @if ($req->actioner)
                                <span>&bull;</span>
                                <span>Actioned by: <strong class="text-slate-700 dark:text-slate-300">{{ $req->actioner->name }}</strong> ({{ $req->action_reason }})</span>
                            @endif
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="flex flex-wrap items-center gap-2">
                        {{-- WhatsApp Link to Owner --}}
                        <a href="{{ $req->whatsapp_url ?: $req->generateWhatsAppUrl() }}" target="_blank"
                           class="inline-flex items-center gap-1.5 rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            WhatsApp Alert
                        </a>

                        @if ($req->isPending())
                            {{-- Big Green Approve --}}
                            <button type="button" @click="activeReqId = {{ $req->id }}; activeTitle = '{{ addslashes($req->title) }}'; approveModal = true"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-emerald-600 px-4 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-emerald-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                APPROVE (منظور)
                            </button>

                            {{-- Big Red Reject --}}
                            <button type="button" @click="activeReqId = {{ $req->id }}; activeTitle = '{{ addslashes($req->title) }}'; rejectModal = true"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-1.5 text-xs font-bold text-white shadow-xs hover:bg-red-700">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                REJECT (مسترد)
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="rounded-2xl border border-dashed border-slate-200 bg-white p-12 text-center dark:border-slate-800 dark:bg-slate-900">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="mt-3 text-base font-bold text-slate-900 dark:text-white">All Clear! No pending approval requests.</h3>
                <p class="mt-1 text-xs text-slate-500">All meter adjustments, shortages, and overrides have been actioned.</p>
            </div>
        @endforelse
    </div>

    <div class="px-4 py-3">{{ $requests->links() }}</div>

    {{-- Approve Modal with mandatory reason --}}
    <div x-show="approveModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="approveModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-emerald-700">✅ Grant Approval</h3>
                <p class="mt-1 text-xs text-slate-500">Request: <strong x-text="activeTitle"></strong></p>

                <form :action="'/approvals/' + activeReqId + '/approve'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Approval Reason / Audit Trail *</label>
                        <textarea name="reason" rows="3" required placeholder="e.g. Verified with owner Sheikh Usman via phone; sanctioned calibration adjustment"
                                  class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="approveModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-emerald-600 px-5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Confirm Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal with mandatory reason --}}
    <div x-show="rejectModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="rejectModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-red-600">🚨 Reject Request</h3>
                <p class="mt-1 text-xs text-slate-500">Request: <strong x-text="activeTitle"></strong></p>

                <form :action="'/approvals/' + activeReqId + '/reject'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Rejection Reason *</label>
                        <textarea name="reason" rows="3" required placeholder="e.g. Unverified shortage; cashier must replenish difference from personal float"
                                  class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"></textarea>
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="rejectModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">Confirm Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Submit Approval Request Modal --}}
    <div x-show="createModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="createModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Submit Approval Request (درخواست برائے منظوری)</h3>

                <form action="{{ route('approvals.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Request Type *</label>
                        <select name="request_type" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="METER_CORRECTION">Meter Correction (میٹر کی درستگی)</option>
                            <option value="CREDIT_OVERRIDE">Customer Credit Limit Override (ادھار کی حد میں اضافہ)</option>
                            <option value="CASH_SHORTAGE">Cash Shortage Waiver (کیش کی کمی کی معافی)</option>
                            <option value="LARGE_CASHOUT">Large Cash-Out (بڑی رقم کی ادائیگی)</option>
                            <option value="STOCK_ADJUSTMENT">Stock Adjustment (اسٹاک میں تبدیلی)</option>
                            <option value="EXPENSE">Special Expense (خصوصی خرچ)</option>
                            <option value="ADVANCE">Staff Loan / Advance (ملازم کو قرضہ)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Title / عنوان *</label>
                        <input type="text" name="title" required placeholder="e.g. Nozzle 02 Pulsar Jump correction" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Amount Involved (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="amount" placeholder="e.g. 25000" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Full Description / تفصیل *</label>
                        <textarea name="description" rows="3" required placeholder="Detailed reason for request..." class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800"></textarea>
                    </div>

                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="createModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white hover:bg-red-700">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
