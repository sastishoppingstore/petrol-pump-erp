<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MeterCorrectionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'new_meter' => ['required', 'numeric', 'min:0', 'max:99999999999.999'],
            'reason' => ['required', 'string', 'min:3', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required for a meter correction.',
        ];
    }
}
