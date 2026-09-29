<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    public function rules(): array
    {
        $user = $this->route('user');
        $userId = $user?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:150',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'employee_code' => [
                'nullable', 'string', 'max:30',
                Rule::unique('users', 'employee_code')->ignore($userId),
            ],
            'phone' => ['nullable', 'string', 'max:30'],

            // Required when creating, optional on edit (leave blank to keep current).
            'password' => [
                $userId ? 'nullable' : 'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
                Password::min(8),
            ],

            'status' => ['required', Rule::in([User::STATUS_ACTIVE, User::STATUS_DISABLED])],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['integer', Rule::exists('roles', 'id')],
            'branches' => ['nullable', 'array'],
            'branches.*' => ['integer', Rule::exists('branches', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'roles.required' => 'At least one role must be assigned.',
            'password.confirmed' => 'The password confirmation does not match.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('email')) {
            $this->merge(['email' => mb_strtolower(trim((string) $this->input('email')))]);
        }
    }
}
