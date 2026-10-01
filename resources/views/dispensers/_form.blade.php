{{--
    Dispenser form (create + edit shared) — 2026 redesign.
    Centered glass card, label input ke upar aur center, har field
    .field-3d + .input-3d me. Field names / old() values same hain.
--}}
<div class="glass-card p-6 sm:p-8">
    <div class="grid grid-cols-1 gap-5 md:grid-cols-12">
        <div class="md:col-span-4">
            <label for="branch_id" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">Branch <span class="text-red-500">*</span></label>
            <div class="field-3d">
                <select id="branch_id" name="branch_id" class="input-3d @error('branch_id') border-red-400 @enderror" required>
                    <option value="">Select branch…</option>
                    @foreach ($branches as $branch)
                        <option value="{{ $branch->id }}" @selected((string) old('branch_id', $dispenser->branch_id) === (string) $branch->id)>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @error('branch_id') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-4">
            <label for="dispenser_number" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">Dispenser number <span class="text-red-500">*</span></label>
            <div class="field-3d">
                <input type="text" id="dispenser_number" name="dispenser_number"
                       value="{{ old('dispenser_number', $dispenser->dispenser_number) }}"
                       class="input-3d @error('dispenser_number') border-red-400 @enderror" required maxlength="30">
            </div>
            @error('dispenser_number') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-4">
            <label for="status" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">Status <span class="text-red-500">*</span></label>
            <div class="field-3d">
                <select id="status" name="status" class="input-3d @error('status') border-red-400 @enderror" required>
                    @foreach (['ACTIVE', 'INACTIVE', 'MAINTENANCE'] as $s)
                        <option value="{{ $s }}" @selected(old('status', $dispenser->status ?? 'ACTIVE') === $s)>{{ ucfirst(strtolower($s)) }}</option>
                    @endforeach
                </select>
            </div>
            @error('status') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-6">
            <label for="name" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">Name</label>
            <div class="field-3d">
                <input type="text" id="name" name="name" value="{{ old('name', $dispenser->name) }}"
                       class="input-3d @error('name') border-red-400 @enderror" maxlength="100">
            </div>
            @error('name') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-3">
            <label for="model" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">Model</label>
            <div class="field-3d">
                <input type="text" id="model" name="model" value="{{ old('model', $dispenser->model) }}"
                       class="input-3d @error('model') border-red-400 @enderror" maxlength="100">
            </div>
            @error('model') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-3">
            <label for="serial_number" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">Serial number</label>
            <div class="field-3d">
                <input type="text" id="serial_number" name="serial_number"
                       value="{{ old('serial_number', $dispenser->serial_number) }}"
                       class="input-3d @error('serial_number') border-red-400 @enderror" maxlength="100">
            </div>
            @error('serial_number') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-12">
            <label for="notes" class="mb-1.5 block text-center text-sm font-bold text-slate-700 dark:text-slate-200">Notes</label>
            <div class="field-3d">
                <textarea id="notes" name="notes" rows="2" class="input-3d">{{ old('notes', $dispenser->notes) }}</textarea>
            </div>
        </div>
    </div>

    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <button type="submit" class="btn-3d btn-3d-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('dispensers.index') }}" class="btn-3d btn-3d-ghost">Cancel</a>
    </div>
</div>
