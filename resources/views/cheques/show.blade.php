@extends('layouts.app')

@section('title', 'Cheque #' . $cheque->cheque_number)
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('cheques.index') }}">Cheques</a></li>
    <li class="text-slate-500">#{{ $cheque->cheque_number }}</li>
@endsection

@section('content')
<div x-data="{ depositModal: false, bounceModal: false, cancelModal: false }" class="mx-auto max-w-4xl space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Cheque #{{ $cheque->cheque_number }}
                </h1>
                <span @class([
                    'rounded-full px-3 py-0.5 text-xs font-semibold',
                    'bg-amber-100 text-amber-800' => $cheque->status === 'RECEIVED',
                    'bg-sky-100 text-sky-800' => $cheque->status === 'DEPOSITED',
                    'bg-emerald-100 text-emerald-800' => $cheque->status === 'CLEARED',
                    'bg-red-100 text-red-800' => $cheque->status === 'BOUNCED',
                    'bg-slate-100 text-slate-600' => $cheque->status === 'CANCELLED',
                ])>{{ $cheque->status }}</span>
                @if ($cheque->isPdc())
                    <span class="rounded bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700">PDC (Post-Dated)</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-500">
                {{ $cheque->type === 'RECEIVED' ? 'Received from customer' : 'Issued to supplier' }} &bull; {{ $cheque->partyName() }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($cheque->type === 'RECEIVED' && $cheque->status === 'RECEIVED')
                <button @click="depositModal = true" class="rounded-lg bg-sky-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-sky-700">
                    Deposit into Bank
                </button>
            @endif

            @if (in_array($cheque->status, ['RECEIVED', 'DEPOSITED']))
                <form action="{{ route('cheques.clear', $cheque) }}" method="POST" onsubmit="return confirm('Clear cheque #{{ $cheque->cheque_number }} and credit bank balance?');" class="inline">
                    @csrf
                    <button type="submit" class="rounded-lg bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-emerald-700">
                        Mark Cleared
                    </button>
                </form>
            @endif

            @if (! in_array($cheque->status, ['BOUNCED', 'CANCELLED']))
                <button @click="bounceModal = true" class="rounded-lg bg-red-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-red-700">
                    Bounce Cheque
                </button>
            @endif

            @if (! in_array($cheque->status, ['CLEARED', 'BOUNCED', 'CANCELLED']))
                <button @click="cancelModal = true" class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                    Cancel
                </button>
            @endif

            <button onclick="window.print()" class="rounded-lg border border-slate-300 px-3.5 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                Print
            </button>
        </div>
    </div>

    {{-- Cheque Details Card --}}
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="mb-4 text-base font-bold text-slate-900 dark:text-white">Cheque Specifications</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">Party (Party Name)</span>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $cheque->partyName() }}</div>
                        <div class="text-xs text-slate-500">{{ $cheque->type === 'RECEIVED' ? 'Customer Account' : 'Supplier Account' }}</div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">Drawee Bank</span>
                        <div class="mt-1 text-sm font-bold text-slate-900 dark:text-white">{{ $cheque->bank_name }}</div>
                        @if ($cheque->bankAccount)
                            <div class="text-xs text-slate-500">Station Account: {{ $cheque->bankAccount->account_title }}</div>
                        @endif
                    </div>

                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">Cheque Amount (رقم)</span>
                        <div class="mt-1 font-mono text-2xl font-bold text-slate-900 dark:text-white">
                            Rs. {{ number_format((float) $cheque->amount, 2) }}
                        </div>
                        <div class="text-xs text-red-600 font-medium">
                            {{ \App\Support\PakistaniCurrency::toUrduWords((string) $cheque->amount) }}
                        </div>
                    </div>

                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">Payee Name on Face</span>
                        <div class="mt-1 text-sm text-slate-800 dark:text-slate-200">{{ $cheque->payee_name ?: '—' }}</div>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 border-t border-slate-100 pt-4 sm:grid-cols-3 dark:border-slate-800">
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">Date on Cheque</span>
                        <div class="mt-1 text-sm font-medium">{{ $cheque->cheque_date?->format('d M Y') }}</div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">Due / Maturity Date</span>
                        <div class="mt-1 text-sm font-medium {{ $cheque->due_date?->isFuture() ? 'text-sky-600 font-bold' : '' }}">
                            {{ $cheque->due_date?->format('d M Y') }}
                        </div>
                    </div>
                    <div>
                        <span class="text-xs font-semibold uppercase text-slate-400">Recorded By</span>
                        <div class="mt-1 text-sm text-slate-600 dark:text-slate-400">{{ $cheque->creator?->name ?? 'System' }}</div>
                    </div>
                </div>

                @if ($cheque->notes)
                    <div class="mt-6 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <span class="text-xs font-semibold uppercase text-slate-400">Notes / تفصیل</span>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $cheque->notes }}</p>
                    </div>
                @endif
            </div>

            {{-- Bounce / Settlement Details if applicable --}}
            @if ($cheque->status === 'BOUNCED')
                <div class="rounded-2xl border border-red-200 bg-red-50/50 p-6 dark:border-red-900/50 dark:bg-red-900/20">
                    <h3 class="text-base font-bold text-red-700 dark:text-red-400">🚨 Cheque Bounce Record</h3>
                    <div class="mt-3 grid gap-4 sm:grid-cols-3 text-sm">
                        <div>
                            <span class="text-xs text-red-500 uppercase font-semibold">Bounce Date</span>
                            <div class="font-bold text-red-900 dark:text-red-200">{{ $cheque->bounced_date?->format('d M Y') }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-red-500 uppercase font-semibold">Reason</span>
                            <div class="font-bold text-red-900 dark:text-red-200">{{ $cheque->bounce_reason }}</div>
                        </div>
                        <div>
                            <span class="text-xs text-red-500 uppercase font-semibold">Bank Penalty Charged</span>
                            <div class="font-mono font-bold text-red-900 dark:text-red-200">Rs. {{ number_format((float) $cheque->bank_charges, 2) }}</div>
                        </div>
                    </div>
                    <p class="mt-3 text-xs text-red-600">
                        The customer's ledger payment was reversed and penalty charges debited.
                    </p>
                </div>
            @endif

            @if ($cheque->status === 'CLEARED')
                <div class="rounded-2xl border border-emerald-200 bg-emerald-50/50 p-6 dark:border-emerald-900/50 dark:bg-emerald-900/20">
                    <h3 class="text-base font-bold text-emerald-700 dark:text-emerald-400">✅ Cleared &amp; Settled</h3>
                    <p class="mt-1 text-sm text-emerald-800 dark:text-emerald-300">
                        Cleared on {{ $cheque->cleared_date?->format('d M Y') }}. Funds settled in bank book.
                    </p>
                </div>
            @endif
        </div>

        {{-- Cheque Image Preview --}}
        <div class="lg:col-span-1">
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="mb-3 text-sm font-bold text-slate-900 dark:text-white">Cheque Scan / Photo</h3>
                @if ($cheque->image_path)
                    <div class="overflow-hidden rounded-xl border border-slate-200 bg-slate-50 dark:border-slate-700 dark:bg-slate-800">
                        <img src="{{ asset('storage/' . $cheque->image_path) }}" alt="Cheque #{{ $cheque->cheque_number }}" class="w-full object-cover">
                    </div>
                    <div class="mt-3">
                        <a href="{{ asset('storage/' . $cheque->image_path) }}" target="_blank" class="block w-full text-center rounded-lg bg-slate-100 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300">
                            Open Full Resolution
                        </a>
                    </div>
                @else
                    <div class="flex h-48 flex-col items-center justify-center rounded-xl border border-dashed border-slate-200 text-slate-400">
                        <svg class="h-8 w-8 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        <span class="text-xs">No image attached</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Deposit Modal --}}
    <div x-show="depositModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="depositModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Deposit Cheque into Bank</h3>
                <form action="{{ route('cheques.deposit', $cheque) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Destination Station Account *</label>
                        <select name="bank_account_id" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            @foreach ($bankAccounts as $acc)
                                <option value="{{ $acc->id }}">{{ $acc->bank?->short_name }} — {{ $acc->account_title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Deposit Date</label>
                        <input type="date" name="deposit_date" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="depositModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-sky-600 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-700">Confirm Deposit</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Bounce Modal --}}
    <div x-show="bounceModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="bounceModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-red-600">🚨 Record Cheque Bounce</h3>
                <form action="{{ route('cheques.bounce', $cheque) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <p class="text-xs text-slate-500">
                        Marking this cheque as bounced will <strong>reverse Rs. {{ number_format((float) $cheque->amount, 2) }} from {{ $cheque->partyName() }}'s ledger</strong>, apply bank charges, and alert the manager.
                    </p>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Bounce Reason *</label>
                        <select name="bounce_reason" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="Insufficient Funds / فنڈز ناکافی">Insufficient Funds / فنڈز ناکافی</option>
                            <option value="Signature Mismatch / دستخط کا فرق">Signature Mismatch / دستخط کا فرق</option>
                            <option value="Payment Stopped by Drawer / ادائیگی روک دی گئی">Payment Stopped by Drawer / ادائیگی روک دی گئی</option>
                            <option value="Account Closed / اکاؤنٹ بند ہے">Account Closed / اکاؤنٹ بند ہے</option>
                            <option value="Post-Dated Cheque / پیشگی تاریخ">Post-Dated Cheque / پیشگی تاریخ</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Bank Penalty Charges (Rs.)</label>
                        <input type="number" step="0.01" min="0" name="bank_charges" value="500.00" class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Bounce Date</label>
                        <input type="date" name="bounced_date" value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="bounceModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Cancel</button>
                        <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">Confirm Bounce</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Cancel Modal --}}
    <div x-show="cancelModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">
        <div class="flex min-h-screen items-center justify-center p-4">
            <div @click="cancelModal = false" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
            <div class="relative w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-xl dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-slate-900 dark:text-white">Cancel Cheque</h3>
                <form action="{{ route('cheques.cancel', $cheque) }}" method="POST" class="mt-4 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold uppercase text-slate-500">Reason for Cancellation *</label>
                        <input type="text" name="reason" required placeholder="e.g. Customer replaced with cash" class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    </div>
                    <div class="flex justify-end gap-2 pt-2">
                        <button type="button" @click="cancelModal = false" class="rounded-lg border border-slate-300 px-4 py-2 text-sm">Close</button>
                        <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-900">Confirm Cancellation</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
