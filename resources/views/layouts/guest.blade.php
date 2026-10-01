<!DOCTYPE html>
<html lang="ur" dir="ltr" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- PWA / app shell metas --}}
    <meta name="theme-color" content="#0f172a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="Mehar ERP">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="192x192" href="{{ asset('icons/icon-192.png') }}">
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
    <script>
        // PWA service worker (app layout wala hi shell — sirf static cache).
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function () {
                try {
                    navigator.serviceWorker.register('/sw.js').catch(function () { /* ignore */ });
                } catch (e) { /* ignore */ }
            });
        }
    </script>
</body>
</html>
