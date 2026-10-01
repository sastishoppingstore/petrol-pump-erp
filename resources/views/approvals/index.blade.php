@extends('layouts.app')

@section('title', 'Approvals Centre / منظوری مرکز')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Approvals</li>
@endsection

{{--
    Approvals Centre — 2026 redesign.
    Page head centered; stats .stat-tile-3d; request cards glass-card;
    modals page ke end par Alpine state se (modal-bounce, backdrop + Esc).
    SAKHT NOTE: x-data state, tamam @click handlers, dynamic form actions
    ('/approvals/' + activeReqId + ...) aur form field names bilkul
    pehle jaisay hi hain.
--}}
@section('content')
<div x-data="{ approveModal: false, rejectModal: false, createModal: false, activeReqId: null, activeTitle: '' }" class="space-y-6">
    {{-- Header --}}
    <div class="page-head">
        <h1>✅ Approvals Centre / منظوری مرکز
            @if ($pendingCount > 0)
                <span class="ml-1 animate-pulse align-middle rounded-full bg-red-500/15 px-3 py-1 text-xs font-black text-red-600 dark:text-red-400">{{ $pendingCount }} Action Required</span>
            @endif
        </h1>
        <p>Owner &amp; Manager authorisation for meter corrections, credit overrides, shift shortages &amp; stock adjustments.</p>
        <div class="page-actions">
            <button @click="createModal = true" class="btn-3d btn-3d-primary">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Submit Approval Request
            </button>
            <a href="https://wa.me/923004342343?text={{ urlencode('السلام علیکم صاحب! مہر فلنگ اسٹیشن (وائٹل پیٹرولیم) سے زیرِ التواء منظوریوں کے لیے رابطہ کیا جا رہا ہے۔') }}" target="_blank" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                WhatsApp Owner ({{ $ownerPhone }})
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="stat-tile-3d stat-amber">
            <div class="stat-label">Pending Approvals</div>
            <div class="stat-value">{{ $pendingCount }}</div>
            <div class="stat-sub">Awaiting owner sign-off</div>
        </div>
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">Approved Today</div>
            <div class="stat-value">{{ $approvedToday }}</div>
            <div class="stat-sub">Processed with audit log</div>
        </div>
        <div class="stat-tile-3d" style="background: linear-gradient(150deg, #f87171 0%, #b91c1c 100%);">
            <div class="stat-label">Rejected Today</div>
            <div class="stat-value">{{ $rejectedToday }}</div>
            <div class="stat-sub">Declined requests</div>
        </div>
    </div>

    {{-- Filter Bar --}}
    <div class="glass-card flex flex-col items-center justify-between gap-4 p-4 lg:flex-row">
        <div class="flex flex-wrap items-center justify-center gap-2">
            @foreach (['PENDING' => 'Pending Queue', 'APPROVED' => 'Approved', 'REJECTED' => 'Rejected', 'ALL' => 'All Requests'] as $stKey => $stLabel)
                <a href="{{ route('approvals.index', ['status' => $stKey, 'type' => request('type')]) }}"
                   class="rounded-full px-4 py-1.5 text-xs font-bold transition {{ $status === $stKey ? 'bg-gradient-to-b from-vital-primary to-vital-darkred text-white shadow' : 'text-slate-600 hover:bg-slate-900/5 dark:text-slate-300 dark:hover:bg-white/10' }}">
                    {{ $stLabel }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('approvals.index') }}" class="flex items-center gap-2">
            <input type="hidden" name="status" value="{{ $status }}">
            <div class="field-3d">
                <select name="type" onchange="this.form.submit()" class="input-3d text-center text-xs">
                    <option value="">All Request Types</option>
                    <option value="METER_CORRECTION" @selected(request('type') === 'METER_CORRECTION')>Meter Correction</option>
                    <option value="CREDIT_OVERRIDE" @selected(request('type') === 'CREDIT_OVERRIDE')>Credit Override</option>
                    <option value="CASH_SHORTAGE" @selected(request('type') === 'CASH_SHORTAGE')>Cash Shortage Waiver</option>
                    <option value="LARGE_CASHOUT" @selected(request('type') === 'LARGE_CASHOUT')>Large Cash-Out</option>
                    <option value="STOCK_ADJUSTMENT" @selected(request('type') === 'STOCK_ADJUSTMENT')>Stock Adjustment</option>
                    <option value="EXPENSE" @selected(request('type') === 'EXPENSE')>Expense Authorization</option>
                    <option value="ADVANCE" @selected(request('type') === 'ADVANCE')>Staff Advance Loan</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Requests Cards --}}
    <div class="space-y-4">
        @forelse ($requests as $req)
            <div class="glass-card card-3d p-5">
                <div class="flex flex-col items-center gap-4 text-center">
                    <div class="space-y-2">
                        <div class="flex flex-wrap items-center justify-center gap-2">
                            <span @class([
                                'rounded-full px-2.5 py-0.5 text-xs font-black uppercase tracking-wider',
                                'bg-purple-500/15 text-purple-700 dark:text-purple-300' => $req->request_type === 'METER_CORRECTION',
                                'bg-amber-500/15 text-amber-700 dark:text-amber-300' => $req->request_type === 'CREDIT_OVERRIDE',
                                'bg-red-500/15 text-red-700 dark:text-red-300' => $req->request_type === 'CASH_SHORTAGE',
                                'bg-sky-500/15 text-sky-700 dark:text-sky-300' => $req->request_type === 'STOCK_ADJUSTMENT',
                                'bg-slate-900/5 text-slate-700 dark:bg-white/10 dark:text-slate-300' => in_array($req->request_type, ['LARGE_CASHOUT', 'EXPENSE', 'ADVANCE']),
                            ])>
                                {{ str_replace('_', ' ', $req->request_type) }}
                            </span>

                            <span @class([
                                'rounded-full px-2 py-0.5 text-[11px] font-black uppercase tracking-wide',
                                'bg-amber-500/15 text-amber-700 dark:text-amber-300' => $req->status === 'PENDING',
                                'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300' => $req->status === 'APPROVED',
                                'bg-red-500/15 text-red-700 dark:text-red-300' => $req->status === 'REJECTED',
                            ])>{{ $req->status }}</span>

                            <span class="text-xs text-slate-400">#{{ $req->id }}</span>
                        </div>

                        <h2 class="text-base font-black text-slate-900 dark:text-white">{{ $req->title }}</h2>
                        <p class="text-sm text-slate-600 dark:text-slate-300">{{ $req->description }}</p>

                        @if ($req->amount)
                            <div class="tabular font-mono text-lg font-black text-vital-primary">
                                Rs. {{ number_format((float) $req->amount, 2) }}
                                <span class="block text-xs font-normal text-slate-400 sm:inline">({{ \App\Support\PakistaniCurrency::toUrduWords((string) $req->amount) }})</span>
                            </div>
                        @endif

                        <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-xs text-slate-400">
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
                    <div class="flex flex-wrap items-center justify-center gap-2">
                        {{-- WhatsApp Link to Owner --}}
                        <a href="{{ $req->whatsapp_url ?: $req->generateWhatsAppUrl() }}" target="_blank"
                           class="btn-3d btn-3d-success btn-3d-sm">
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M.057 24l1.687-6.163c-1.041-1.804-1.588-3.849-1.587-5.946.003-6.556 5.338-11.891 11.893-11.891 3.181.001 6.167 1.24 8.413 3.488 2.245 2.248 3.481 5.236 3.48 8.414-.003 6.557-5.338 11.892-11.893 11.892-1.99-.001-3.951-.5-5.688-1.448l-6.305 1.654zm6.597-3.807c1.676.995 3.276 1.591 5.392 1.592 5.448 0 9.886-4.434 9.889-9.885.002-5.462-4.415-9.89-9.881-9.892-5.452 0-9.887 4.434-9.889 9.884-.001 2.225.651 3.891 1.746 5.634l-.999 3.648 3.742-.981z"/></svg>
                            WhatsApp Alert
                        </a>

                        @if ($req->isPending())
                            {{-- Big Green Approve --}}
                            <button type="button" @click="activeReqId = {{ $req->id }}; activeTitle = '{{ addslashes($req->title) }}'; approveModal = true"
                                    class="btn-3d btn-3d-success btn-3d-sm">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                APPROVE (منظور)
                            </button>

                            {{-- Big Red Reject --}}
                            <button type="button" @click="activeReqId = {{ $req->id }}; activeTitle = '{{ addslashes($req->title) }}'; rejectModal = true"
                                    class="btn-3d btn-3d-sm bg-gradient-to-b from-red-400 to-red-600 shadow">
                                <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                REJECT (مسترد)
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @empty
            <div class="glass-card p-12 text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 text-white shadow-lg">
                    <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </div>
                <h3 class="mt-4 text-base font-black text-slate-900 dark:text-white">All Clear! No pending approval requests.</h3>
                <p class="mt-1 text-xs text-slate-500">All meter adjustments, shortages, and overrides have been actioned.</p>
            </div>
        @endforelse
    </div>

    <div>{{ $requests->links() }}</div>

    {{-- Approve Modal with mandatory reason --}}
    <div x-show="approveModal" x-cloak x-on:keydown.escape.window="approveModal = false" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="approveModal = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
            <div class="modal-bounce glass-card relative w-full max-w-md p-6 text-center">
                <h3 class="text-base font-black text-emerald-600 dark:text-emerald-400">✅ Grant Approval</h3>
                <p class="mt-1 text-xs text-slate-500">Request: <strong x-text="activeTitle"></strong></p>

                <form :action="'/approvals/' + activeReqId + '/approve'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Approval Reason / Audit Trail *</label>
                        <textarea name="reason" rows="3" required placeholder="e.g. Verified with owner Sheikh Usman via phone; sanctioned calibration adjustment"
                                  class="input-3d text-center text-sm"></textarea>
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <button type="button" @click="approveModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-success btn-3d-sm">Confirm Approve</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Reject Modal with mandatory reason --}}
    <div x-show="rejectModal" x-cloak x-on:keydown.escape.window="rejectModal = false" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="rejectModal = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
            <div class="modal-bounce glass-card relative w-full max-w-md p-6 text-center">
                <h3 class="text-base font-black text-red-600 dark:text-red-400">🚨 Reject Request</h3>
                <p class="mt-1 text-xs text-slate-500">Request: <strong x-text="activeTitle"></strong></p>

                <form :action="'/approvals/' + activeReqId + '/reject'" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Rejection Reason *</label>
                        <textarea name="reason" rows="3" required placeholder="e.g. Unverified shortage; cashier must replenish difference from personal float"
                                  class="input-3d text-center text-sm"></textarea>
                    </div>
                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <button type="button" @click="rejectModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-sm bg-gradient-to-b from-red-400 to-red-600 shadow">Confirm Reject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Submit Approval Request Modal --}}
    <div x-show="createModal" x-cloak x-on:keydown.escape.window="createModal = false" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="createModal = false" class="fixed inset-0 bg-slate-950/60 backdrop-blur-sm"></div>
            <div class="modal-bounce glass-card relative w-full max-w-lg p-6 text-center">
                <h3 class="text-base font-black text-slate-900 dark:text-white">Submit Approval Request (درخواست برائے منظوری)</h3>

                <form action="{{ route('approvals.store') }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Request Type *</label>
                        <select name="request_type" required class="input-3d text-center text-sm">
                            <option value="METER_CORRECTION">Meter Correction (میٹر کی درستگی)</option>
                            <option value="CREDIT_OVERRIDE">Customer Credit Limit Override (ادھار کی حد میں اضافہ)</option>
                            <option value="CASH_SHORTAGE">Cash Shortage Waiver (کیش کی کمی کی معافی)</option>
                            <option value="LARGE_CASHOUT">Large Cash-Out (بڑی رقم کی ادائیگی)</option>
                            <option value="STOCK_ADJUSTMENT">Stock Adjustment (اسٹاک میں تبدیلی)</option>
                            <option value="EXPENSE">Special Expense (خصوصی خرچ)</option>
                            <option value="ADVANCE">Staff Loan / Advance (ملازم کو قرضہ)</option>
                        </select>
                    </div>

                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Title / عنوان *</label>
                        <input type="text" name="title" required placeholder="e.g. Nozzle 02 Pulsar Jump correction" class="input-3d text-center text-sm">
                    </div>

                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Amount Involved (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="amount" placeholder="e.g. 25000" class="input-3d text-center font-mono text-sm">
                    </div>

                    <div class="field-3d">
                        <label class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Full Description / تفصیل *</label>
                        <textarea name="description" rows="3" required placeholder="Detailed reason for request..." class="input-3d text-center text-sm"></textarea>
                    </div>

                    <div class="flex flex-wrap justify-center gap-2 pt-1">
                        <button type="button" @click="createModal = false" class="btn-3d btn-3d-ghost btn-3d-sm">Cancel</button>
                        <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">Submit Request</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
