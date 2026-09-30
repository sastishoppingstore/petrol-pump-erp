@extends('layouts.app')

@section('title', 'Daily Staff Attendance / روزانہ حاضری')
@section('breadcrumb')
    <li class="text-slate-500"><a href="{{ route('employees.index') }}">Staff</a></li>
    <li class="text-slate-500">Attendance</li>
@endsection

@section('content')
<div x-data="{
    markAll(status) {
        document.querySelectorAll('input[type=radio][value=' + status + ']').forEach(el => el.checked = true);
    }
}" class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">Daily Staff Attendance / روزانہ حاضری شیٹ</h1>
            <p class="mt-1 text-sm text-slate-500">
                Mark shift attendance for pump attendants, cashiers &amp; staff. Determines monthly payroll deductions.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <button type="button" @click="markAll('PRESENT')" class="rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-bold text-emerald-800 hover:bg-emerald-100 dark:border-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                ✓ Mark All Present (سب حاضر)
            </button>
            <a href="{{ route('employees.payroll') }}" class="rounded-lg border border-slate-300 bg-white px-3.5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200">
                View Payroll Sheet
            </a>
        </div>
    </div>

    {{-- Date Filter & Summary Pills --}}
    <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <form method="GET" action="{{ route('employees.attendance') }}" class="flex items-center gap-3">
            <label for="date" class="text-xs font-semibold uppercase text-slate-500">Select Date:</label>
            <input type="date" id="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                   class="rounded-lg border-slate-300 text-sm font-semibold text-slate-900 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
        </form>

        <div class="flex flex-wrap items-center gap-3 text-xs">
            <div class="flex items-center gap-1.5 rounded-lg bg-emerald-50 px-3 py-1.5 font-bold text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                Present: {{ $stats['present'] }}
            </div>
            <div class="flex items-center gap-1.5 rounded-lg bg-red-50 px-3 py-1.5 font-bold text-red-800 dark:bg-red-900/30 dark:text-red-300">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                Absent: {{ $stats['absent'] }}
            </div>
            <div class="flex items-center gap-1.5 rounded-lg bg-amber-50 px-3 py-1.5 font-bold text-amber-800 dark:bg-amber-900/30 dark:text-amber-300">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                Half-Day: {{ $stats['half_day'] }}
            </div>
            <div class="flex items-center gap-1.5 rounded-lg bg-sky-50 px-3 py-1.5 font-bold text-sky-800 dark:bg-sky-900/30 dark:text-sky-300">
                <span class="h-2 w-2 rounded-full bg-sky-500"></span>
                Leave: {{ $stats['leave'] }}
            </div>
        </div>
    </div>

    {{-- Attendance Form --}}
    <form method="POST" action="{{ route('employees.attendance.store') }}">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                        <tr>
                            <th class="px-5 py-3">Code</th>
                            <th class="px-5 py-3">Employee Name</th>
                            <th class="px-5 py-3">Designation</th>
                            <th class="px-5 py-3 text-center">Present (حاضر)</th>
                            <th class="px-5 py-3 text-center">Absent (غیر حاضر)</th>
                            <th class="px-5 py-3 text-center">Leave (چھٹی)</th>
                            <th class="px-5 py-3 text-center">Half-Day (آدھا دن)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse ($employees as $emp)
                            @php
                                $status = $existingAttendances->get($emp->id)?->status ?? 'PRESENT';
                            @endphp
                            <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-800/40">
                                <td class="px-5 py-3 font-mono text-xs font-semibold text-slate-500">{{ $emp->code }}</td>
                                <td class="px-5 py-3">
                                    <div class="font-bold text-slate-900 dark:text-white">{{ $emp->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $emp->phone ?? '' }}</div>
                                </td>
                                <td class="px-5 py-3 text-xs text-slate-600 dark:text-slate-300">{{ $emp->designation }}</td>

                                <td class="px-5 py-3 text-center">
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                        <input type="radio" name="attendance[{{ $emp->id }}]" value="PRESENT"
                                               @checked($status === 'PRESENT')
                                               class="h-4 w-4 border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    </label>
                                </td>

                                <td class="px-5 py-3 text-center">
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                        <input type="radio" name="attendance[{{ $emp->id }}]" value="ABSENT"
                                               @checked($status === 'ABSENT')
                                               class="h-4 w-4 border-slate-300 text-red-600 focus:ring-red-500">
                                    </label>
                                </td>

                                <td class="px-5 py-3 text-center">
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                        <input type="radio" name="attendance[{{ $emp->id }}]" value="LEAVE"
                                               @checked($status === 'LEAVE')
                                               class="h-4 w-4 border-slate-300 text-sky-600 focus:ring-sky-500">
                                    </label>
                                </td>

                                <td class="px-5 py-3 text-center">
                                    <label class="inline-flex cursor-pointer items-center justify-center p-1">
                                        <input type="radio" name="attendance[{{ $emp->id }}]" value="HALF_DAY"
                                               @checked($status === 'HALF_DAY')
                                               class="h-4 w-4 border-slate-300 text-amber-600 focus:ring-amber-500">
                                    </label>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">
                                    No active employees registered.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between border-t border-slate-200 p-4 dark:border-slate-800">
                <span class="text-xs text-slate-500">{{ $employees->count() }} active staff members</span>
                <button type="submit" class="rounded-lg bg-red-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                    Save Attendance for {{ \Carbon\Carbon::parse($date)->format('d M Y') }}
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
