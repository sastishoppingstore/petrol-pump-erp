<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FuelPriceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'fuel_product_id' => ['required', 'integer', 'exists:fuel_products,id'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'effective_from' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
