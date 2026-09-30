<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankAccountRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('bank_account')?->id;

        return [
            'bank_id' => ['required', 'integer', Rule::exists('banks', 'id')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'account_title' => ['required', 'string', 'max:150'],
            'account_number' => [
                'required', 'string', 'max:30',
                Rule::unique('bank_accounts', 'account_number')
                    ->where(fn ($q) => $q->where('bank_id', $this->input('bank_id')))
                    ->ignore($id),
            ],
            'iban' => ['nullable', 'string', 'max:34', new \App\Rules\PakistaniIban()],
            'account_type' => ['required', Rule::in(['CURRENT', 'SAVINGS'])],
            'opening_balance' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
