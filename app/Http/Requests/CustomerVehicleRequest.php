<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerVehicleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'registration_number' => ['required', 'string', 'max:30'],
            'driver_name' => ['nullable', 'string', 'max:100'],
            'make' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'colour' => ['nullable', 'string', 'max:50'],
            'type' => ['required', 'string', 'in:CAR,TRUCK,BUS,PICKUP,VAN,MOTORCYCLE,TRACTOR,OTHER'],
            'tank_capacity' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'in:ACTIVE,INACTIVE'],
        ];
    }
}
