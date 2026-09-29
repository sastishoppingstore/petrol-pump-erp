<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FuelProductRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('fuel')?->id;

        return [
            'code' => ['required', 'string', 'max:20', 'regex:/^[A-Za-z0-9\-_ ]+$/', Rule::unique('fuel_products', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:100'],
            'unit' => ['required', Rule::in(['LITRE', 'KG'])],
            'color' => ['nullable', 'string', 'max:20'],
            'selling_price' => ['required', 'numeric', 'min:0', 'max:99999999.99'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
        }
    }
}
