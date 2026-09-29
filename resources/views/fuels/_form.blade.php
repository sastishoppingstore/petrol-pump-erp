<div class="erp-card p-4">
    <div class="row g-3">
        <div class="col-md-3">
            <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
            <input type="text" id="code" name="code" value="{{ old('code', $fuel->code) }}"
                   class="form-control @error('code') is-invalid @enderror" required maxlength="20" placeholder="PET">
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-5">
            <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $fuel->name) }}"
                   class="form-control @error('name') is-invalid @enderror" required maxlength="100">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label for="unit" class="form-label">Unit <span class="text-danger">*</span></label>
            <select id="unit" name="unit" class="form-select @error('unit') is-invalid @enderror" required>
                @foreach (['LITRE', 'KG'] as $u)
                    <option value="{{ $u }}" @selected(old('unit', $fuel->unit ?? 'LITRE') === $u)>{{ $u }}</option>
                @endforeach
            </select>
            @error('unit') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-2">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach (['ACTIVE', 'INACTIVE'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $fuel->status ?? 'ACTIVE') === $s)>{{ ucfirst(strtolower($s)) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="selling_price" class="form-label">Selling price <span class="text-danger">*</span></label>
            <input type="number" step="0.01" min="0" id="selling_price" name="selling_price"
                   value="{{ old('selling_price', $fuel->selling_price) }}"
                   class="form-control @error('selling_price') is-invalid @enderror" required>
            @error('selling_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="tax_rate" class="form-label">Tax rate %</label>
            <input type="number" step="0.01" min="0" max="100" id="tax_rate" name="tax_rate"
                   value="{{ old('tax_rate', $fuel->tax_rate) }}"
                   class="form-control @error('tax_rate') is-invalid @enderror">
            @error('tax_rate') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="minimum_stock" class="form-label">Minimum stock (litres)</label>
            <input type="number" step="0.001" min="0" id="minimum_stock" name="minimum_stock"
                   value="{{ old('minimum_stock', $fuel->minimum_stock) }}"
                   class="form-control @error('minimum_stock') is-invalid @enderror">
            @error('minimum_stock') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="color" class="form-label">Colour</label>
            <input type="text" id="color" name="color" value="{{ old('color', $fuel->color) }}"
                   class="form-control @error('color') is-invalid @enderror" maxlength="20" placeholder="#0f2540">
            @error('color') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="2"
                      class="form-control @error('description') is-invalid @enderror">{{ old('description', $fuel->description) }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    @if ($fuel->exists)
        <div class="alert alert-info mt-3 py-2 small">
            Changing the selling price records a new row in the price history and is written to the
            audit log. The previous price is never overwritten.
        </div>
    @endif

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('fuels.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>
