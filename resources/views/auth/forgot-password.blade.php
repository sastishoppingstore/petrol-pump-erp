<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset password — {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
<div class="container" style="max-width: 420px;">
    <div class="d-flex flex-column justify-content-center min-vh-100 py-4">
        <div class="erp-card p-4">
            <h1 class="h5 mb-3">Forgot your password?</h1>
            <p class="text-muted small">
                Enter the email address registered with your account and we will send you a reset link.
            </p>

            @include('partials.flash')

            <form method="POST" action="{{ route('password.email') }}" novalidate>
                @csrf
                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email" id="email" name="email"
                           value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required autofocus maxlength="150">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary w-100">Send reset link</button>
            </form>

            <div class="text-center mt-3">
                <a href="{{ route('login') }}" class="small">Back to sign in</a>
            </div>
        </div>
    </div>
</div>
</body>
</html>
