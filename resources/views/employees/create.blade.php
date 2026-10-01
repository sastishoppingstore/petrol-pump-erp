@extends('layouts.app')

@section('title', $employee->exists ? __('admin.employee_form.edit_title') . ' — ' . $employee->name : __('admin.employee_form.register_title'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('employees.index') }}" class="hover:text-vital-primary">{{ __('admin.employees.staff') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ $employee->exists ? __('ui.actions.edit') : __('admin.employee_form.register') }}</li>
@endsection

{{--
    Employee create/edit (ek hi file dono ke liye) — 2026 redesign.
    Page head centered; form centered glass-card; labels input ke oopar +
    CENTER. Tamam field names/ids, dynamic action (store/update) aur
    @method hook pehle jaisay hi hain.
--}}
@section('content')
    <div class="page-head">
        <h1>{{ $employee->exists ? '✏️ ' . __('admin.employee_form.heading_edit') : '➕ ' . __('admin.employee_form.heading_register') }}</h1>
        <p>{{ __('admin.employee_form.subtitle') }}</p>
    </div>

    <form method="POST"
          action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}"
          class="glass-card mx-auto max-w-3xl p-6">
        @csrf
        @if ($employee->exists)
            @method('PUT')
        @endif

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="field-3d">
                <label for="name" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employee_form.full_name') }} *</label>
                <input type="text" id="name" name="name" value="{{ old('name', $employee->name) }}" required
                       placeholder="e.g. Muhammad Aslam"
                       class="input-3d text-center text-sm @error('name') !border-red-400 @enderror">
                @error('name') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="designation" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employee_form.designation') }} *</label>
                <select id="designation" name="designation" required class="input-3d text-center text-sm @error('designation') !border-red-400 @enderror">
                    @foreach (['Pump Attendant', 'Cashier', 'Security Guard', 'Shift Supervisor', 'Station Manager', 'Lube Expert', 'Accountant'] as $des)
                        <option value="{{ $des }}" @selected(old('designation', $employee->designation) === $des)>
                            {{ $des }}
                        </option>
                    @endforeach
                </select>
                @error('designation') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="phone" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employee_form.mobile_phone') }}</label>
                <input type="text" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}"
                       placeholder="0300-1234567"
                       class="input-3d text-center font-mono text-sm @error('phone') !border-red-400 @enderror">
                @error('phone') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="cnic" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employee_form.cnic') }}</label>
                <input type="text" id="cnic" name="cnic" value="{{ old('cnic', $employee->cnic) }}"
                       placeholder="35404-1234567-1"
                       class="input-3d text-center font-mono text-sm @error('cnic') !border-red-400 @enderror">
                @error('cnic') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="basic_salary" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employee_form.basic_salary') }} *</label>
                <input type="number" step="0.01" min="0" id="basic_salary" name="basic_salary"
                       value="{{ old('basic_salary', $employee->basic_salary ?? '35000') }}" required
                       class="input-3d text-center font-mono text-base font-bold @error('basic_salary') !border-red-400 @enderror">
                @error('basic_salary') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            <div class="field-3d">
                <label for="daily_wage" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employee_form.daily_wage') }}</label>
                <input type="number" step="0.01" min="0" id="daily_wage" name="daily_wage"
                       value="{{ old('daily_wage', $employee->daily_wage ?? '0') }}"
                       class="input-3d text-center font-mono text-sm">
                <span class="mt-1 block text-center text-[11px] text-slate-400">{{ __('admin.employee_form.daily_wage_hint') }}</span>
            </div>

            <div class="field-3d">
                <label for="joining_date" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employee_form.joining_date') }}</label>
                <input type="date" id="joining_date" name="joining_date"
                       value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                       class="input-3d text-center text-sm @error('joining_date') !border-red-400 @enderror">
                @error('joining_date') <p class="mt-1.5 text-center text-xs font-semibold text-red-600">{{ $message }}</p> @enderror
            </div>

            @if ($employee->exists)
                <div class="field-3d">
                    <label for="status" class="mb-1.5 block text-center text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.employee_form.employment_status') }} *</label>
                    <select id="status" name="status" required class="input-3d text-center text-sm">
                        <option value="ACTIVE" @selected($employee->status === 'ACTIVE')>{{ __('admin.employees.status_active') }}</option>
                        <option value="INACTIVE" @selected($employee->status === 'INACTIVE')>{{ __('admin.employee_form.status_inactive') }}</option>
                        <option value="TERMINATED" @selected($employee->status === 'TERMINATED')>{{ __('admin.employees.status_terminated') }}</option>
                    </select>
                </div>
            @endif
        </div>

        <div class="mt-7 flex flex-wrap items-center justify-center gap-3 border-t border-slate-200/70 pt-5 dark:border-slate-700/60">
            <button type="submit" class="btn-3d btn-3d-primary">
                {{ $employee->exists ? __('admin.employee_form.update_employee') : __('admin.employee_form.save_employee') }}
            </button>
            <a href="{{ route('employees.index') }}" class="btn-3d btn-3d-ghost">{{ __('ui.actions.cancel') }}
            </a>
        </div>
    </form>
@endsection
