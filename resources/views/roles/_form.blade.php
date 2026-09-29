<div class="erp-card p-4">
    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <label for="name" class="form-label">Role name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}"
                   class="form-control @error('name') is-invalid @enderror"
                   required maxlength="100" placeholder="STORE_MANAGER"
                   @if ($isBuiltIn ?? false) readonly @endif>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">Uppercase, letters/numbers/underscore only.</div>
        </div>

        <div class="col-md-5">
            <label for="label" class="form-label">Display label <span class="text-danger">*</span></label>
            <input type="text" id="label" name="label" value="{{ old('label', $role->label) }}"
                   class="form-control @error('label') is-invalid @enderror" required maxlength="150">
            @error('label') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-3">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach (['ACTIVE', 'INACTIVE'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $role->status ?? 'ACTIVE') === $option)>{{ ucfirst(strtolower($option)) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" rows="2"
                      class="form-control @error('description') is-invalid @enderror"
                      maxlength="1000">{{ old('description', $role->description) }}</textarea>
            @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    <hr>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <h2 class="h6 mb-0">Permissions</h2>
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-outline-secondary" id="erp-perm-all">Select all</button>
            <button type="button" class="btn btn-outline-secondary" id="erp-perm-none">Clear all</button>
        </div>
    </div>

    @if ($role->is_super_admin)
        <div class="alert alert-warning py-2">
            This is the Administrator role. It always holds every permission and the matrix is locked.
        </div>
    @endif

    <div class="row g-3">
        @foreach ($matrix as $module => $permissions)
            <div class="col-md-6 col-xl-4">
                <div class="erp-card p-3 h-100">
                    <h3 class="h6">{{ $module }}</h3>
                    @foreach ($permissions as $permission => $label)
                        <div class="form-check">
                            <input class="form-check-input erp-perm" type="checkbox"
                                   name="permissions[]" value="{{ $permission }}"
                                   id="perm_{{ $permission }}"
                                   @checked(in_array($permission, old('permissions', $selected)))
                                   @disabled($role->is_super_admin ?? false)>
                            <label class="form-check-label" for="perm_{{ $permission }}">{{ $label }}</label>
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>

    @error('permissions') <div class="text-danger small mt-2">{{ $message }}</div> @enderror

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('roles.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>

@push('scripts')
    <script>
        document.getElementById('erp-perm-all')?.addEventListener('click', function () {
            document.querySelectorAll('.erp-perm:not(:disabled)').forEach(function (el) { el.checked = true; });
        });
        document.getElementById('erp-perm-none')?.addEventListener('click', function () {
            document.querySelectorAll('.erp-perm:not(:disabled)').forEach(function (el) { el.checked = false; });
        });
    </script>
@endpush
