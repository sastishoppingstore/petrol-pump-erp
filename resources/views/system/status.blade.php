<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>System Status — {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
<div class="container py-5" style="max-width: 720px;">
    <div class="erp-card p-4 mb-3">
        <h1 class="h4 mb-1">{{ config('app.name') }}</h1>
        <p class="text-muted mb-4">Environment check — setup verification page.</p>

        @if (! $dbConnected)
            <div class="alert alert-danger">
                <strong>Database unavailable.</strong>
                The application cannot reach its database. Check the <code>DB_*</code> values in <code>.env</code>.
            </div>
        @else
            <div class="alert alert-success">
                <strong>Database connected.</strong> Migrations can run.
            </div>
        @endif

        <table class="table table-sm mb-0">
            <tbody>
                <tr><th scope="row" style="width: 40%;">Application</th><td>{{ config('app.name') }}</td></tr>
                <tr><th scope="row">Environment</th><td>{{ app()->environment() }}</td></tr>
                <tr><th scope="row">Laravel</th><td>{{ $laravelVersion }}</td></tr>
                <tr><th scope="row">PHP</th><td>{{ $phpVersion }}</td></tr>
                <tr><th scope="row">Database driver</th><td>{{ $dbDriver }}</td></tr>
                <tr><th scope="row">Database name</th><td>{{ $dbName }}</td></tr>
                <tr><th scope="row">DB connection</th><td>{{ $dbConnected ? 'OK' : 'FAILED' }}</td></tr>
            </tbody>
        </table>
    </div>

    <p class="text-muted small mb-0">
        Setup verification only. This page is replaced by the login screen in Phase 1.
    </p>
</div>
</body>
</html>
