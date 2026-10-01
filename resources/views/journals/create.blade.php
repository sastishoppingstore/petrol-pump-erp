@extends('layouts.app')

@section('title', __('finance.journals.create_title'))

@section('content')
<div x-data="{
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
    <div class="page-head">
        <h1>{{ __('finance.journals.create_heading') }}</h1>
        <p style="font-family: 'Jameel Noori Nastaleeq', Tahoma;">
            {{ __('finance.journals.create_sub') }}
        </p>
        <div class="page-actions">
            <a href="{{ route('journals.index') }}" class="btn-3d btn-3d-ghost btn-3d-sm">
                {{ __('finance.journals.back_journals') }}
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="glass-card mb-4 p-4 text-center text-sm text-vital-primary" role="alert">
            <ul class="space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('journals.store') }}">
        @csrf
        <div class="glass-card card-3d mb-4 p-5">
            <div class="grid gap-4 sm:grid-cols-4">
                <div class="field-3d">
                    <label class="mb-1 block text-center text-sm font-bold">{{ __('finance.journals.posting_date') }}</label>
                    <input type="date" name="date" class="input-3d w-full text-center" value="{{ old('date', now()->format('Y-m-d')) }}" required>
                </div>
                <div class="field-3d sm:col-span-3">
                    <label class="mb-1 block text-center text-sm font-bold">{{ __('finance.journals.narration_label') }}</label>
                    <input type="text" name="narration" class="input-3d w-full text-center" placeholder="{{ __('finance.journals.ph_narration') }}" required value="{{ old('narration') }}">
                </div>
            </div>
        </div>

        <div class="glass-card mb-4 overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200/70 px-5 py-3 dark:border-slate-700/60">
                <h2 class="text-base font-bold">{{ __('finance.journals.lines_heading') }}</h2>
                <button type="button" class="btn-3d btn-3d-navy btn-3d-sm" @click="addLine()">
                    {{ __('finance.journals.add_line') }}
                </button>
            </div>
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 35%;">{{ __('finance.journals.account_col') }}</th>
                            <th style="width: 20%;">{{ __('finance.journals.debit_rs') }}</th>
                            <th style="width: 20%;">{{ __('finance.journals.credit_rs') }}</th>
                            <th style="width: 20%;">{{ __('finance.journals.memo_reference') }}</th>
                            <th style="width: 5%;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="(line, index) in lines" :key="index">
                            <tr>
                                <td>
                                    <div class="field-3d">
                                        <select :name="'lines[' + index + '][account_id]'" x-model="line.account_id" class="input-3d w-full text-center text-sm" required>
                                            <option value="">{{ __('finance.journals.select_account') }}</option>
                                            @foreach($accounts as $acc)
                                                <option value="{{ $acc->id }}">
                                                    {{ $acc->code }} - {{ $acc->name }} ({{ $acc->urdu_name }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </td>
                                <td>
                                    <div class="field-3d">
                                        <input type="number" step="0.01" :name="'lines[' + index + '][debit]'" x-model="line.debit"
                                               class="input-3d tabular w-full text-center text-sm" placeholder="0.00"
                                               :disabled="line.credit > 0">
                                    </div>
                                </td>
                                <td>
                                    <div class="field-3d">
                                        <input type="number" step="0.01" :name="'lines[' + index + '][credit]'" x-model="line.credit"
                                               class="input-3d tabular w-full text-center text-sm" placeholder="0.00"
                                               :disabled="line.debit > 0">
                                    </div>
                                </td>
                                <td>
                                    <div class="field-3d">
                                        <input type="text" :name="'lines[' + index + '][memo]'" x-model="line.memo"
                                               class="input-3d w-full text-center text-sm" placeholder="{{ __('finance.journals.ph_notes') }}">
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="btn-3d btn-3d-primary btn-3d-sm" @click="removeLine(index)" :disabled="lines.length <= 2">
                                        &times;
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="border-t-2 border-slate-300 bg-slate-50 font-bold dark:border-slate-700 dark:bg-slate-800">
                        <tr>
                            <td>{{ __('finance.journals.totals') }}</td>
                            <td class="tabular text-emerald-600" x-text="'Rs. ' + totalDebit()"></td>
                            <td class="tabular text-vital-primary" x-text="'Rs. ' + totalCredit()"></td>
                            <td colspan="2">
                                <span class="rounded-full px-3 py-1 text-xs font-bold text-white" :class="isBalanced() ? 'bg-emerald-600' : 'bg-vital-primary'"
                                      x-text="isBalanced() ? '✓ Balanced (برابر)' : '⚠ Unbalanced'">
                                </span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="flex flex-wrap justify-center gap-2">
            <a href="{{ route('journals.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
            <button type="submit" class="btn-3d btn-3d-primary disabled:opacity-50" :disabled="!isBalanced()">
                {{ __('finance.journals.post_entry') }}
            </button>
        </div>
    </form>
</div>
@endsection
