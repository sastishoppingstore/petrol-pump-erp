<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('app.name') }}</title>
    {{-- Admin Settings → Theme ke rang (CSS variables) --}}
    @include('partials.theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
{{--
    Login makeover (2026 redesign)
    - Dark petroleum gradient + brand glow ke upar centered 3D glass card.
    - Autocomplete fix: card par koi transform / overflow-hidden nahi hai
      aur har input .field-3d wrapper (position:relative) me hai, is liye
      browser ka autofill dropdown saaf overlay banta hai — button ya text
      apni jagah se nahi hilta, layout clip nahi hota.
--}}
<body class="min-h-full bg-navy-950 font-sans text-slate-100 antialiased">
@php
    $stationNameEn = app(\App\Services\System\SettingService::class)->get('station_name_en') ?: config('app.name');
    $omcBrandName = app(\App\Services\System\SettingService::class)->get('omc_brand_name');
@endphp

<div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
    {{-- Petroleum gradient backdrop + brand glows (decorative, pointer-events-none) --}}
    <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-navy-950 via-slate-950 to-navy-900" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -left-32 -top-32 h-96 w-96 rounded-full opacity-30 blur-3xl" style="background: rgb(var(--brand-primary-rgb));" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-navy-500 opacity-20 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent" aria-hidden="true"></div>

    <div class="relative w-full max-w-md">
        {{-- Brand header --}}
        <div class="mb-7 text-center">
            <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-[1.75rem] bg-gradient-to-br from-vital-primary to-vital-darkred text-4xl shadow-glow ring-1 ring-white/30"
                 style="animation: idleFloat 4s ease-in-out infinite;" aria-hidden="true">⛽</div>
            <h1 class="mt-4 text-2xl font-black tracking-tight text-white">{{ $stationNameEn }}</h1>
            @if ($omcBrandName)
                <p class="mt-1 text-xs font-bold uppercase tracking-[0.25em] text-amber-300/90">{{ $omcBrandName }}</p>
            @endif
            <p class="mt-2 text-sm text-slate-400">Sign in to continue to your station ERP</p>
        </div>

        {{-- 3D glass card — NOTE: yahan jaan boojh kar overflow-hidden / transform
             nahi lagaya gaya, warna browser autocomplete dropdown clip ho jata hai. --}}
        <div class="glass-dark rounded-[1.75rem] p-7 shadow-3d-lg sm:p-8">
            @include('partials.flash')

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                <div class="field-3d mb-5">
                    <label for="email" class="mb-1.5 block text-sm font-semibold text-slate-200">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="input-glass @error('email') !border-red-400 @enderror"
                           required autofocus autocomplete="username" maxlength="150" placeholder="you@station.pk">
                    @error('email')
                        <p class="mt-1.5 text-xs font-semibold text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-3d mb-5">
                    <label for="password" class="mb-1.5 block text-sm font-semibold text-slate-200">Password</label>
                    <input type="password" id="password" name="password"
                           class="input-glass @error('password') !border-red-400 @enderror"
                           required autocomplete="current-password" placeholder="••••••••">
                    @error('password')
                        <p class="mt-1.5 text-xs font-semibold text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-6 flex items-center justify-between gap-3">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                               class="h-4 w-4 rounded border-white/30 bg-white/10 text-vital-primary focus:ring-vital-primary">
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-amber-300 hover:text-amber-200 hover:underline">Forgot password?</a>
                </div>

                <button type="submit" class="btn-3d btn-3d-primary w-full py-3 text-base">
                    Sign in
                </button>

                <div class="my-5 flex items-center gap-3">
                    <div class="h-px flex-1 bg-white/15"></div>
                    <span class="text-[11px] font-bold uppercase tracking-widest text-slate-400">یا / OR</span>
                    <div class="h-px flex-1 bg-white/15"></div>
                </div>

                <a href="{{ route('pin.login') }}" class="btn-3d btn-3d-ghost w-full py-3 text-base !text-slate-900">
                    <span>⛽</span>
                    <span>کیشئر فوری پن لاگ اِن (Cashier PIN)</span>
                </a>
            </form>
        </div>

        <p class="mt-6 text-center text-xs text-slate-500">
            Authorised personnel only. All activity is logged.
        </p>
    </div>
</div>
</body>
</html>
