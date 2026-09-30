<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot password — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h1 class="mb-1 text-base font-bold">Forgot your password?</h1>
            <p class="mb-5 text-sm text-slate-500">
                Enter the email address registered with your account and we will send you a reset link.
            </p>

            @include('partials.flash')

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf
                <div class="mb-4">
                    <label for="email" class="mb-1 block text-sm font-medium">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 @error('email') border-red-500 @enderror"
                           required autofocus maxlength="150">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <button type="submit" class="w-full rounded-md bg-navy-800 py-2 font-semibold text-white hover:bg-navy-900 dark:bg-navy-700">
                    Send reset link
                </button>
            </form>

            <div class="mt-4 text-center">
                <a href="{{ route('login') }}" class="text-sm text-navy-700 hover:underline dark:text-slate-300">Back to sign in</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
