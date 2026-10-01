{{--
    User form (shared create/edit) — 2026 redesign.
    Centered 3D card; labels input ke oopar + CENTER; fields .field-3d +
    .input-3d. Tamam field names, checkbox names/values (roles[], branches[]),
    ids aur validation hooks pehle jaisay hi hain.
--}}
<div class="glass-card mx-auto max-w-3xl p-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
        <div class="field-3d">
            <label for="name" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.users.full_name') }} <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}"
                   class="input-3d text-center @error('name') !border-red-400 @enderror" required maxlength="255">
            @error('name') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="email" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.users.email_address') }} <span class="text-red-500">*</span></label>
            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                   class="input-3d text-center @error('email') !border-red-400 @enderror" required maxlength="150">
            @error('email') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="employee_code" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.common.employee_code') }}</label>
            <input type="text" id="employee_code" name="employee_code"
                   value="{{ old('employee_code', $user->employee_code) }}"
                   class="input-3d text-center @error('employee_code') !border-red-400 @enderror" maxlength="30">
            @error('employee_code') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="phone" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.common.phone') }}</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                   class="input-3d text-center @error('phone') !border-red-400 @enderror" maxlength="30">
            @error('phone') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d md:col-span-2">
            <label for="status" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.common.status') }} <span class="text-red-500">*</span></label>
            <select id="status" name="status" class="input-3d text-center @error('status') !border-red-400 @enderror" required>
                @foreach (['ACTIVE', 'DISABLED'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $user->status ?? 'ACTIVE') === $option)>{{ ucfirst(strtolower($option)) }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="password" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                {{ __('admin.users.password') }}
                @if (! $user->exists) <span class="text-red-500">*</span> @else <span class="normal-case text-slate-400">{{ __('admin.users.password_keep') }}</span> @endif
            </label>
            <input type="password" id="password" name="password"
                   class="input-3d text-center @error('password') !border-red-400 @enderror"
                   @if (! $user->exists) required @endif
                   autocomplete="new-password" minlength="8">
            @error('password') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="password_confirmation" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.users.confirm_password') }}</label>
            <input type="password" id="password_confirmation" name="password_confirmation"
                   class="input-3d text-center" autocomplete="new-password">
        </div>
    </div>

    <div class="mt-6 border-t border-slate-200/70 pt-5 dark:border-slate-700/60">
        <h2 class="text-center text-sm font-black text-slate-800 dark:text-slate-100">{{ __('admin.common.roles') }} <span class="text-red-500">*</span></h2>
        <p class="mt-1 text-center text-xs text-slate-500">{{ __('admin.users.roles_help') }}</p>

        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($roles as $role)
                <label for="role_{{ $role->id }}" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5 dark:text-slate-200">
                    <input class="h-4 w-4 shrink-0 rounded border-slate-300 text-vital-primary focus:ring-vital-primary" type="checkbox"
                           name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}"
                           @checked(in_array($role->id, old('roles', $selectedRoles ?? [])))>
                    <span>{{ $role->label }} <span class="text-xs text-slate-400">({{ $role->name }})</span></span>
                </label>
            @endforeach
        </div>
        @error('roles') <p class="mt-2 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="mt-6 border-t border-slate-200/70 pt-5 dark:border-slate-700/60">
        <h2 class="text-center text-sm font-black text-slate-800 dark:text-slate-100">{{ __('admin.users.branch_access') }}</h2>
        <p class="mt-1 text-center text-xs text-slate-500">
            {{ __('admin.users.branch_access_help') }}
        </p>

        <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
            @forelse ($branches as $branch)
                <label for="branch_{{ $branch->id }}" class="flex cursor-pointer items-center justify-center gap-2 rounded-xl border border-slate-200 bg-white/60 px-3 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-slate-700 dark:bg-white/5 dark:text-slate-200">
                    <input class="h-4 w-4 shrink-0 rounded border-slate-300 text-vital-primary focus:ring-vital-primary" type="checkbox"
                           name="branches[]" value="{{ $branch->id }}" id="branch_{{ $branch->id }}"
                           @checked(in_array($branch->id, old('branches', $selectedBranches ?? [])))>
                    <span>{{ $branch->name }} <span class="text-xs text-slate-400">({{ $branch->code }})</span></span>
                </label>
            @empty
                <p class="text-center text-sm text-slate-400 sm:col-span-2 lg:col-span-3">{{ __('admin.users.no_branches') }}</p>
            @endforelse
        </div>
    </div>

    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <button type="submit" class="btn-3d btn-3d-primary">{{ $submitLabel ?? __('ui.actions.save') }}</button>
        <a href="{{ route('users.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
    </div>
</div>
