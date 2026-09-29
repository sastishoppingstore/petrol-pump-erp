<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CloseShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Permission check or shift ownership verified in controller / service
        return true;
    }

    public function rules(): array
    {
        return [
            'actual_cash' => ['required', 'numeric', 'min:0'],
            'card_total' => ['nullable', 'numeric', 'min:0'],
            'closing_notes' => ['nullable', 'string', 'max:1000'],
            'nozzles' => ['required', 'array', 'min:1'],
            'nozzles.*.nozzle_id' => ['required', 'exists:nozzles,id'],
            'nozzles.*.closing_meter' => ['required', 'numeric', 'min:0'],
        ];
    }
}
