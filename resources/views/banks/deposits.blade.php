@extends('layouts.app')

@section('title', 'Bank Deposits')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('banks.index') }}">Banks</a></li>
    <li class="text-slate-500">Deposits</li>
@endsection

@section('content')
    <h1 class="mb-4 text-xl font-bold">Bank Deposit Report</h1>

    <form method="GET" action="{{ route('bank-deposits.index') }}" class="mb-4 rounded-lg border border-slate-200 bg-white p-4 shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="grid gap-3 sm:grid-cols-4">
            <div>
                <label for="bank_account_id" class="mb-1 block text-xs font-medium">Account</label>
                <select id="bank_account_id" name="bank_account_id" class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    <option value="">All accounts</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}" @selected((string) request('bank_account_id') === (string) $account->id)>
                            {{ $account->bank?->name }} — {{ $account->maskedAccountNumber() }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="from" class="mb-1 block text-xs font-medium">From</label>
                <input type="date" id="from" name="from" value="{{ request('from') }}"
                       class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div>
                <label for="to" class="mb-1 block text-xs font-medium">To</label>
                <input type="date" id="to" name="to" value="{{ request('to') }}"
                       class="w-full rounded-md border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm dark:border-slate-700">Filter</button>
            </div>
        </div>
    </form>

    <div class="rounded-lg border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <div class="overflow-x-auto">
            <table class="mb-0 w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-2 text-left">When</th>
                        <th class="px-4 py-2 text-left">Bank</th>
                        <th class="px-4 py-2 text-left">Reference</th>
                        <th class="px-4 py-2 text-right">Amount</th>
                        <th class="px-4 py-2 text-right">Balance After</th>
                        <th class="px-4 py-2 text-left">Method</th>
                        <th class="px-4 py-2 text-left">Shift</th>
                        <th class="px-4 py-2 text-left">By</th>
                        <th class="px-4 py-2 text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($deposits as $d)
                        <tr class="border-t border-slate-100 dark:border-slate-800">
                            <td class="whitespace-nowrap px-4 py-2 text-xs">{{ $d->deposited_at?->format('d M Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $d->bank_name }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $d->reference_number }}</td>
                            <td class="tabular px-4 py-2 text-right font-semibold">{{ number_format((float) $d->amount, 2) }}</td>
                            <td class="tabular px-4 py-2 text-right text-slate-500">{{ number_format((float) $d->balance_after, 2) }}</td>
                            <td class="px-4 py-2 text-xs">{{ $d->deposit_type }}</td>
                            <td class="px-4 py-2 text-xs">{{ $d->shift?->shift_number ?? '—' }}</td>
                            <td class="px-4 py-2 text-xs">{{ $d->depositor?->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-center">
                                <span @class([
                                    'rounded px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $d->status === 'COMPLETED',
                                    'bg-red-100 text-red-800' => $d->status === 'REVERSED',
                                ])>{{ $d->status }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-slate-500">No deposits in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $deposits->links() }}</div>
    </div>
@endsection
