@extends('layouts.app')

@section('title', 'General Ledger Journal Entries — روزنامچہ کھاتہ')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h2 class="h3 fw-bold mb-1">General Ledger & Day Book</h2>
            <div class="text-muted" style="font-family: 'Jameel Noori Nastaleeq', Tahoma; font-size: 1.1rem;">
                ڈبل انٹری جنرل لیجر اور روزنامچہ اندراجات
            </div>
        </div>
        <div class="btn-group">
            <a href="{{ route('journals.create') }}" class="btn btn-danger">
                + New Journal Entry (نیا اندراج)
            </a>
            <a href="{{ route('journals.trial-balance') }}" class="btn btn-outline-dark">
                ⚖️ Trial Balance (میزان نامہ)
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Entry #</th>
                            <th>Date</th>
                            <th>Narration</th>
                            <th>Reference</th>
                            <th>Accounts & Postings (Debits / Credits)</th>
                            <th class="text-center">Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($entries as $e)
                            <tr>
                                <td class="font-monospace fw-bold">{{ $e->entry_number }}</td>
                                <td>{{ $e->date->format('d M Y') }}</td>
                                <td>{{ $e->narration }}</td>
                                <td><small class="text-muted">{{ class_basename($e->reference_type ?? '') }} #{{ $e->reference_id }}</small></td>
                                <td>
                                    <div class="small">
                                        @foreach($e->lines as $l)
                                            <div class="d-flex justify-content-between text-nowrap gap-3">
                                                <span>{{ $l->account->name }}</span>
                                                <span>
                                                    @if(! \App\Support\Money::isZero($l->debit))
                                                        <span class="text-success fw-bold">Dr. {{ \App\Support\PakistaniCurrency::format($l->debit) }}</span>
                                                    @endif
                                                    @if(! \App\Support\Money::isZero($l->credit))
                                                        <span class="text-danger fw-bold">Cr. {{ \App\Support\PakistaniCurrency::format($l->credit) }}</span>
                                                    @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-center">
                                    <span class="badge {{ $e->isPosted() ? 'bg-success' : 'bg-danger' }}">
                                        {{ $e->status }}
                                    </span>
                                </td>
                                <td class="text-end">
                                    @if($e->isPosted())
                                        <form method="POST" action="{{ route('journals.void', $e->id) }}" class="d-inline" onsubmit="return confirm('Void entry {{ $e->entry_number }} and post reversal?')">
                                            @csrf
                                            <input type="hidden" name="reason" value="Manager void and reversal">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                Void & Revoke
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted py-4">No journal entries recorded.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            {{ $entries->links() }}
        </div>
    </div>
</div>
@endsection
