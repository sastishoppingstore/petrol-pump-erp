<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function rules(): array
    {
        $roleId = $this->route('role')?->id;

        return [
            'name' => [
                'required', 'string', 'max:100',
                'regex:/^[A-Z][A-Z0-9_]*$/',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'label' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::exists('permissions', 'name')],
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => 'Role name must be UPPERCASE with letters, numbers and underscores only (e.g. STORE_MANAGER).',
        ];
    }
}
