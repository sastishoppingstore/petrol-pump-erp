<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Choose a new password — {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 dark:bg-slate-950">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-sm">
        <div class="rounded-lg border border-slate-200 bg-white p-6 shadow-card dark:border-slate-800 dark:bg-slate-900">
            <h1 class="mb-5 text-base font-bold">Choose a new password</h1>

            @include('partials.flash')

            <form method="POST" action="{{ route('password.update') }}" novalidate>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-4">
                    <label for="email" class="mb-1 block text-sm font-medium">Email address</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}"
                           class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 @error('email') border-red-500 @enderror"
                           required maxlength="150">
                    @error('email')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label for="password" class="mb-1 block text-sm font-medium">New password</label>
                    <input type="password" id="password" name="password"
                           class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800 @error('password') border-red-500 @enderror"
                           required autocomplete="new-password">
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-5">
                    <label for="password_confirmation" class="mb-1 block text-sm font-medium">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="w-full rounded-md border-slate-300 dark:border-slate-700 dark:bg-slate-800"
                           required autocomplete="new-password">
                </div>

                <button type="submit" class="w-full rounded-md bg-navy-800 py-2 font-semibold text-white hover:bg-navy-900 dark:bg-navy-700">
                    Update password
                </button>
            </form>
        </div>
    </div>
</div>
</body>
</html>
