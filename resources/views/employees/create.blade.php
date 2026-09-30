@extends('layouts.app')

@section('title', $employee->exists ? 'Edit Employee — ' . $employee->name : 'Register Employee')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('employees.index') }}">Staff</a></li>
    <li class="text-slate-500">{{ $employee->exists ? 'Edit' : 'Register' }}</li>
@endsection

@section('content')
<div class="mx-auto max-w-2xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
            {{ $employee->exists ? 'Edit Employee / ملازم کی تفصیلات تبدیل کریں' : 'Register New Employee / نیا ملازم شامل کریں' }}
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Vital Petroleum — Mehar Filling Station staff profile and salary structure.
        </p>
    </div>

    <form method="POST"
          action="{{ $employee->exists ? route('employees.update', $employee) : route('employees.store') }}"
          class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        @csrf
        @if ($employee->exists)
            @method('PUT')
        @endif

        <div class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="name" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Full Name (پورا نام) *</label>
                    <input type="text" id="name" name="name" value="{{ old('name', $employee->name) }}" required
                           placeholder="e.g. Muhammad Aslam"
                           class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="designation" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Designation (عہدہ) *</label>
                    <select id="designation" name="designation" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                        @foreach (['Pump Attendant', 'Cashier', 'Security Guard', 'Shift Supervisor', 'Station Manager', 'Lube Expert', 'Accountant'] as $des)
                            <option value="{{ $des }}" @selected(old('designation', $employee->designation) === $des)>
                                {{ $des }}
                            </option>
                        @endforeach
                    </select>
                    @error('designation') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="phone" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Mobile Phone (فون)</label>
                    <input type="text" id="phone" name="phone" value="{{ old('phone', $employee->phone) }}"
                           placeholder="0300-1234567"
                           class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="cnic" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">CNIC (شناختی کارڈ نمبر)</label>
                    <input type="text" id="cnic" name="cnic" value="{{ old('cnic', $employee->cnic) }}"
                           placeholder="35404-1234567-1"
                           class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    @error('cnic') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="basic_salary" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Monthly Basic Salary (Rs.) *</label>
                    <input type="number" step="0.01" min="0" id="basic_salary" name="basic_salary"
                           value="{{ old('basic_salary', $employee->basic_salary ?? '35000') }}" required
                           class="w-full rounded-lg border-slate-300 font-mono text-base font-bold dark:border-slate-700 dark:bg-slate-800">
                    @error('basic_salary') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="daily_wage" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Daily Wage Equivalent (Rs.)</label>
                    <input type="number" step="0.01" min="0" id="daily_wage" name="daily_wage"
                           value="{{ old('daily_wage', $employee->daily_wage ?? '0') }}"
                           class="w-full rounded-lg border-slate-300 font-mono text-sm dark:border-slate-700 dark:bg-slate-800">
                    <span class="text-[11px] text-slate-400">If 0, calculated automatically from basic / 30.</span>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="joining_date" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Joining Date</label>
                    <input type="date" id="joining_date" name="joining_date"
                           value="{{ old('joining_date', $employee->joining_date?->format('Y-m-d') ?? date('Y-m-d')) }}"
                           class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                    @error('joining_date') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                @if ($employee->exists)
                    <div>
                        <label for="status" class="mb-1 block text-xs font-semibold uppercase text-slate-600 dark:text-slate-300">Employment Status *</label>
                        <select id="status" name="status" required class="w-full rounded-lg border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-800">
                            <option value="ACTIVE" @selected($employee->status === 'ACTIVE')>Active</option>
                            <option value="INACTIVE" @selected($employee->status === 'INACTIVE')>Inactive / Suspended</option>
                            <option value="TERMINATED" @selected($employee->status === 'TERMINATED')>Terminated</option>
                        </select>
                    </div>
                @endif
            </div>

            <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <a href="{{ route('employees.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300">
                    Cancel
                </a>
                <button type="submit" class="rounded-lg bg-red-600 px-5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                    {{ $employee->exists ? 'Update Employee' : 'Save Employee' }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
