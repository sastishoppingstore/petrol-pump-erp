@extends('layouts.app')

@section('title', 'General Ledger Journal Entries — روزنامچہ کھاتہ')

@section('content')
<div class="space-y-6">
    <div class="page-head">
        <h1>General Ledger &amp; Day Book</h1>
        <p style="font-family: 'Jameel Noori Nastaleeq', Tahoma; font-size: 1.1rem;">
            ڈبل انٹری جنرل لیجر اور روزنامچہ اندراجات
        </p>
        <div class="page-actions">
            <a href="{{ route('journals.create') }}" class="btn-3d btn-3d-primary">
                + New Journal Entry (نیا اندراج)
            </a>
            <a href="{{ route('journals.trial-balance') }}" class="btn-3d btn-3d-navy">
                ⚖️ Trial Balance (میزان نامہ)
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="glass-card p-4 text-center text-sm font-semibold text-emerald-700 dark:text-emerald-300" role="alert">
            {{ session('success') }}
        </div>
    @endif

    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Entry #</th>
                        <th>Date</th>
                        <th>Narration</th>
                        <th>Reference</th>
                        <th>Accounts &amp; Postings (Debits / Credits)</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $e)
                        <tr>
                            <td class="tabular font-mono font-bold">{{ $e->entry_number }}</td>
                            <td class="whitespace-nowrap">{{ $e->date->format('d M Y') }}</td>
                            <td>{{ $e->narration }}</td>
                            <td><small class="text-slate-500">{{ class_basename($e->reference_type ?? '') }} #{{ $e->reference_id }}</small></td>
                            <td>
                                <div class="text-xs">
                                    @foreach($e->lines as $l)
                                        <div class="flex items-center justify-between gap-3 whitespace-nowrap">
                                            <span>{{ $l->account->name }}</span>
                                            <span class="tabular">
                                                @if(! \App\Support\Money::isZero($l->debit))
                                                    <span class="font-bold text-emerald-600">Dr. {{ \App\Support\PakistaniCurrency::format($l->debit) }}</span>
                                                @endif
                                                @if(! \App\Support\Money::isZero($l->credit))
                                                    <span class="font-bold text-vital-primary">Cr. {{ \App\Support\PakistaniCurrency::format($l->credit) }}</span>
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </td>
                            <td>
                                <span class="pill-status {{ $e->isPosted() ? 'pill-active' : 'pill-inactive' }}"><span class="dot"></span>{{ $e->status }}</span>
                            </td>
                            <td class="whitespace-nowrap">
                                @if($e->isPosted())
                                    <form method="POST" action="{{ route('journals.void', $e->id) }}" class="inline" onsubmit="return confirm('Void entry {{ $e->entry_number }} and post reversal?')">
                                        @csrf
                                        <input type="hidden" name="reason" value="Manager void and reversal">
                                        <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">
                                            Void &amp; Revoke
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">No journal entries recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-4 py-3 dark:border-slate-800">
            {{ $entries->links() }}
        </div>
    </div>

    <a href="{{ route('journals.create') }}" class="fab-3d" title="New journal entry">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    </a>
</div>
@endsection
