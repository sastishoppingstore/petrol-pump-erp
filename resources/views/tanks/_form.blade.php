{{--
    Tank form (create + edit shared) — 2026 redesign.
    Centered glass card, label input ke upar aur center, har field
    .field-3d + .input-3d me. Tamam field names, old() values aur
    validation hooks pehle jaisay hi hain.
--}}
<div class="glass-card p-6 sm:p-8">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
        <div class="md:col-span-4">
            <label for="branch_id" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.branch') }} <span class="text-red-500">*</span></label>
            <div class="field-3d">
                <select id="branch_id" name="branch_id" class="input-3d @error('branch_id') border-red-400 @enderror" required>
                    <option value="">{{ __('forecourt.common.select_branch') }}</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $tank->branch_id) === (string) $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('branch_id') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-4">
            <label for="fuel_product_id" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.fuel') }} <span class="text-red-500">*</span></label>
            <div class="field-3d">
                <select id="fuel_product_id" name="fuel_product_id" class="input-3d @error('fuel_product_id') border-red-400 @enderror" required>
                    <option value="">{{ __('forecourt.common.select_fuel') }}</option>
                    @foreach ($fuels as $fuel)
                        <option value="{{ $fuel->id }}" @selected((string) old('fuel_product_id', $tank->fuel_product_id) === (string) $fuel->id)>
                            {{ $fuel->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('fuel_product_id') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-4">
            <label for="tank_number" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.tanks.form.tank_number') }} <span class="text-red-500">*</span></label>
            <div class="field-3d">
                <input type="text" id="tank_number" name="tank_number" value="{{ old('tank_number', $tank->tank_number) }}"
                       class="input-3d @error('tank_number') border-red-400 @enderror" required maxlength="30">
            </div>
            @error('tank_number') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-8">
            <label for="name" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.description') }}</label>
            <div class="field-3d">
                <input type="text" id="name" name="name" value="{{ old('name', $tank->name) }}"
                       class="input-3d @error('name') border-red-400 @enderror" maxlength="100" placeholder="{{ __('forecourt.tanks.form.name_example') }}">
            </div>
            @error('name') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-4">
            <label for="status" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.status') }} <span class="text-red-500">*</span></label>
            <div class="field-3d">
                <select id="status" name="status" class="input-3d @error('status') border-red-400 @enderror" required>
                    @foreach (['ACTIVE', 'INACTIVE', 'MAINTENANCE'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $tank->status ?? 'ACTIVE') === $s)>{{ ucfirst(strtolower($s)) }}</option>
                    @endforeach
                </select>
            </div>
            @error('status') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-3">
            <label for="capacity" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.tanks.form.capacity_l') }} <span class="text-red-500">*</span></label>
            <div class="field-3d">
                <input type="number" step="0.001" min="0.001" id="capacity" name="capacity"
                       value="{{ old('capacity', $tank->capacity) }}"
                       class="input-3d @error('capacity') border-red-400 @enderror" required>
            </div>
            @error('capacity') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-3">
            <label for="opening_stock" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.tanks.form.opening_stock_l') }}</label>
            <div class="field-3d">
                <input type="number" step="0.001" min="0" id="opening_stock" name="opening_stock"
                       value="{{ old('opening_stock', $tank->opening_stock) }}"
                       class="input-3d @error('opening_stock') border-red-400 @enderror"
                       @if ($tank->exists) disabled @endif>
            </div>
            @if ($tank->exists)<p class="mt-1.5 text-center text-xs text-slate-400">{{ __('forecourt.tanks.form.opening_note') }}</p>@endif
            @error('opening_stock') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-3">
            <label for="low_stock_threshold" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.tanks.form.low_stock_at') }}</label>
            <div class="field-3d">
                <input type="number" step="0.001" min="0" id="low_stock_threshold" name="low_stock_threshold"
                       value="{{ old('low_stock_threshold', $tank->low_stock_threshold) }}"
                       class="input-3d @error('low_stock_threshold') border-red-400 @enderror">
            </div>
            @error('low_stock_threshold') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-3">
            <label for="installation_date" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.tanks.form.installed_on') }}</label>
            <div class="field-3d">
                <input type="date" id="installation_date" name="installation_date"
                       value="{{ old('installation_date', $tank->installation_date?->toDateString()) }}"
                       class="input-3d">
            </div>
        </div>

        <div class="md:col-span-6">
            <label for="min_level" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.tanks.form.min_level_l') }}</label>
            <div class="field-3d">
                <input type="number" step="0.001" min="0" id="min_level" name="min_level"
                       value="{{ old('min_level', $tank->min_level) }}"
                       class="input-3d @error('min_level') border-red-400 @enderror">
            </div>
            @error('min_level') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-6">
            <label for="max_level" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.tanks.form.max_level_l') }}</label>
            <div class="field-3d">
                <input type="number" step="0.001" min="0" id="max_level" name="max_level"
                       value="{{ old('max_level', $tank->max_level) }}"
                       class="input-3d @error('max_level') border-red-400 @enderror">
            </div>
            @error('max_level') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-12">
            <label for="notes" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">{{ __('forecourt.common.notes') }}</label>
            <div class="field-3d">
                <textarea id="notes" name="notes" rows="2" class="input-3d">{{ old('notes', $tank->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <button type="submit" class="btn-3d btn-3d-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('tanks.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}</a>
    </div>
</div>
