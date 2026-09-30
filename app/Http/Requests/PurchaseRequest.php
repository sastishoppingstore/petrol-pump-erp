<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'purchase_date' => ['required', 'date'],
            'tank_id' => ['required', 'exists:tanks,id'],
            'fuel_product_id' => ['required', 'exists:fuel_products,id'],
            'challan_number' => ['required', 'string', 'max:50'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'tanker_number' => ['required', 'string', 'max:30'],
            'driver_name' => ['required', 'string', 'max:100'],
            'volume_ordered' => ['required', 'numeric', 'min:0.001'],
            'volume_received' => ['required', 'numeric', 'min:0.001'],
            'dip_before' => ['nullable', 'numeric'],
            'dip_after' => ['nullable', 'numeric'],
            'purchase_rate' => ['required', 'numeric', 'min:0.01'],
            'ifem' => ['nullable', 'numeric', 'min:0'],
            'petroleum_levy' => ['nullable', 'numeric', 'min:0'],
            'freight_charges' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'other_charges' => ['nullable', 'numeric', 'min:0'],
            'density' => ['nullable', 'numeric'],
            'temperature' => ['nullable', 'numeric'],
            'bill_photo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
