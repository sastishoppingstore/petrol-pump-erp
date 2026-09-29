<?php

namespace App\Http\Requests;

use App\Models\ShiftCash;
use Illuminate\Foundation\Http\FormRequest;

class ShiftCashRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', 'string', 'in:'.implode(',', [
                ShiftCash::TYPE_FLOAT_ADDITION,
                ShiftCash::TYPE_DROP,
                ShiftCash::TYPE_HANDOVER,
                ShiftCash::TYPE_EXPENSE_PAYOUT,
            ])],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
