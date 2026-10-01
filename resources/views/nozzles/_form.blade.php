<div class="glass-card mx-auto max-w-4xl p-6 sm:p-8">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
        <div class="field-3d">
            <label for="branch_id">{{ __('forecourt.common.branch') }} <span class="text-red-600">*</span></label>
            <select id="branch_id" name="branch_id" class="input-3d" required>
                <option value="">{{ __('forecourt.common.select_branch') }}</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $nozzle->branch_id) === (string) $branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="dispenser_id">{{ __('forecourt.common.dispenser') }} <span class="text-red-600">*</span></label>
            <select id="dispenser_id" name="dispenser_id" class="input-3d" required>
                <option value="">{{ __('forecourt.common.select_dispenser') }}</option>
                @foreach ($dispensers as $dispenser)
                    <option value="{{ $dispenser->id }}" @selected((string) old('dispenser_id', $nozzle->dispenser_id) === (string) $dispenser->id)>
                        {{ $dispenser->dispenser_number }} @if($dispenser->name) — {{ $dispenser->name }} @endif
                    </option>
                @endforeach
            </select>
            @error('dispenser_id') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="tank_id">{{ __('forecourt.common.tank') }} <span class="text-red-600">*</span></label>
            <select id="tank_id" name="tank_id" class="input-3d" required>
                <option value="">{{ __('forecourt.common.select_tank') }}</option>
                @foreach ($tanks as $tank)
                    <option value="{{ $tank->id }}" @selected((string) old('tank_id', $nozzle->tank_id) === (string) $tank->id)>
                        {{ $tank->displayName() }} — {{ $tank->fuelName() }}
                    </option>
                @endforeach
            </select>
            @error('tank_id') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="fuel_product_id">{{ __('forecourt.common.fuel') }} <span class="text-red-600">*</span></label>
            <select id="fuel_product_id" name="fuel_product_id" class="input-3d" required>
                <option value="">{{ __('forecourt.common.select_fuel') }}</option>
                @foreach ($fuels as $fuel)
                    <option value="{{ $fuel->id }}" @selected((string) old('fuel_product_id', $nozzle->fuel_product_id) === (string) $fuel->id)>
                        {{ $fuel->name }}
                    </option>
                @endforeach
            </select>
            @error('fuel_product_id') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1 text-center text-xs text-slate-400">{{ __('forecourt.nozzles.form.fuel_help') }}</p>
        </div>

        <div class="field-3d">
            <label for="nozzle_number">{{ __('forecourt.nozzles.form.nozzle_number') }} <span class="text-red-600">*</span></label>
            <input type="text" id="nozzle_number" name="nozzle_number"
                   value="{{ old('nozzle_number', $nozzle->nozzle_number) }}"
                   class="input-3d text-center font-mono font-bold" required maxlength="20">
            @error('nozzle_number') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="opening_meter">{{ __('forecourt.common.opening_meter') }}</label>
            <input type="number" step="0.001" min="0" id="opening_meter" name="opening_meter"
                   value="{{ old('opening_meter', $nozzle->opening_meter) }}"
                   class="input-3d text-center font-mono"
                   @if ($nozzle->exists) disabled @endif>
            @if ($nozzle->exists)
                <p class="mt-1 text-center text-xs text-slate-400">{{ __('forecourt.nozzles.form.opening_note') }}</p>
            @endif
            @error('opening_meter') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="meter_multiplier">{{ __('forecourt.nozzles.form.multiplier') }}</label>
            <input type="number" step="0.001" min="0.001" id="meter_multiplier" name="meter_multiplier"
                   value="{{ old('meter_multiplier', $nozzle->meter_multiplier ?? '1') }}"
                   class="input-3d text-center font-mono">
            @error('meter_multiplier') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d sm:col-span-2 lg:col-span-3">
            <label for="notes">{{ __('forecourt.common.notes') }}</label>
            <textarea id="notes" name="notes" rows="2" class="input-3d">{{ old('notes', $nozzle->notes) }}</textarea>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap justify-center gap-3">
        <button type="submit" class="btn-3d btn-3d-primary px-8">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('nozzles.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
    </div>
</div>
