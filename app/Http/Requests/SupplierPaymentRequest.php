<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SupplierPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'in:CASH,BANK_TRANSFER,CHEQUE'],
            'bank_account_id' => ['nullable', 'required_if:payment_method,BANK_TRANSFER', 'exists:bank_accounts,id'],
            'cheque_number' => ['nullable', 'required_if:payment_method,CHEQUE', 'string', 'max:50'],
            'cheque_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
