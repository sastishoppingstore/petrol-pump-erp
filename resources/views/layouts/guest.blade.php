<!DOCTYPE html>
<html lang="ur" dir="ltr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'لاگ اِن — مہر فلنگ اسٹیشن (وائٹل پیٹرولیم)' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
    <div class="min-h-full">
        {{ $slot ?? '' }}
        @yield('content')
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
