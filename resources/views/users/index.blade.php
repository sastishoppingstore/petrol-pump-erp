@extends('layouts.app')

@section('title', __('admin.users.title'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.users.title') }}</li>
@endsection

{{--
    Users — 2026 redesign.
    Page head centered, users 3D cards grid + barri screen par centered
    table (.table-3d). Tamam routes, @can gates aur delete form ka
    confirm hook pehle jaisa hi hai.
--}}
@section('content')
    <div class="page-head">
        <h1>👥 {{ __('admin.users.title') }}</h1>
        <p>{{ trans_choice('admin.users.count', $users->total()) }} • {{ __('admin.users.subtitle') }}</p>
        @can('user.create')
            <div class="page-actions">
                <a href="{{ route('users.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> {{ __('admin.users.add_user') }}
                </a>
            </div>
        @endcan
    </div>

    @forelse ($users as $user)
        @if ($loop->first)
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @endif

        <article class="glass-card card-3d relative overflow-hidden p-5 text-center">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-vital-primary to-vital-darkred" aria-hidden="true"></div>

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-vital-primary to-vital-darkred text-xl font-black text-white shadow-lg" aria-hidden="true">
                {{ strtoupper(substr($user->name, 0, 1)) }}
            </div>

            <h2 class="mt-3 truncate text-base font-black text-slate-900 dark:text-white">{{ $user->name }}</h2>
            <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>

            <div class="mt-3 flex flex-wrap items-center justify-center gap-1.5">
                @forelse ($user->roles as $role)
                    <span class="rounded-full bg-slate-900/5 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $role->label }}</span>
                @empty
                    <span class="text-xs text-slate-400">{{ __('admin.users.no_role') }}</span>
                @endforelse
            </div>

            <dl class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('admin.common.employee_code') }}</dt>
                    <dd class="mt-0.5 text-sm font-bold text-slate-700 dark:text-slate-200">{{ $user->employee_code ?: '—' }}</dd>
                </div>
                <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('admin.common.last_login') }}</dt>
                    <dd class="mt-0.5 text-xs font-bold text-slate-700 dark:text-slate-200">{{ $user->last_login_at?->format('d M Y H:i') ?: __('admin.common.never') }}</dd>
                </div>
            </dl>

            <div class="mt-4 flex justify-center">
                <span class="pill-status {{ $user->isActive() ? 'pill-active' : 'pill-inactive' }}">
                    <span class="dot" aria-hidden="true"></span>{{ $user->status }}
                </span>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                @can('user.edit')
                    <a href="{{ route('users.edit', $user) }}" class="btn-3d btn-3d-ghost btn-3d-sm">✏️ {{ __('ui.actions.edit') }}</a>
                @endcan
                @can('user.delete')
                    @if ($user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                              onsubmit="return confirm('Delete user {{ $user->name }}?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-3d btn-3d-sm bg-gradient-to-b from-red-400 to-red-600 shadow">{{ __('ui.actions.delete') }}</button>
                        </form>
                    @endif
                @endcan
            </div>
        </article>

        @if ($loop->last)
            </div>
        @endif
    @empty
        <div class="glass-card p-10 text-center">
            <div class="text-4xl" aria-hidden="true">👥</div>
            <p class="mt-3 font-semibold text-slate-600 dark:text-slate-300">{{ __('admin.users.none') }}</p>
            @can('user.create')
                <a href="{{ route('users.create') }}" class="btn-3d btn-3d-primary mt-4">{{ __('admin.common.add_first') }}</a>
            @endcan
        </div>
    @endforelse

    {{-- ================= Centered data table (barri screens) ================= --}}
    @if ($users->isNotEmpty())
        <div class="glass-card mt-6 hidden overflow-hidden xl:block">
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('admin.common.name') }}</th>
                            <th>{{ __('admin.common.employee_code') }}</th>
                            <th>{{ __('admin.common.email') }}</th>
                            <th>{{ __('admin.common.roles') }}</th>
                            <th>{{ __('admin.common.last_login') }}</th>
                            <th>{{ __('admin.common.status') }}</th>
                            <th>{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $user)
                            <tr>
                                <td class="font-bold text-slate-800 dark:text-slate-100">{{ $user->name }}</td>
                                <td>{{ $user->employee_code ?: '—' }}</td>
                                <td class="text-slate-500">{{ $user->email }}</td>
                                <td>
                                    @forelse ($user->roles as $role)
                                        <span class="rounded-full bg-slate-900/5 px-2.5 py-0.5 text-[11px] font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $role->label }}</span>
                                    @empty
                                        <span class="text-slate-400">—</span>
                                    @endforelse
                                </td>
                                <td class="text-slate-500">{{ $user->last_login_at?->format('d M Y H:i') ?: __('admin.common.never') }}</td>
                                <td>
                                    <span class="pill-status {{ $user->isActive() ? 'pill-active' : 'pill-inactive' }}">
                                        <span class="dot" aria-hidden="true"></span>{{ $user->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        @can('user.edit')
                                            <a href="{{ route('users.edit', $user) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.edit') }}</a>
                                        @endcan
                                        @can('user.delete')
                                            @if ($user->id !== auth()->id())
                                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                                      onsubmit="return confirm('Delete user {{ $user->name }}?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn-3d btn-3d-sm bg-gradient-to-b from-red-400 to-red-600 shadow">{{ __('ui.actions.delete') }}</button>
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <div class="mt-6">{{ $users->links() }}</div>

    {{-- ================= Floating Action Button ================= --}}
    @can('user.create')
        <a href="{{ route('users.create') }}" class="fab-3d" title="{{ __('admin.users.add_user') }}">
            <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('admin.users.add_user') }}
        </a>
    @endcan
@endsection
