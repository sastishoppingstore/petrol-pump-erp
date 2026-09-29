<div class="erp-card p-4">
    <div class="row g-3">
        <div class="col-md-4">
            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                <option value="">Select branch…</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $tank->branch_id) === (string) $branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="fuel_product_id" class="form-label">Fuel <span class="text-danger">*</span></label>
            <select id="fuel_product_id" name="fuel_product_id" class="form-select @error('fuel_product_id') is-invalid @enderror" required>
                <option value="">Select fuel…</option>
                @foreach ($fuels as $fuel)
                    <option value="{{ $fuel->id }}" @selected((string) old('fuel_product_id', $tank->fuel_product_id) === (string) $fuel->id)>
                        {{ $fuel->name }}
                    </option>
                @endforeach
            </select>
            @error('fuel_product_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="tank_number" class="form-label">Tank number <span class="text-danger">*</span></label>
            <input type="text" id="tank_number" name="tank_number" value="{{ old('tank_number', $tank->tank_number) }}"
                   class="form-control @error('tank_number') is-invalid @enderror" required maxlength="30">
            @error('tank_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-8">
            <label for="name" class="form-label">Description</label>
            <input type="text" id="name" name="name" value="{{ old('name', $tank->name) }}"
                   class="form-control @error('name') is-invalid @enderror" maxlength="100" placeholder="Underground tank A">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach (['ACTIVE', 'INACTIVE', 'MAINTENANCE'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $tank->status ?? 'ACTIVE') === $s)>{{ ucfirst(strtolower($s)) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="capacity" class="form-label">Capacity (L) <span class="text-danger">*</span></label>
            <input type="number" step="0.001" min="0.001" id="capacity" name="capacity"
                   value="{{ old('capacity', $tank->capacity) }}"
                   class="form-control @error('capacity') is-invalid @enderror" required>
            @error('capacity') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="opening_stock" class="form-label">Opening stock (L)</label>
            <input type="number" step="0.001" min="0" id="opening_stock" name="opening_stock"
                   value="{{ old('opening_stock', $tank->opening_stock) }}"
                   class="form-control @error('opening_stock') is-invalid @enderror"
                   @if ($tank->exists) disabled @endif>
            @if ($tank->exists)<div class="form-text">Opening stock is history and cannot be changed.</div>@endif
            @error('opening_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="low_stock_threshold" class="form-label">Low-stock alert at (L)</label>
            <input type="number" step="0.001" min="0" id="low_stock_threshold" name="low_stock_threshold"
                   value="{{ old('low_stock_threshold', $tank->low_stock_threshold) }}"
                   class="form-control @error('low_stock_threshold') is-invalid @enderror">
            @error('low_stock_threshold') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="installation_date" class="form-label">Installed on</label>
            <input type="date" id="installation_date" name="installation_date"
                   value="{{ old('installation_date', $tank->installation_date?->toDateString()) }}"
                   class="form-control">
        </div>

        <div class="col-md-6">
            <label for="min_level" class="form-label">Minimum level (L)</label>
            <input type="number" step="0.001" min="0" id="min_level" name="min_level"
                   value="{{ old('min_level', $tank->min_level) }}"
                   class="form-control @error('min_level') is-invalid @enderror">
            @error('min_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label for="max_level" class="form-label">Maximum level (L)</label>
            <input type="number" step="0.001" min="0" id="max_level" name="max_level"
                   value="{{ old('max_level', $tank->max_level) }}"
                   class="form-control @error('max_level') is-invalid @enderror">
            @error('max_level') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes', $tank->notes) }}</textarea>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('tanks.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>
