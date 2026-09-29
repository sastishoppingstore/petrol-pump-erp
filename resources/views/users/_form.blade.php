<div class="erp-card p-4">
    <div class="row g-3">
        <div class="col-md-6">
            <label for="name" class="form-label">Full name <span class="text-danger">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                   class="form-control @error('name') is-invalid @enderror" required maxlength="255">
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-6">
            <label for="email" class="form-label">Email address <span class="text-danger">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                   class="form-control @error('email') is-invalid @enderror" required maxlength="150">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="employee_code" class="form-label">Employee code</label>
            <input type="text" id="employee_code" name="employee_code"
                   value="{{ old('employee_code', $user->employee_code) }}"
                   class="form-control @error('employee_code') is-invalid @enderror" maxlength="30">
            @error('employee_code') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="phone" class="form-label">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                   class="form-control @error('phone') is-invalid @enderror" maxlength="30">
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
            <select id="status" name="status" class="form-select @error('status') is-invalid @enderror" required>
                @foreach (['ACTIVE', 'DISABLED'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $user->status ?? 'ACTIVE') === $option)>{{ ucfirst(strtolower($option)) }}</option>
                @endforeach
            </select>
            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="password" class="form-label">
                Password
                @if (! $user->exists) <span class="text-danger">*</span> @else <span class="text-muted small">(leave blank to keep current)</span> @endif
            </label>
            <input type="password" id="password" name="password"
                   class="form-control @error('password') is-invalid @enderror"
                   @if (! $user->exists) required @endif
                   autocomplete="new-password" minlength="8">
            @error('password') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>

        <div class="col-md-4">
            <label for="password_confirmation" class="form-label">Confirm password</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="form-control" autocomplete="new-password">
        </div>

        <div class="col-12">
            <hr class="my-1">
            <h2 class="h6">Roles <span class="text-danger">*</span></h2>
            <p class="text-muted small">Role decides what this user can do. Permissions are managed on the Roles screen.</p>

            <div class="row g-2">
                @foreach ($roles as $role)
                    <div class="col-md-6 col-lg-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}"
                                   @checked(in_array($role->id, old('roles', $selectedRoles ?? [])))>
                            <label class="form-check-label" for="role_{{ $role->id }}">
                                {{ $role->label }}
                                <span class="text-muted small">({{ $role->name }})</span>
                            </label>
                        </div>
                    </div>
                @endforeach
            </div>
            @error('roles') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
        </div>

        <div class="col-12">
            <hr class="my-1">
            <h2 class="h6">Branch access</h2>
            <p class="text-muted small">
                A manager or cashier only sees the branches selected here. Administrators are not branch-scoped.
            </p>

            <div class="row g-2">
                @forelse ($branches as $branch)
                    <div class="col-md-6 col-lg-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox"
                                   name="branches[]" value="{{ $branch->id }}" id="branch_{{ $branch->id }}"
                                   @checked(in_array($branch->id, old('branches', $selectedBranches ?? [])))>
                            <label class="form-check-label" for="branch_{{ $branch->id }}">
                                {{ $branch->name }} <span class="text-muted small">({{ $branch->code }})</span>
                            </label>
                        </div>
                    </div>
                @empty
                    <div class="col-12 text-muted small">No branches defined yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <button type="submit" class="btn btn-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">Cancel</a>
    </div>
</div>
