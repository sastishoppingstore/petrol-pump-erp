<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customerId = $this->route('customer') instanceof Customer
            ? $this->route('customer')->id
            : $this->route('customer');

        return [
            'code' => [
                'required',
                'string',
                'max:30',
                Rule::unique('customers', 'code')->ignore($customerId),
            ],
            'name' => ['required', 'string', 'max:150'],
            'phone' => [
                'required',
                'string',
                'regex:/^((\+92)|(0092)|(92)|0)?3[0-9]{2}-?[0-9]{7}$/',
            ],
            'cnic' => [
                'nullable',
                'string',
                'regex:/^([0-9]{5}-[0-9]{7}-[0-9]{1}|[0-9]{13})$/',
            ],
            'email' => ['nullable', 'email', 'max:150'],
            'address' => ['nullable', 'string', 'max:500'],
            'ntn_number' => ['nullable', 'string', 'max:30'],
            'is_tax_liable' => ['nullable', 'boolean'],
            'credit_limit' => ['required', 'numeric', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number must be a valid Pakistani mobile number (e.g., 0300-1234567).',
            'cnic.regex' => 'The CNIC must be 13 digits in Pakistani format (e.g., 35201-1234567-1).',
        ];
    }
}
