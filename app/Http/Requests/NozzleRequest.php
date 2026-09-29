<?php

namespace App\Http\Requests;

use App\Models\Tank;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class NozzleRequest extends FormRequest
{
    public function rules(): array
    {
        $id = $this->route('nozzle')?->id;
        $tankId = $this->input('tank_id');
        $dispenserId = $this->input('dispenser_id');

        return [
            'branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'dispenser_id' => ['required', 'integer', Rule::exists('dispensers', 'id')],
            'tank_id' => ['required', 'integer', Rule::exists('tanks', 'id')],
            'fuel_product_id' => ['required', 'integer', Rule::exists('fuel_products', 'id')],
            'nozzle_number' => [
                'required', 'string', 'max:20',
                Rule::unique('nozzles', 'nozzle_number')
                    ->where(fn ($q) => $q->where('dispenser_id', $dispenserId))
                    ->ignore($id),
            ],
            'opening_meter' => ['nullable', 'numeric', 'min:0', 'max:99999999999.999'],
            'meter_multiplier' => ['nullable', 'numeric', 'min:0.001'],
            'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE', 'MAINTENANCE'])],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->input('tank_id') || ! $this->input('fuel_product_id')) {
                return;
            }

            $tank = Tank::find($this->input('tank_id'));

            if (! $tank) {
                return;
            }

            // Nozzle fuel must equal the tank's fuel (spec section 6).
            if ((int) $tank->fuel_product_id !== (int) $this->input('fuel_product_id')) {
                $validator->errors()->add(
                    'fuel_product_id',
                    'Nozzle fuel must match the tank fuel. Tank "' . $tank->tank_number . '" holds '
                        . ($tank->fuelProduct?->name ?? 'a different fuel') . '.',
                );
            }

            // The tank and dispenser must belong to the selected branch.
            if ((int) $tank->branch_id !== (int) $this->input('branch_id')) {
                $validator->errors()->add('tank_id', 'The selected tank belongs to a different branch.');
            }

            $dispenserBranch = DB::table('dispensers')
                ->where('id', $this->input('dispenser_id'))
                ->value('branch_id');

            if ($dispenserBranch !== null && (int) $dispenserBranch !== (int) $this->input('branch_id')) {
                $validator->errors()->add('dispenser_id', 'The selected dispenser belongs to a different branch.');
            }
        });
    }
}
