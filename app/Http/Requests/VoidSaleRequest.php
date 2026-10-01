<?php

namespace App\Http\Requests;

use App\Support\PermissionList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class VoidSaleRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'as_refund' => ['nullable', 'boolean'],
            'manager_pin' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'A reason is required to void or refund a sale.',
        ];
    }

    public function authorize(): bool
    {
        $permission = $this->boolean('as_refund')
            ? PermissionList::SALES_REFUND
            : PermissionList::SALES_VOID;

        // Authorisation is enforced by the middleware as well; this keeps the
        // Form Request self-contained.
        if (! $this->user() || ! $this->user()->hasPermission($permission)) {
            throw ValidationException::withMessages([
                'reason' => 'You do not have permission to perform this action.',
            ]);
        }

        return true;
    }
}
