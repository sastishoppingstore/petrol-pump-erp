<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('app.name') }}</title>
    {{-- Admin Settings → Theme ke rang (CSS variables) --}}
    @include('partials.theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        /* ===== Login page — 3D animated glass (self-contained) ===== */
        .login-bg {
            background: linear-gradient(150deg, #020617 0%, #0f172a 58%, #111c30 100%);
        }
        .orb {
            position: absolute;
            border-radius: 9999px;
            filter: blur(70px);
            pointer-events: none;
            will-change: transform;
        }
        .orb-1 {
            width: 26rem; height: 26rem; left: -8rem; top: -8rem;
            background: rgb(var(--brand-primary-rgb) / 0.5);
            animation: orbFloat1 11s ease-in-out infinite;
        }
        .orb-2 {
            width: 30rem; height: 30rem; right: -10rem; bottom: -12rem;
            background: rgba(37, 99, 235, 0.38);
            animation: orbFloat2 14s ease-in-out infinite;
        }
        .orb-3 {
            width: 15rem; height: 15rem; right: 16%; top: 8%;
            background: rgba(245, 158, 11, 0.22);
            animation: orbFloat1 9s ease-in-out infinite reverse;
        }
        @keyframes orbFloat1 {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
            50% { transform: translate3d(2.5rem, 2rem, 0) scale(1.08); }
        }
        @keyframes orbFloat2 {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
            50% { transform: translate3d(-2rem, -2.5rem, 0) scale(1.06); }
        }
        @keyframes loginCardIn {
            from { opacity: 0; transform: translateY(26px) scale(0.97); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }
        .login-card {
            width: 100%;
            max-width: 440px;
            background: linear-gradient(155deg, rgba(255, 255, 255, 0.11), rgba(255, 255, 255, 0.05));
            -webkit-backdrop-filter: blur(20px) saturate(1.5);
            backdrop-filter: blur(20px) saturate(1.5);
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 1.75rem;
            box-shadow: 0 35px 70px -18px rgba(0, 0, 0, 0.75),
                        0 18px 28px -12px rgba(0, 0, 0, 0.55),
                        0 0 0 1px rgba(255, 255, 255, 0.04),
                        0 0 65px -18px rgb(var(--brand-primary-rgb) / 0.45),
                        inset 0 1px 0 0 rgba(255, 255, 255, 0.22),
                        inset 0 -1px 0 0 rgba(0, 0, 0, 0.25);
            /* fill-mode backwards: animation khatam hote hi transform khatam —
               browser autocomplete/autofill overlay kabhi clip nahi hota */
            animation: loginCardIn 0.7s cubic-bezier(0.22, 1, 0.36, 1) backwards;
        }
        .login-logo {
            animation: idleFloat 4.5s ease-in-out infinite;
            box-shadow: 0 22px 38px -10px rgb(var(--brand-primary-rgb) / 0.65),
                        0 0 45px -6px rgb(var(--brand-primary-rgb) / 0.55),
                        inset 0 2px 0 0 rgba(255, 255, 255, 0.4),
                        inset 0 -3px 0 0 rgba(0, 0, 0, 0.28);
        }
        /* Input guarantee: typing / paste / tap-focus kabhi block na ho */
        .login-input {
            pointer-events: auto !important;
            user-select: text !important;
            -webkit-user-select: text !important;
            position: relative;
            z-index: 2;
            width: 100%;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.16);
            background: rgba(2, 6, 23, 0.5);
            color: #ffffff;
            padding: 1rem 1.1rem 1rem 3.1rem;
            font-size: 1.05rem;
            box-shadow: inset 0 2px 8px rgba(0, 0, 0, 0.45),
                        0 1px 0 0 rgba(255, 255, 255, 0.07);
            transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
        }
        .login-input::placeholder { color: rgba(203, 213, 225, 0.5); }
        .login-input:focus {
            outline: none;
            background: rgba(2, 6, 23, 0.68);
            border-color: rgb(var(--brand-primary-rgb));
            box-shadow: 0 0 0 4px rgb(var(--brand-primary-rgb) / 0.3),
                        0 0 22px -4px rgb(var(--brand-primary-rgb) / 0.55),
                        inset 0 2px 8px rgba(0, 0, 0, 0.4);
        }
        .login-field-icon {
            position: absolute;
            left: 1.05rem;
            top: 50%;
            transform: translateY(-50%);
            z-index: 3;
            font-size: 1.15rem;
            line-height: 1;
            pointer-events: none;
            opacity: 0.85;
        }
        .btn-login {
            transition: transform 0.2s cubic-bezier(0.22, 1, 0.36, 1), box-shadow 0.2s ease, filter 0.2s ease;
            box-shadow: 0 20px 32px -10px rgb(var(--brand-primary-rgb) / 0.65),
                        0 0 28px -6px rgb(var(--brand-primary-rgb) / 0.5),
                        inset 0 2px 0 0 rgba(255, 255, 255, 0.35),
                        inset 0 -3px 0 0 rgba(0, 0, 0, 0.3);
        }
        .btn-login:hover {
            transform: translateY(-4px);
            box-shadow: 0 28px 42px -12px rgb(var(--brand-primary-rgb) / 0.75),
                        0 0 40px -4px rgb(var(--brand-primary-rgb) / 0.65),
                        inset 0 2px 0 0 rgba(255, 255, 255, 0.4),
                        inset 0 -3px 0 0 rgba(0, 0, 0, 0.3);
        }
        .btn-login:active { transform: translateY(1px) scale(0.99); }
        @media (prefers-reduced-motion: reduce) {
            .orb, .login-logo { animation: none; }
            .login-card { animation-duration: 0.01s; }
        }
    </style>
</head>
{{--
    Login overhaul (2026-10-01, owner feedback ke baad)
    - Card ab bilkul 440px balanced container hai — na stretched, na chhota.
    - Inputs ki guarantee: decorative orbs pointer-events-none hain, inputs par
      pointer-events:auto + user-select:text + z-index laga hai — typing, paste
      aur mobile tap-focus 100% chalte hain.
    - Card ki entry animation fill-mode:backwards hai — khatam hote hi transform
      saaf ho jata hai, is liye autocomplete dropdown kabhi clip nahi hota.
--}}
<body class="login-bg min-h-dvh font-sans text-slate-100 antialiased">
@php
    $stationNameEn = app(\App\Services\System\SettingService::class)->get('station_name_en') ?: config('app.name');
    $omcBrandName = app(\App\Services\System\SettingService::class)->get('omc_brand_name');
@endphp

<div class="relative flex min-h-dvh items-center justify-center overflow-hidden px-4 py-10 sm:px-6">
    {{-- Animated ambient glow orbs (decorative only) --}}
    <div class="orb orb-1" aria-hidden="true"></div>
    <div class="orb orb-2" aria-hidden="true"></div>
    <div class="orb orb-3" aria-hidden="true"></div>
    <div class="pointer-events-none absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-white/40 to-transparent" aria-hidden="true"></div>

    {{-- Centered column: brand + card (440px) --}}
    <div class="relative z-10 flex w-full flex-col items-center" style="max-width: 440px;">

        {{-- Brand header --}}
        <div class="mb-7 text-center">
            <div class="login-logo mx-auto flex h-[5.5rem] w-[5.5rem] items-center justify-center rounded-[1.9rem] bg-gradient-to-br from-vital-primary via-[#e0331f] to-vital-darkred text-5xl ring-1 ring-white/30" aria-hidden="true">⛽</div>
            <h1 class="mt-5 text-3xl font-black tracking-tight text-white">{{ $stationNameEn }}</h1>
            @if ($omcBrandName)
                <p class="mt-1.5 text-xs font-bold uppercase tracking-[0.3em] text-amber-300/90">{{ $omcBrandName }}</p>
            @endif
            <p class="mt-2 text-base text-slate-400">Sign in to continue to your station ERP</p>
        </div>

        {{-- 3D glass card --}}
        <div class="login-card p-7 sm:p-9">
            @include('partials.flash')

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                <div class="field-3d mb-5">
                    <label for="email" class="mb-2 block text-center text-sm font-bold text-slate-200">Email address</label>
                    <div class="relative">
                        <span class="login-field-icon" aria-hidden="true">✉️</span>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="login-input @error('email') !border-red-400 @enderror"
                               required autofocus autocomplete="username" maxlength="150" placeholder="you@station.pk">
                    </div>
                    @error('email')
                        <p class="mt-2 text-center text-xs font-semibold text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="field-3d mb-5">
                    <label for="password" class="mb-2 block text-center text-sm font-bold text-slate-200">Password</label>
                    <div class="relative">
                        <span class="login-field-icon" aria-hidden="true">🔒</span>
                        <input type="password" id="password" name="password"
                               class="login-input @error('password') !border-red-400 @enderror"
                               required autocomplete="current-password" placeholder="••••••••">
                    </div>
                    @error('password')
                        <p class="mt-2 text-center text-xs font-semibold text-red-300">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-7 flex items-center justify-between gap-3">
                    <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-300">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                               class="h-[1.1rem] w-[1.1rem] rounded border-white/30 bg-white/10 text-vital-primary focus:ring-vital-primary">
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm font-semibold text-amber-300 transition hover:text-amber-200 hover:underline">Forgot password?</a>
                </div>

                <button type="submit" class="btn-3d btn-3d-primary btn-login w-full py-4 text-lg">
                    Sign In
                </button>

                <div class="my-6 flex items-center gap-3">
                    <div class="h-px flex-1 bg-white/15"></div>
                    <span class="text-[11px] font-bold uppercase tracking-widest text-slate-400">یا / OR</span>
                    <div class="h-px flex-1 bg-white/15"></div>
                </div>

                <a href="{{ route('pin.login') }}" class="btn-3d btn-3d-ghost btn-login w-full py-3.5 text-base !text-slate-900">
                    <span>⛽</span>
                    <span>کیشئر فوری پن لاگ اِن (Cashier PIN)</span>
                </a>
            </form>
        </div>

        <p class="mt-7 text-center text-xs text-slate-500">
            Authorised personnel only. All activity is logged.
        </p>
    </div>
</div>
</body>
</html>
