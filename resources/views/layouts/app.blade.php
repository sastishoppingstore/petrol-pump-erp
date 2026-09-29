<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
<div class="erp-layout">

    {{-- ---------- Sidebar ---------- --}}
    <aside class="erp-sidebar">
        <div class="erp-sidebar-brand">
            <span aria-hidden="true">⛽</span>
            <span>{{ config('app.name') }}</span>
        </div>

        <nav>
            <ul class="erp-nav">
                @foreach (\App\Support\SidebarNav::itemsFor(auth()->user()) as $item)
                    @if ($item['type'] === 'section')
                        <li class="erp-nav-section">{{ $item['label'] }}</li>
                    @elseif ($item['permission'] === null || auth()->user()->hasPermission($item['permission']))
                        <li>
                            <a href="{{ $item['route'] ? route($item['route']) : '#' }}"
                               class="erp-nav-link {{ request()->routeIs($item['match'] ?? '___none___') ? 'active' : '' }}">
                                <span aria-hidden="true">{!! $item['icon'] !!}</span>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        </li>
                    @endif
                @endforeach
            </ul>
        </nav>
    </aside>

    {{-- ---------- Main ---------- --}}
    <div class="erp-main">
        <header class="erp-topbar erp-no-print">
            <button type="button"
                    class="btn btn-sm btn-outline-secondary erp-hamburger"
                    data-erp-sidebar-toggle
                    aria-label="Toggle navigation">
                ☰
            </button>

            <nav aria-label="breadcrumb" class="me-auto">
                <ol class="breadcrumb mb-0 py-0 small">
                    <li class="breadcrumb-item">
                        <a href="{{ route('dashboard') }}">Home</a>
                    </li>
                    @yield('breadcrumb')
                </ol>
            </nav>

            {{-- Branch switcher --}}
            @php
                $branches = app(\App\Services\Security\BranchScopeService::class)
                    ->selectableBranches(auth()->user());
            @endphp
            @if ($branches->count() > 0)
                <form method="POST" action="{{ route('branch.switch') }}" class="d-flex align-items-center">
                    @csrf
                    <label for="branch-switcher" class="visually-hidden">Active branch</label>
                    <select id="branch-switcher"
                            name="branch_id"
                            class="form-select form-select-sm me-2"
                            onchange="this.form.submit()">
                        @if (auth()->user()->isSuperAdmin())
                            <option value="">All branches</option>
                        @endif
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}"
                                @selected((int) session('active_branch_id') === $branch->id)>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    <noscript><button type="submit" class="btn btn-sm btn-primary">Switch</button></noscript>
                </form>
            @endif

            {{-- Notifications bell (route arrives with the notifications phase) --}}
            @if (\Illuminate\Support\Facades\Route::has('notifications.index'))
                <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-secondary position-relative"
                   title="Notifications">
                    🔔
                    @if (($unreadNotificationCount ?? 0) > 0)
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            {{ $unreadNotificationCount }}
                        </span>
                    @endif
                </a>
            @endif

            {{-- User menu --}}
            <div class="dropdown">
                <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button"
                        data-bs-toggle="dropdown" aria-expanded="false">
                    {{ auth()->user()->name }}
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <span class="dropdown-item-text small text-muted">
                            {{ auth()->user()->email }}<br>
                            <strong>{{ auth()->user()->roleLabel() }}</strong>
                        </span>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item">Sign out</button>
                        </form>
                    </li>
                </ul>
            </div>
        </header>

        <main class="erp-content">
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
</div>

@stack('scripts')
</body>
</html>
