<div class="glass-card mx-auto max-w-4xl p-6 sm:p-8">
    <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="field-3d">
            <label for="code">Code <span class="text-red-600">*</span></label>
            <input type="text" id="code" name="code" value="{{ old('code', $fuel->code) }}"
                   class="input-3d text-center font-mono font-bold" required maxlength="20" placeholder="PET">
            @error('code') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d sm:col-span-2">
            <label for="name">Name <span class="text-red-600">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $fuel->name) }}"
                   class="input-3d text-center font-bold" required maxlength="100">
            @error('name') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="unit">Unit <span class="text-red-600">*</span></label>
            <select id="unit" name="unit" class="input-3d" required>
                @foreach (['LITRE', 'KG'] as $u)
                    <option value="{{ $u }}" @selected(old('unit', $fuel->unit ?? 'LITRE') === $u)>{{ $u }}</option>
                @endforeach
            </select>
            @error('unit') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="status">Status <span class="text-red-600">*</span></label>
            <select id="status" name="status" class="input-3d" required>
                @foreach (['ACTIVE', 'INACTIVE'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $fuel->status ?? 'ACTIVE') === $s)>{{ ucfirst(strtolower($s)) }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="selling_price">Selling price <span class="text-red-600">*</span></label>
            <input type="number" step="0.01" min="0" id="selling_price" name="selling_price"
                   value="{{ old('selling_price', $fuel->selling_price) }}"
                   class="input-3d text-center font-mono font-bold" required>
            @error('selling_price') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="tax_rate">Tax rate %</label>
            <input type="number" step="0.01" min="0" max="100" id="tax_rate" name="tax_rate"
                   value="{{ old('tax_rate', $fuel->tax_rate) }}"
                   class="input-3d text-center font-mono">
            @error('tax_rate') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="minimum_stock">Minimum stock (litres)</label>
            <input type="number" step="0.001" min="0" id="minimum_stock" name="minimum_stock"
                   value="{{ old('minimum_stock', $fuel->minimum_stock) }}"
                   class="input-3d text-center font-mono">
            @error('minimum_stock') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="color">Colour</label>
            <input type="text" id="color" name="color" value="{{ old('color', $fuel->color) }}"
                   class="input-3d text-center font-mono" maxlength="20" placeholder="#0f2540">
            @error('color') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d sm:col-span-2 lg:col-span-4">
            <label for="description">Description</label>
            <textarea id="description" name="description" rows="2"
                      class="input-3d">{{ old('description', $fuel->description) }}</textarea>
            @error('description') <p class="mt-1 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    @if ($fuel->exists)
        <div class="alert alert-info mt-5">
            Changing the selling price records a new row in the price history and is written to the
            audit log. The previous price is never overwritten.
        </div>
    @endif

    <div class="mt-6 flex flex-wrap justify-center gap-3">
        <button type="submit" class="btn-3d btn-3d-primary px-8">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('fuels.index') }}" class="btn-3d btn-3d-ghost">Cancel</a>
    </div>
</div>
