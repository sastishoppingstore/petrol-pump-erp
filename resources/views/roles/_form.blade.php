{{--
    Role form (shared create/edit) — 2026 redesign.
    Centered glass card; permission matrix module-wise 3D cards me.
    SAKHT NOTE: har permission checkbox ka name="permissions[]", value aur
    id bilkul pehle jaisa hai; "Select all / Clear all" buttons ke JS hooks
    (erp-perm-all / erp-perm-none / .erp-perm) bhi bilkul waisa hi hai.
--}}
<div class="glass-card mx-auto max-w-3xl p-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="field-3d">
            <label for="name" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Role name <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name" value="{{ old('name', $role->name) }}"
                   class="input-3d text-center @error('name') !border-red-400 @enderror"
                   required maxlength="100" placeholder="STORE_MANAGER"
                   @if ($isBuiltIn ?? false) readonly @endif>
            @error('name') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            <p class="mt-1.5 text-center text-xs text-slate-400">Uppercase, letters/numbers/underscore only.</p>
        </div>

        <div class="field-3d">
            <label for="label" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Display label <span class="text-red-500">*</span></label>
            <input type="text" id="label" name="label" value="{{ old('label', $role->label) }}"
                   class="input-3d text-center @error('label') !border-red-400 @enderror" required maxlength="150">
            @error('label') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="status" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Status <span class="text-red-500">*</span></label>
            <select id="status" name="status" class="input-3d text-center @error('status') !border-red-400 @enderror" required>
                @foreach (['ACTIVE', 'INACTIVE'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $role->status ?? 'ACTIVE') === $option)>{{ ucfirst(strtolower($option)) }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d md:col-span-3">
            <label for="description" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Description</label>
            <textarea id="description" name="description" rows="2"
                      class="input-3d text-center @error('description') !border-red-400 @enderror"
                      maxlength="1000">{{ old('description', $role->description) }}</textarea>
            @error('description') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-7 border-t border-slate-200/70 pt-5 dark:border-slate-700/60">
        <h2 class="text-center text-base font-black text-slate-800 dark:text-slate-100">Permissions</h2>
        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
            <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" id="erp-perm-all">Select all</button>
            <button type="button" class="btn-3d btn-3d-ghost btn-3d-sm" id="erp-perm-none">Clear all</button>
        </div>

        @if ($role->is_super_admin)
            <div class="mt-4 rounded-2xl border border-amber-300/60 bg-amber-50 px-4 py-3 text-center text-sm font-semibold text-amber-800 shadow-sm dark:bg-amber-500/10 dark:text-amber-300">
                This is the Administrator role. It always holds every permission and the matrix is locked.
            </div>
        @endif

        <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
            @foreach ($matrix as $module => $permissions)
                <div class="rounded-2xl border border-slate-200/80 bg-white/60 p-4 shadow-sm dark:border-slate-700/70 dark:bg-white/5">
                    <h3 class="text-center text-xs font-black uppercase tracking-wider text-slate-600 dark:text-slate-300">{{ $module }}</h3>
                    <div class="mt-3 space-y-2">
                        @foreach ($permissions as $permission => $label)
                            <label class="flex cursor-pointer items-center gap-2.5 rounded-xl px-2 py-1.5 text-sm font-medium text-slate-700 transition hover:bg-slate-900/[0.04] dark:text-slate-200 dark:hover:bg-white/5" for="perm_{{ $permission }}">
                                <input class="erp-perm h-4 w-4 shrink-0 rounded border-slate-300 text-vital-primary focus:ring-vital-primary" type="checkbox"
                                       name="permissions[]" value="{{ $permission }}"
                                       id="perm_{{ $permission }}"
                                       @checked(in_array($permission, old('permissions', $selected)))
                                       @disabled($role->is_super_admin ?? false)>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>

        @error('permissions') <p class="mt-3 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
    </div>

    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <button type="submit" class="btn-3d btn-3d-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('roles.index') }}" class="btn-3d btn-3d-ghost">Cancel</a>
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
