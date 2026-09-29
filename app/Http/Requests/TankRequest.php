<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TankRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('tank')?->id;

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'fuel_product_id' => ['required', 'integer', Rule::exists('fuel_products', 'id')],
            'tank_number' => [
                'required', 'string', 'max:30',
                Rule::unique('tanks', 'tank_number')
                    ->where(fn ($q) => $q->where('branch_id', $this->input('branch_id')))
                    ->ignore($id),
            ],
            'name' => ['nullable', 'string', 'max:100'],
            // Litres: DECIMAL(12,3). Never a float field.
            'capacity' => ['required', 'numeric', 'min:0.001', 'max:999999999.999'],
            'min_level' => ['nullable', 'numeric', 'min:0'],
            'max_level' => ['nullable', 'numeric', 'min:0'],
            'opening_stock' => ['nullable', 'numeric', 'min:0'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
            'installation_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE', 'MAINTENANCE'])],
        ];
    }

    public function messages(): array
    {
        return [
            'capacity.min' => 'Tank capacity must be greater than zero.',
        ];
    }
}
