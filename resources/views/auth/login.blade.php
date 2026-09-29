<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body class="bg-light">
<div class="container" style="max-width: 420px;">
    <div class="d-flex flex-column justify-content-center min-vh-100 py-4">

        <div class="text-center mb-4">
            <div class="fs-1">⛽</div>
            <h1 class="h4 mb-1">{{ config('app.name') }}</h1>
            <p class="text-muted small mb-0">Sign in to continue</p>
        </div>

        <div class="erp-card p-4">
            @include('partials.flash')

            <form method="POST" action="{{ route('login.store') }}" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="email" class="form-label">Email address</label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="form-control @error('email') is-invalid @enderror"
                           required
                           autofocus
                           autocomplete="username"
                           maxlength="150">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password"
                           id="password"
                           name="password"
                           class="form-control @error('password') is-invalid @enderror"
                           required
                           autocomplete="current-password">
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox"
                               class="form-check-input"
                               id="remember"
                               name="remember"
                               value="1"
                               @checked(old('remember'))>
                        <label class="form-check-label small" for="remember">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="small">Forgot password?</a>
                </div>

                <button type="submit" class="btn btn-primary w-100 erp-pos-btn">Sign in</button>
            </form>
        </div>

        <p class="text-center text-muted small mt-3 mb-0">
            Authorised personnel only. All activity is logged.
        </p>
    </div>
</div>
</body>
</html>
