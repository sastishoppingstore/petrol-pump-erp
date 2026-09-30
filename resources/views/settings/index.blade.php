@extends('layouts.app')

@section('title', 'ترتیبات / Settings')
@section('breadcrumb')
    <li class="text-slate-500">Settings</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">اسٹیشن ترتیبات / Station Settings</h1>
            <p class="mt-1 text-sm text-slate-500">
                Mehar Filling Station (مہر فلنگ اسٹیشن) - Identity, receipt branding &amp; operational thresholds.
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('settings.bill-designer') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-red-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                بل ڈیزائنر / Bill Designer
            </a>
            <a href="{{ route('backups.index') }}" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                بیک اپ / Backups
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900/50 dark:bg-emerald-950/30 dark:text-emerald-300">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('settings.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- 1. Station Identity --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">اسٹیشن کی شناخت / Station Identity</h3>
            <p class="mt-1 text-xs text-slate-500">یہ معلومات تمام بلوں، رسیدوں اور رپورٹوں پر پرنٹ ہوتی ہے۔</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">اسٹیشن کا نام (اردو) / Urdu Name</label>
                    <input type="text" name="station_name_ur" value="{{ old('station_name_ur', $station['station_name_ur'] ?? 'مہر فلنگ اسٹیشن') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white" dir="rtl">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">Station Name (English)</label>
                    <input type="text" name="station_name_en" value="{{ old('station_name_en', $station['station_name_en'] ?? 'Mehar Filling Station') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">مالک کا نام / Owner Name</label>
                    <input type="text" name="owner_name" value="{{ old('owner_name', $station['owner_name'] ?? 'Muhammad Rizwan Aslam') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">فون نمبر / Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $station['phone'] ?? '0300-4342343') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">واٹس ایپ الرٹ نمبر / WhatsApp</label>
                    <input type="text" name="whatsapp" value="{{ old('whatsapp', $station['whatsapp'] ?? 'wa.me/923004342343') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">او ایم سی برانڈ / OMC Brand</label>
                    <input type="text" name="omc_brand" value="{{ old('omc_brand', $station['omc_brand'] ?? 'Vital Petroleum Pvt. Ltd.') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">پتہ / Station Address</label>
                    <input type="text" name="address" value="{{ old('address', $station['address'] ?? 'G39V+VQ8, Sheikhupura–Sharaqpur Road, Sheikhupura, Punjab, Pakistan') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>
        </div>

        {{-- 2. Tax & Legal Identity --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">ٹیکس اور قانونی تفصیلات / Tax &amp; Legal</h3>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">NTN نمبر</label>
                    <input type="text" name="ntn" value="{{ old('ntn', $settings['company.ntn'] ?? '') }}" placeholder="مثلاً: 1234567-8" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">STRN / Sales Tax نمبر</label>
                    <input type="text" name="strn" value="{{ old('strn', $settings['company.strn'] ?? '') }}" placeholder="مثلاً: 12-34-5678-901-23" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>
        </div>

        {{-- 3. Operational Thresholds --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">آپریشنل حدیں / Operational Thresholds</h3>
            <p class="mt-1 text-xs text-slate-500">ان حدود سے زیادہ کا فرق ہونے پر مینیجر یا مالک کی منظوری درکار ہوگی۔</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-3">
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">شفٹ کیش فرق کی حد (روپے) / Shift Cash Threshold</label>
                    <input type="number" step="0.01" name="shift_variance_threshold" value="{{ old('shift_variance_threshold', $settings['business.shift_variance_threshold'] ?? '100.00') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">میٹر ریڈنگ فرق کی گنجائش (لیٹر) / Meter Tolerance</label>
                    <input type="number" step="0.001" name="meter_variance_tolerance" value="{{ old('meter_variance_tolerance', $settings['business.meter_variance_tolerance'] ?? '0.500') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-300">ادھار زائد المیعاد دن / Overdue Credit Days</label>
                    <input type="number" name="overdue_credit_days" value="{{ old('overdue_credit_days', $settings['business.overdue_credit_days'] ?? '30') }}" class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
                </div>
            </div>
        </div>

        {{-- 4. Receipt Footer --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <h3 class="text-lg font-bold text-slate-900 dark:text-white">رسید کا الوداعی پیغام / Receipt Message</h3>

            <div class="mt-4">
                <input type="text" name="thank_you_message" value="{{ old('thank_you_message', $settings['invoice.thank_you_message'] ?? 'Thank you for your patronage. Drive safely.') }}" class="block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-white">
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-red-600 px-6 py-2.5 text-sm font-bold text-white shadow-md hover:bg-red-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                تبدیلیاں محفوظ کریں / Save Settings
            </button>
        </div>
    </form>
</div>
@endsection
