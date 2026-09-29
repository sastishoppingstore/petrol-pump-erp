<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OpenShiftRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('shift.create');
    }

    public function rules(): array
    {
        return [
            'branch_id' => ['required', 'exists:branches,id'],
            'user_id' => ['required', 'exists:users,id'],
            'opening_cash' => ['required', 'numeric', 'min:0'],
            'opening_notes' => ['nullable', 'string', 'max:1000'],
            'nozzles' => ['required', 'array', 'min:1'],
            'nozzles.*.nozzle_id' => ['required', 'exists:nozzles,id'],
            'nozzles.*.opening_meter' => ['nullable', 'numeric', 'min:0'],
            'nozzles.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
