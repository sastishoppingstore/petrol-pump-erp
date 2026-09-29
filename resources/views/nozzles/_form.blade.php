<div class="erp-card p-4">
    <div class="row g-3">
        <div class="col-md-4">
            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                <option value="">Select branch…</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $nozzle->branch_id) === (string) $branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="dispenser_id" class="form-label">Dispenser <span class="text-danger">*</span></label>
            <select id="dispenser_id" name="dispenser_id" class="form-select @error('dispenser_id') is-invalid @enderror" required>
                <option value="">Select dispenser…</option>
                @foreach ($dispensers as $dispenser)
                    <option value="{{ $dispenser->id }}" @selected((string) old('dispenser_id', $nozzle->dispenser_id) === (string) $dispenser->id)>
                        {{ $dispenser->dispenser_number }} @if($dispenser->name) — {{ $dispenser->name }} @endif
                    </option>
                @endforeach
            </select>
            @error('dispenser_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="tank_id" class="form-label">Tank <span class="text-danger">*</span></label>
            <select id="tank_id" name="tank_id" class="form-select @error('tank_id') is-invalid @enderror" required>
                <option value="">Select tank…</option>
                @foreach ($tanks as $tank)
                    <option value="{{ $tank->id }}" @selected((string) old('tank_id', $nozzle->tank_id) === (string) $tank->id)>
                        {{ $tank->displayName() }} — {{ $tank->fuelName() }}
                    </option>
                @endforeach
            </select>
            @error('tank_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="fuel_product_id" class="form-label">Fuel <span class="text-danger">*</span></label>
            <select id="fuel_product_id" name="fuel_product_id" class="form-select @error('fuel_product_id') is-invalid @enderror" required>
                <option value="">Select fuel…</option>
                @foreach ($fuels as $fuel)
                    <option value="{{ $fuel->id }}" @selected((string) old('fuel_product_id', $nozzle->fuel_product_id) === (string) $fuel->id)>
                        {{ $fuel->name }}
                    </option>
                @endforeach
            </select>
            @error('fuel_product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Must match the selected tank's fuel.</div>
        </div>

        <div class="col-md-3">
            <label for="nozzle_number" class="form-label">Nozzle number <span class="text-danger">*</span></label>
            <input type="text" id="nozzle_number" name="nozzle_number"
                   value="{{ old('nozzle_number', $nozzle->nozzle_number) }}"
                   class="form-control @error('nozzle_number') is-invalid @enderror" required maxlength="20">
            @error('nozzle_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="opening_meter" class="form-label">Opening meter</label>
            <input type="number" step="0.001" min="0" id="opening_meter" name="opening_meter"
                   value="{{ old('opening_meter', $nozzle->opening_meter) }}"
                   class="form-control @error('opening_meter') is-invalid @enderror"
                   @if ($nozzle->exists) disabled @endif>
            @if ($nozzle->exists)
                <div class="form-text">Opening meter is history and cannot be changed. Use meter correction.</div>
            @endif
            @error('opening_meter') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label for="meter_multiplier" class="form-label">Multiplier</label>
            <input type="number" step="0.001" min="0.001" id="meter_multiplier" name="meter_multiplier"
                   value="{{ old('meter_multiplier', $nozzle->meter_multiplier ?? '1') }}"
                   class="form-control @error('meter_multiplier') is-invalid @enderror">
            @error('meter_multiplier') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes', $nozzle->notes) }}</textarea>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('nozzles.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>
