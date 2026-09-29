<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DispenserRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('dispenser')?->id;

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'dispenser_number' => [
                'required', 'string', 'max:30',
                Rule::unique('dispensers', 'dispenser_number')
                    ->where(fn ($q) => $q->where('branch_id', $this->input('branch_id')))
                    ->ignore($id),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE', 'MAINTENANCE'])],
        ];
    }
}
