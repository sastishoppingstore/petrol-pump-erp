@extends('layouts.app')

@section('title', 'New Journal Entry — نیا کھاتہ اندراج')

@section('content')
<div class="container-fluid py-4" x-data="{
    lines: [
        { account_id: '', debit: '', credit: '', memo: '' },
        { account_id: '', debit: '', credit: '', memo: '' }
    ],
    addLine() {
        this.lines.push({ account_id: '', debit: '', credit: '', memo: '' });
    },
    removeLine(idx) {
        if (this.lines.length > 2) {
            this.lines.splice(idx, 1);
        }
    },
    totalDebit() {
        return this.lines.reduce((sum, l) => sum + (parseFloat(l.debit) || 0), 0).toFixed(2);
    },
    totalCredit() {
        return this.lines.reduce((sum, l) => sum + (parseFloat(l.credit) || 0), 0).toFixed(2);
    },
    isBalanced() {
        return this.totalDebit() > 0 && this.totalDebit() === this.totalCredit();
    }
}">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="h3 fw-bold mb-1">Post Double-Entry Journal Entry</h2>
            <div class="text-muted" style="font-family: 'Jameel Noori Nastaleeq', Tahoma;">
                متوازن ڈبل انٹری روزنامچہ واؤچر تیار کریں
            </div>
        </div>
        <a href="{{ route('journals.index') }}" class="btn btn-outline-secondary">
            &larr; Back to Journals
        </a>
    </div>

    @if($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <ul class="mb-0">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <form method="POST" action="{{ route('journals.store') }}">
        @csrf
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Posting Date</label>
                        <input type="date" name="date" class="form-control" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label fw-bold">Narration / Description</label>
                        <input type="text" name="narration" class="form-control" placeholder="e.g. Monthly electricity bill payment / Owner capital injection" required value="{{ old('narration') }}">
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">Journal Entry Lines (کم از کم دو لائنیں)</h5>
                <button type="button" class="btn btn-sm btn-outline-primary" @click="addLine()">
                    + Add Line
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 35%;">Account (کھاتہ)</th>
                            <th style="width: 20%;">Debit (Rs.)</th>
                            <th style="width: 20%;">Credit (Rs.)</th>
                            <th style="width: 20%;">Memo / Reference</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr>
                                <td>
                                    <select :name="'lines[' + index + '][account_id]'" x-model="line.account_id" class="form-select form-select-sm" required>
                                        <option value="">-- Select Chart of Account --</option>
                                        @foreach($accounts as $acc)
                                            <option value="{{ $acc->id }}">
                                                {{ $acc->code }} - {{ $acc->name }} ({{ $acc->urdu_name }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <input type="number" step="0.01" :name="'lines[' + index + '][debit]'" x-model="line.debit"
                                           class="form-control form-control-sm text-end" placeholder="0.00"
                                           :disabled="line.credit > 0">
                                </td>
                                <td>
                                    <input type="number" step="0.01" :name="'lines[' + index + '][credit]'" x-model="line.credit"
                                           class="form-control form-control-sm text-end" placeholder="0.00"
                                           :disabled="line.debit > 0">
                                </td>
                                <td>
                                    <input type="text" :name="'lines[' + index + '][memo]'" x-model="line.memo"
                                           class="form-control form-control-sm" placeholder="Notes...">
                                </td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-sm btn-outline-danger" @click="removeLine(index)" :disabled="lines.length <= 2">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="table-light fw-bold fs-6">
                        <tr>
                            <td>TOTALS (میزان)</td>
                            <td class="text-end text-success" x-text="'Rs. ' + totalDebit()"></td>
                            <td class="text-end text-danger" x-text="'Rs. ' + totalCredit()"></td>
                            <td colspan="2">
                                <span class="badge" :class="isBalanced() ? 'bg-success' : 'bg-danger'"
                                      x-text="isBalanced() ? '✓ Balanced (برابر)' : '⚠ Unbalanced'">
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('journals.index') }}" class="btn btn-secondary px-4">Cancel</a>
            <button type="submit" class="btn btn-danger px-5 fw-bold" :disabled="!isBalanced()">
                ✓ Post Journal Entry
            </button>
        </div>
    </form>
</div>
@endsection
