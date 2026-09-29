<div class="erp-card p-4">
    <div class="row g-3">
        <div class="col-md-3">
            <label for="code" class="form-label">Code <span class="text-danger">*</span></label>
            <input type="text" id="code" name="code"
                   value="{{ old('code', $branch->code) }}"
                   class="form-control @error('code') is-invalid @enderror"
                   required maxlength="20" placeholder="BR-01">
            @error('code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label for="name" class="form-label">Branch name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name"
                   value="{{ old('name', $branch->name) }}"
                   class="form-control @error('name') is-invalid @enderror"
                   required maxlength="150">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select id="status" name="status"
                    class="form-select @error('status') is-invalid @enderror" required>
                @foreach (['ACTIVE', 'INACTIVE'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $branch->status) === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="address" class="form-label">Address</label>
            <input type="text" id="address" name="address"
                   value="{{ old('address', $branch->address) }}"
                   class="form-control @error('address') is-invalid @enderror" maxlength="500">
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="city" class="form-label">City</label>
            <input type="text" id="city" name="city" value="{{ old('city', $branch->city) }}"
                   class="form-control @error('city') is-invalid @enderror" maxlength="100">
            @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="state" class="form-label">State / Province</label>
            <input type="text" id="state" name="state" value="{{ old('state', $branch->state) }}"
                   class="form-control @error('state') is-invalid @enderror" maxlength="100">
            @error('state') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="country" class="form-label">Country</label>
            <input type="text" id="country" name="country" value="{{ old('country', $branch->country) }}"
                   class="form-control @error('country') is-invalid @enderror" maxlength="100">
            @error('country') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}"
                   class="form-control @error('phone') is-invalid @enderror" maxlength="30">
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $branch->email) }}"
                   class="form-control @error('email') is-invalid @enderror" maxlength="150">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="ntn_number" class="form-label">NTN number</label>
            <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number', $branch->ntn_number) }}"
                   class="form-control @error('ntn_number') is-invalid @enderror" maxlength="30">
            @error('ntn_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="strn_number" class="form-label">STRN number</label>
            <input type="text" id="strn_number" name="strn_number" value="{{ old('strn_number', $branch->strn_number) }}"
                   class="form-control @error('strn_number') is-invalid @enderror" maxlength="30">
            @error('strn_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="latitude" class="form-label">Latitude</label>
            <input type="number" step="0.0000001" id="latitude" name="latitude"
                   value="{{ old('latitude', $branch->latitude) }}"
                   class="form-control @error('latitude') is-invalid @enderror" min="-90" max="90">
            @error('latitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="longitude" class="form-label">Longitude</label>
            <input type="number" step="0.0000001" id="longitude" name="longitude"
                   value="{{ old('longitude', $branch->longitude) }}"
                   class="form-control @error('longitude') is-invalid @enderror" min="-180" max="180">
            @error('longitude') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>
