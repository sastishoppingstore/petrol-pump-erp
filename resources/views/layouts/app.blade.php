<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    {{-- Admin Settings → Theme ke rang (CSS variables) — har page se pehle --}}
    @include('partials.theme')
    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/dashboard-motion.js'])
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased dark:bg-slate-950 dark:text-slate-200">
@php
    // Station ka naam Admin → Settings se aata hai (admin panel se editable).
    $stationNameEn = app(\App\Services\System\SettingService::class)->get('station_name_en') ?: config('app.name');
@endphp
<div class="min-h-full">

@if (! ($hideChrome ?? false))
    {{-- ================= Sidebar ================= --}}
    <aside class="app-sidebar fixed inset-y-0 left-0 z-40 w-64 overflow-y-auto bg-gradient-to-b from-navy-800 via-navy-900 to-navy-950 text-slate-300 shadow-2xl">
        <div class="flex min-h-[60px] items-center gap-2.5 border-b border-white/10 px-4 py-2">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-vital-primary to-vital-darkred text-lg shadow-glow" aria-hidden="true">⛽</span>
            <span class="min-w-0 leading-tight">
                <span class="block truncate text-[15px] font-bold text-white">{{ $stationNameEn }}</span>
                <span class="block text-[10px] font-semibold uppercase tracking-widest text-slate-400">Petrol Pump ERP</span>
            </span>
        </div>

        <nav class="py-2">
            @foreach (\App\Support\SidebarNav::itemsFor(auth()->user()) as $item)
                @if ($item['type'] === 'section')
                    <div class="px-4 pb-1 pt-4 text-[11px] font-bold uppercase tracking-wider text-slate-500">
                        {{ $item['label'] }}
                    </div>
                @elseif ($item['permission'] === null || auth()->user()->hasPermission($item['permission']))
                    <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                       @class([
                           'flex items-center gap-2.5 border-l-[3px] border-transparent px-4 py-2 text-sm no-underline transition',
                           'border-amberx-500 bg-navy-800 font-semibold text-white' => request()->routeIs($item['match'] ?? '___none___'),
                           'hover:bg-navy-800 hover:text-white' => ! request()->routeIs($item['match'] ?? '___none___'),
                       ])>
                        <span aria-hidden="true">{{ $item['icon'] }}</span>
                        <span>{{ $item['label'] }}</span>
                    </a>
                @endif
            @endforeach

            {{-- Amanat & Document Vault — is module ke links (SidebarNav pattern hi follow) --}}
            @if (auth()->user()->hasPermission(\App\Support\PermissionList::CUSTOMER_VIEW))
                <a href="{{ route('amanat.index') }}"
                   @class([
                       'flex items-center gap-2.5 border-l-[3px] border-transparent px-4 py-2 text-sm no-underline transition',
                       'border-amberx-500 bg-navy-800 font-semibold text-white' => request()->routeIs('amanat.*'),
                       'hover:bg-navy-800 hover:text-white' => ! request()->routeIs('amanat.*'),
                   ])>
                    <span aria-hidden="true">💳</span>
                    <span>Amanat Deposits</span>
                </a>
            @endif
            @if (auth()->user()->hasPermission(\App\Support\PermissionList::SETTINGS_VIEW))
                <a href="{{ route('documents.index') }}"
                   @class([
                       'flex items-center gap-2.5 border-l-[3px] border-transparent px-4 py-2 text-sm no-underline transition',
                       'border-amberx-500 bg-navy-800 font-semibold text-white' => request()->routeIs('documents.*'),
                       'hover:bg-navy-800 hover:text-white' => ! request()->routeIs('documents.*'),
                   ])>
                    <span aria-hidden="true">🗂️</span>
                    <span>Document Vault</span>
                </a>
            @endif
        </nav>
    </aside>

    <div class="app-sidebar-backdrop fixed inset-0 z-30 hidden bg-slate-900/50" data-sidebar-toggle></div>

    {{-- ================= Main ================= --}}
    <div class="lg:pl-64">
        <header class="app-topbar no-print sticky top-0 z-20 flex h-[60px] items-center gap-3 border-b border-slate-200/70 bg-white/85 px-4 shadow-sm backdrop-blur-md dark:border-slate-800 dark:bg-slate-900/85">
            <button type="button" class="rounded p-1.5 hover:bg-slate-100 lg:hidden dark:hover:bg-slate-800"
                    data-sidebar-toggle aria-label="Toggle navigation">☰</button>

            <nav aria-label="breadcrumb" class="me-auto">
                <ol class="flex items-center gap-1 text-sm text-slate-500">
                    <li><a href="{{ route('dashboard') }}" class="hover:text-navy-700 dark:hover:text-slate-300">Home</a></li>
                    @yield('breadcrumb')
                </ol>
            </nav>

            {{-- Branch switcher --}}
            @php $branches = app(\App\Services\Security\BranchScopeService::class)->selectableBranches(auth()->user()); @endphp
            @if ($branches->isNotEmpty())
                <form method="POST" action="{{ route('branch.switch') }}" class="flex items-center">
                    @csrf
                    <label for="branch-switcher" class="sr-only">Active branch</label>
                    <select id="branch-switcher" name="branch_id" onchange="this.form.submit()"
                            class="me-2 rounded-md border-slate-300 py-1 pl-2 pr-7 text-sm dark:border-slate-700 dark:bg-slate-800">
                        @if (auth()->user()->isSuperAdmin())
                            <option value="">All branches</option>
                        @endif
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) session('active_branch_id') === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-sm btn-primary">Switch</button></noscript>
                </form>
            @endif

            {{-- Notifications --}}
            @if (\Illuminate\Support\Facades\Route::has('notifications.index'))
                <a href="{{ route('notifications.index') }}" class="relative rounded p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800"
                   title="Notifications">
                    🔔
                    @if (($unreadNotificationCount ?? 0) > 0)
                        <span class="absolute -right-1 -top-1 rounded-full bg-red-600 px-1.5 text-[10px] font-bold text-white">
                            {{ $unreadNotificationCount }}
                        </span>
                    @endif
                </a>
            @endif

            {{-- User menu --}}
            <div class="relative" x-data="{ open: false }">
                <button type="button" @click="open = !open"
                        class="flex items-center gap-1 rounded-md border border-slate-300 px-2.5 py-1 text-sm dark:border-slate-700 dark:bg-slate-800">
                    {{ auth()->user()->name }}
                    <span aria-hidden="true">▾</span>
                </button>
                <div x-show="open" @click.outside="open = false" x-cloak
                     class="absolute right-0 mt-1 w-56 rounded-md border border-slate-200 bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800">
                    <div class="border-b border-slate-200 px-3 py-2 text-xs text-slate-500 dark:border-slate-700">
                        {{ auth()->user()->email }}<br>
                        <strong class="text-slate-700 dark:text-slate-300">{{ auth()->user()->roleLabel() }}</strong>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="w-full px-3 py-2 text-left text-sm hover:bg-slate-100 dark:hover:bg-slate-700">
                            Sign out
                        </button>
                    </form>
                </div>
            </div>
        </header>

        {{-- Cinematic particle background — sirf desktop par JS load karta hai,
             pointer-events none taake clicks kabhi block na hon. --}}
        <canvas id="fx-particles" class="pointer-events-none fixed inset-0 -z-10 hidden lg:block" aria-hidden="true"></canvas>

        {{-- Fluid content: mobile par full-width app feel, desktop par poori
             viewport width (max 1680px) — koi fixed mobile frame nahi. --}}
        <main class="print-area page-enter mx-auto w-full max-w-[1680px] p-5 lg:p-8">
            @include('partials.flash')
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
@else
    <main class="min-h-full">
        @include('partials.flash')
        {{ $slot ?? '' }}
        @yield('content')
    </main>
@endif
</div>

@livewireScripts
@stack('scripts')
</body>
</html>
