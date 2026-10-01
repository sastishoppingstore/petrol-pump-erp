{{--
    Branch form (shared create/edit) — 2026 redesign.
    Centered 3D card; labels input ke oopar + CENTER; fields .field-3d +
    .input-3d. Tamam field names/ids/attributes pehle jaisay hi hain.
--}}
<div class="glass-card mx-auto max-w-3xl p-6">
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="field-3d">
            <label for="code" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Code <span class="text-red-500">*</span></label>
            <input type="text" id="code" name="code"
                   value="{{ old('code', $branch->code) }}"
                   class="input-3d text-center @error('code') !border-red-400 @enderror"
                   required maxlength="20" placeholder="BR-01">
            @error('code') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="name" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Branch name <span class="text-red-500">*</span></label>
            <input type="text" id="name" name="name"
                   value="{{ old('name', $branch->name) }}"
                   class="input-3d text-center @error('name') !border-red-400 @enderror"
                   required maxlength="150">
            @error('name') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="status" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Status <span class="text-red-500">*</span></label>
            <select id="status" name="status"
                    class="input-3d text-center @error('status') !border-red-400 @enderror" required>
                @foreach (['ACTIVE', 'INACTIVE'] as $option)
                    <option value="{{ $option }}" @selected(old('status', $branch->status) === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @error('status') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d md:col-span-3">
            <label for="address" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Address</label>
            <input type="text" id="address" name="address"
                   value="{{ old('address', $branch->address) }}"
                   class="input-3d text-center @error('address') !border-red-400 @enderror" maxlength="500">
            @error('address') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="city" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">City</label>
            <input type="text" id="city" name="city" value="{{ old('city', $branch->city) }}"
                   class="input-3d text-center @error('city') !border-red-400 @enderror" maxlength="100">
            @error('city') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="state" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">State / Province</label>
            <input type="text" id="state" name="state" value="{{ old('state', $branch->state) }}"
                   class="input-3d text-center @error('state') !border-red-400 @enderror" maxlength="100">
            @error('state') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="country" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Country</label>
            <input type="text" id="country" name="country" value="{{ old('country', $branch->country) }}"
                   class="input-3d text-center @error('country') !border-red-400 @enderror" maxlength="100">
            @error('country') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="phone" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Phone</label>
            <input type="text" id="phone" name="phone" value="{{ old('phone', $branch->phone) }}"
                   class="input-3d text-center @error('phone') !border-red-400 @enderror" maxlength="30">
            @error('phone') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="email" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email</label>
            <input type="email" id="email" name="email" value="{{ old('email', $branch->email) }}"
                   class="input-3d text-center @error('email') !border-red-400 @enderror" maxlength="150">
            @error('email') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="ntn_number" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">NTN number</label>
            <input type="text" id="ntn_number" name="ntn_number" value="{{ old('ntn_number', $branch->ntn_number) }}"
                   class="input-3d text-center @error('ntn_number') !border-red-400 @enderror" maxlength="30">
            @error('ntn_number') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="strn_number" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">STRN number</label>
            <input type="text" id="strn_number" name="strn_number" value="{{ old('strn_number', $branch->strn_number) }}"
                   class="input-3d text-center @error('strn_number') !border-red-400 @enderror" maxlength="30">
            @error('strn_number') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="latitude" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Latitude</label>
            <input type="number" step="0.0000001" id="latitude" name="latitude"
                   value="{{ old('latitude', $branch->latitude) }}"
                   class="input-3d text-center @error('latitude') !border-red-400 @enderror" min="-90" max="90">
            @error('latitude') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="field-3d">
            <label for="longitude" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Longitude</label>
            <input type="number" step="0.0000001" id="longitude" name="longitude"
                   value="{{ old('longitude', $branch->longitude) }}"
                   class="input-3d text-center @error('longitude') !border-red-400 @enderror" min="-180" max="180">
            @error('longitude') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div class="mt-7 flex flex-wrap items-center justify-center gap-3">
        <button type="submit" class="btn-3d btn-3d-primary">{{ $submitLabel ?? 'Save' }}</button>
        <a href="{{ route('branches.index') }}" class="btn-3d btn-3d-ghost">Cancel</a>
    </div>
</div>
