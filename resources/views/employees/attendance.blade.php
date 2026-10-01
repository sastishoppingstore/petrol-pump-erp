@extends('layouts.app')

@section('title', __('admin.emp_attendance.title'))
@section('breadcrumb')
    <li>/</li>
    <li><a href="{{ route('employees.index') }}" class="hover:text-vital-primary">{{ __('admin.employees.staff') }}</a></li>
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.emp_attendance.breadcrumb') }}</li>
@endsection

{{--
    Daily Attendance — 2026 redesign.
    Page head centered; date filter + summary pills glass box me; attendance
    sheet .table-3d. Alpine markAll() hook, radio names
    (attendance[employee_id]) aur values bilkul pehle jaisay hi hain.
--}}
@section('content')
<div x-data="{
    markAll(status) {
        document.querySelectorAll('input[type=radio][value=' + status + ']').forEach(el => el.checked = true);
    }
}" class="space-y-6">
    {{-- Header --}}
    <div class="page-head">
        <h1>🗓️ {{ __('admin.emp_attendance.title') }}</h1>
        <p>{{ __('admin.emp_attendance.subtitle') }}</p>
        <div class="page-actions">
            <button type="button" @click="markAll('PRESENT')" class="btn-3d btn-3d-success btn-3d-sm">✓ {{ __('admin.emp_attendance.mark_all_present') }}
            </button>
            <a href="{{ route('employees.payroll') }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('admin.emp_attendance.view_payroll') }}
            </a>
        </div>
    </div>

    {{-- Date Filter & Summary Pills --}}
    <div class="glass-card flex flex-col items-center justify-between gap-4 p-4 lg:flex-row">
        <form method="GET" action="{{ route('employees.attendance') }}" class="flex items-center gap-3">
            <label for="date" class="text-xs font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('admin.emp_attendance.select_date') }}</label>
            <div class="field-3d">
                <input type="date" id="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                       class="input-3d text-center text-sm font-bold">
            </div>
        </form>

        <div class="flex flex-wrap items-center justify-center gap-2 text-xs">
            <div class="pill-status pill-active">
                <span class="dot" aria-hidden="true"></span>
                {{ __('admin.common.present') }}: {{ $stats['present'] }}
            </div>
            <div class="pill-status bg-red-500/15 text-red-700 dark:text-red-300">
                <span class="dot bg-red-500" aria-hidden="true"></span>
                {{ __('admin.common.absent') }}: {{ $stats['absent'] }}
            </div>
            <div class="pill-status bg-amber-500/15 text-amber-700 dark:text-amber-300">
                <span class="dot bg-amber-500" aria-hidden="true"></span>
                {{ __('admin.common.half_day_dash') }}: {{ $stats['half_day'] }}
            </div>
            <div class="pill-status bg-sky-500/15 text-sky-700 dark:text-sky-300">
                <span class="dot bg-sky-500" aria-hidden="true"></span>
                {{ __('admin.common.leave') }}: {{ $stats['leave'] }}
            </div>
        </div>
    </div>

    {{-- Attendance Form --}}
    <form method="POST" action="{{ route('employees.attendance.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="glass-card overflow-hidden">
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('admin.common.code') }}</th>
                            <th>{{ __('admin.employees.employee_name') }}</th>
                            <th>{{ __('admin.employee_form.designation') }}</th>
                            <th>{{ __('admin.common.present') }}</th>
                            <th>{{ __('admin.common.absent') }}</th>
                            <th>{{ __('admin.common.leave') }}</th>
                            <th>{{ __('admin.common.half_day_dash') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($employees as $emp)
                            @php
                                $status = $existingAttendances->get($emp->id)?->status ?? 'PRESENT';
                            @endphp
                            <tr>
                                <td class="font-mono text-xs font-bold text-slate-500">{{ $emp->code }}</td>
                                <td>
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $emp->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $emp->phone ?? '' }}</div>
                                </td>
                                <td class="text-xs text-slate-600 dark:text-slate-300">{{ $emp->designation }}</td>

                                <td>
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                        <input type="radio" name="attendance[{{ $emp->id }}]" value="PRESENT"
                                               @checked($status === 'PRESENT')
                                               class="h-5 w-5 border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    </label>
                                </td>

                                <td>
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                        <input type="radio" name="attendance[{{ $emp->id }}]" value="ABSENT"
                                               @checked($status === 'ABSENT')
                                               class="h-5 w-5 border-slate-300 text-red-600 focus:ring-red-500">
                                    </label>
                                </td>

                                <td>
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                        <input type="radio" name="attendance[{{ $emp->id }}]" value="LEAVE"
                                               @checked($status === 'LEAVE')
                                               class="h-5 w-5 border-slate-300 text-sky-600 focus:ring-sky-500">
                                    </label>
                                </td>

                                <td>
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                        <input type="radio" name="attendance[{{ $emp->id }}]" value="HALF_DAY"
                                               @checked($status === 'HALF_DAY')
                                               class="h-5 w-5 border-slate-300 text-amber-600 focus:ring-amber-500">
                                    </label>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="py-12 text-slate-500">
                                    {{ __('admin.emp_attendance.none') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-center justify-between gap-3 border-t border-slate-200/70 p-4 dark:border-slate-700/60 sm:flex-row">
                <span class="text-xs text-slate-500">{{ __('admin.emp_attendance.staff_count', ['count' => $employees->count()]) }}</span>
                <button type="submit" class="btn-3d btn-3d-primary">{{ __('admin.emp_attendance.save_for', ['date' => \Carbon\Carbon::parse($date)->format('d M Y')]) }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
