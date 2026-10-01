<!DOCTYPE html>
<html lang="ur" dir="ltr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'لاگ اِن — مہر فلنگ اسٹیشن (وائٹل پیٹرولیم)' }}</title>
    {{-- Admin Settings → Theme ke rang (CSS variables) --}}
    @include('partials.theme')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-gradient-to-br from-navy-950 via-slate-950 to-navy-900 font-sans text-slate-800 antialiased dark:text-slate-200">
    <div class="min-h-full">
        {{ $slot ?? '' }}
        @yield('content')
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>
