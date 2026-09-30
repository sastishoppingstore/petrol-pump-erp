@extends('layouts.app')

@section('title', 'Banks')
@section('breadcrumb')
    <li class="text-slate-500">Banks</li>
@endsection

@section('content')
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold">Banks &amp; Accounts</h1>
            <p class="text-sm text-slate-500">
                {{ \App\Models\Bank::count() }} banks available. Add the accounts this station deposits into.
            </p>
        </div>
        <div class="flex gap-2">
            @can('cash.create')
                <a href="{{ route('bank-accounts.create') }}" class="rounded-md bg-navy-800 px-4 py-2 text-sm font-semibold text-white hover:bg-navy-900 dark:bg-navy-700">
                    Add Bank Account
                </a>
            @endcan
            @can('cash.view')
                <a href="{{ route('bank-deposits.index') }}" class="rounded-md border border-slate-300 px-4 py-2 text-sm dark:border-slate-700">
                    Deposit Report
                </a>
            @endcan
        </div>
    </div>

    {{-- ================= Station's accounts ================= --}}
    <div class="mb-6 rounded-lg border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
        <h2 class="border-b border-slate-200 px-4 py-3 text-base font-bold dark:border-slate-800">
            This station's bank accounts
        </h2>
        <div class="overflow-x-auto">
            <table class="mb-0 w-full text-sm">
                <thead class="bg-slate-50 text-xs uppercase dark:bg-slate-800">
                    <tr>
                        <th class="px-4 py-2 text-left">Bank</th>
                        <th class="px-4 py-2 text-left">Title</th>
                        <th class="px-4 py-2 text-left">Account No.</th>
                        <th class="px-4 py-2 text-left">Type</th>
                        <th class="tabular px-4 py-2 text-right">Current Balance</th>
                        <th class="px-4 py-2 text-center">Status</th>
                        <th class="px-4 py-2 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($accounts as $account)
                        <tr class="border-t border-slate-100 dark:border-slate-800">
                            <td class="px-4 py-2">{{ $account->bank?->name }}</td>
                            <td class="px-4 py-2">{{ $account->account_title }}</td>
                            <td class="px-4 py-2 font-mono text-xs">{{ $account->maskedAccountNumber() }}</td>
                            <td class="px-4 py-2 text-xs">{{ $account->account_type }}</td>
                            <td class="tabular px-4 py-2 text-right font-semibold">
                                {{ number_format((float) $account->currentBalance(), 2) }}
                            </td>
                            <td class="px-4 py-2 text-center">
                                <span @class([
                                    'rounded px-2 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $account->isActive(),
                                    'bg-slate-100 text-slate-600' => ! $account->isActive(),
                                ])>{{ $account->status }}</span>
                            </td>
                            <td class="px-4 py-2 text-right text-nowrap">
                                @can('cash.create')
                                    <a href="{{ route('bank-accounts.edit', $account) }}" class="text-xs text-navy-700 hover:underline dark:text-slate-300">Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                No bank accounts yet. Add one so deposits have somewhere to go.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3">{{ $accounts->links() }}</div>
    </div>

    {{-- ================= Bank reference list ================= --}}
    <h2 class="mb-3 text-lg font-bold">All Pakistani Banks</h2>

    @foreach ($banks as $group => $groupBanks)
        <div class="mb-5 rounded-lg border border-slate-200 bg-white shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h3 class="border-b border-slate-200 px-4 py-2 text-sm font-bold text-slate-600 dark:border-slate-800 dark:text-slate-300">
                {{ $group }} <span class="font-normal text-slate-400">({{ $groupBanks->count() }})</span>
            </h3>
            <div class="grid gap-x-6 gap-y-1 p-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($groupBanks as $bank)
                    <div class="flex items-center justify-between border-b border-slate-100 py-1 text-sm dark:border-slate-800">
                        <span>{{ $bank->name }}</span>
                        <span class="ml-2 shrink-0 rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] text-slate-600 dark:bg-slate-700 dark:text-slate-300">
                            {{ $bank->short_name }}
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
@endsection
