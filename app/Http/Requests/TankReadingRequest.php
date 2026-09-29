<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TankReadingRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'tank_id' => ['required', 'integer', Rule::exists('tanks', 'id')],
            'physical_quantity' => ['required', 'numeric', 'min:0', 'max:999999999.999'],
            'reading_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
