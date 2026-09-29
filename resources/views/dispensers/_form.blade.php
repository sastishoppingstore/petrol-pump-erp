<div class="erp-card p-4">
    <div class="row g-3">
        <div class="col-md-4">
            <label for="branch_id" class="form-label">Branch <span class="text-danger">*</span></label>
            <select id="branch_id" name="branch_id" class="form-select @error('branch_id') is-invalid @enderror" required>
                <option value="">Select branch…</option>
                @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}" @selected((string) old('branch_id', $dispenser->branch_id) === (string) $branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
            @error('branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="dispenser_number" class="form-label">Dispenser number <span class="text-danger">*</span></label>
            <input type="text" id="dispenser_number" name="dispenser_number"
                   value="{{ old('dispenser_number', $dispenser->dispenser_number) }}"
                   class="form-control @error('dispenser_number') is-invalid @enderror" required maxlength="30">
            @error('dispenser_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach (['ACTIVE', 'INACTIVE', 'MAINTENANCE'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $dispenser->status ?? 'ACTIVE') === $s)>{{ ucfirst(strtolower($s)) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label for="name" class="form-label">Name</label>
            <input type="text" id="name" name="name" value="{{ old('name', $dispenser->name) }}"
                   class="form-control @error('name') is-invalid @enderror" maxlength="100">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="model" class="form-label">Model</label>
            <input type="text" id="model" name="model" value="{{ old('model', $dispenser->model) }}"
                   class="form-control @error('model') is-invalid @enderror" maxlength="100">
            @error('model') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="serial_number" class="form-label">Serial number</label>
            <input type="text" id="serial_number" name="serial_number"
                   value="{{ old('serial_number', $dispenser->serial_number) }}"
                   class="form-control @error('serial_number') is-invalid @enderror" maxlength="100">
            @error('serial_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="notes" class="form-label">Notes</label>
            <textarea id="notes" name="notes" rows="2" class="form-control">{{ old('notes', $dispenser->notes) }}</textarea>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('dispensers.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>
