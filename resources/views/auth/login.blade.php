<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-sm">

        <div class="mb-6 text-center">
            <div class="text-4xl">⛽</div>
            <h1 class="mt-2 text-lg font-bold">{{ config('app.name') }}</h1>
            <p class="mt-1 text-sm text-slate-500">Sign in to continue</p>
        </div>

        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-card dark:border-slate-800 dark:bg-slate-900">
            @include('partials.flash')

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                <div class="mb-4">
                    <label for="email" class="mb-1 block text-sm font-medium">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 @error('email') border-red-500 @enderror"
                           required autofocus autocomplete="username" maxlength="150">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="mb-1 block text-sm font-medium">Password</label>
                    <input type="password" id="password" name="password"
                           class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 @error('password') border-red-500 @enderror"
                           required autocomplete="current-password">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5 flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="remember" value="1" @checked(old('remember'))
                               class="rounded border-slate-300 text-navy-700 focus:ring-navy-600">
                        Remember me
                    </label>
                    <a href="{{ route('password.request') }}" class="text-sm text-navy-700 hover:underline dark:text-slate-300">Forgot password?</a>
                </div>

                <button type="submit" class="w-full rounded-md bg-navy-800 py-2.5 font-semibold text-white hover:bg-navy-900 dark:bg-navy-700 dark:hover:bg-navy-800">
                    Sign in
                </button>
            </form>
        </div>

        <p class="mt-5 text-center text-xs text-slate-500">
            Authorised personnel only. All activity is logged.
        </p>
    </div>
</div>
</body>
</html>
