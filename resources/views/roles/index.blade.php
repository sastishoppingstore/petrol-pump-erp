@extends('layouts.app')

@section('title', __('admin.roles.title'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.roles.title') }}</li>
@endsection

{{--
    Roles — 2026 redesign.
    Page head centered, roles 3D cards grid + barri screen par centered
    table (.table-3d). Tamam routes, @can gates aur delete form ka
    confirm hook pehle jaisa hi hai.
--}}
@section('content')
    <div class="page-head">
        <h1>🛡️ {{ __('admin.roles.title') }}</h1>
        <p>{{ trans_choice('admin.roles.count', $roles->total()) }}</p>
        @can('role.create')
            <div class="page-actions">
                <a href="{{ route('roles.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> {{ __('admin.roles.add_role') }}
                </a>
            </div>
        @endcan
    </div>

    @forelse ($roles as $role)
        @if ($loop->first)
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @endif

        <article class="glass-card card-3d relative overflow-hidden p-5 text-center">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 {{ $role->is_super_admin ? 'bg-gradient-to-r from-red-500 to-rose-700' : 'bg-gradient-to-r from-vital-primary to-vital-darkred' }}" aria-hidden="true"></div>

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl {{ $role->is_super_admin ? 'bg-gradient-to-br from-red-500 to-rose-700' : 'bg-gradient-to-br from-vital-primary to-vital-darkred' }} text-2xl shadow-lg" aria-hidden="true">🛡️</div>

            <h2 class="mt-3 text-base font-black text-slate-900 dark:text-white">{{ $role->label }}</h2>
            @if ($role->is_super_admin)
                <span class="mt-1 inline-flex rounded-full bg-red-500/15 px-2.5 py-0.5 text-[11px] font-black uppercase tracking-wide text-red-600 dark:text-red-400">{{ __('admin.roles.super_admin') }}</span>
            @endif
            <p class="mt-1"><code class="rounded-lg bg-slate-900/5 px-2 py-0.5 text-xs font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">{{ $role->name }}</code></p>

            <dl class="mt-4 grid grid-cols-2 gap-2 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('admin.common.permissions') }}</dt>
                    <dd class="tabular mt-0.5 text-lg font-black text-slate-800 dark:text-slate-100">{{ $role->permissions_count }}</dd>
                </div>
                <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">{{ __('admin.common.users') }}</dt>
                    <dd class="tabular mt-0.5 text-lg font-black text-slate-800 dark:text-slate-100">{{ $role->users_count }}</dd>
                </div>
            </dl>

            <div class="mt-4 flex justify-center">
                <span class="pill-status {{ $role->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }}">
                    <span class="dot" aria-hidden="true"></span>{{ $role->status }}
                </span>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                @can('role.edit')
                    <a href="{{ route('roles.edit', $role) }}" class="btn-3d btn-3d-ghost btn-3d-sm">✏️ {{ __('ui.actions.edit') }}</a>
                @endcan
                @can('role.delete')
                    @if (! $role->is_super_admin)
                        <form method="POST" action="{{ route('roles.destroy', $role) }}"
                              onsubmit="return confirm('Delete role {{ $role->label }}?');">
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
            <div class="text-4xl" aria-hidden="true">🛡️</div>
            <p class="mt-3 font-semibold text-slate-600 dark:text-slate-300">
                {!! __('admin.roles.none', ['cmd' => '<code class="rounded bg-slate-900/5 px-1.5 py-0.5 text-xs dark:bg-white/10">php artisan db:seed</code>']) !!}
            </p>
        </div>
    @endforelse

    {{-- ================= Centered data table (barri screens) ================= --}}
    @if ($roles->isNotEmpty())
        <div class="glass-card mt-6 hidden overflow-hidden xl:block">
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>{{ __('admin.roles.role') }}</th>
                            <th>{{ __('admin.common.name') }}</th>
                            <th>{{ __('admin.common.permissions') }}</th>
                            <th>{{ __('admin.common.users') }}</th>
                            <th>{{ __('admin.common.status') }}</th>
                            <th>{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $role)
                            <tr>
                                <td class="font-bold text-slate-800 dark:text-slate-100">
                                    {{ $role->label }}
                                    @if ($role->is_super_admin)
                                        <span class="ml-1 rounded-full bg-red-500/15 px-2 py-0.5 text-[10px] font-black uppercase tracking-wide text-red-600 dark:text-red-400">{{ __('admin.roles.super_admin') }}</span>
                                    @endif
                                </td>
                                <td><code class="rounded bg-slate-900/5 px-1.5 py-0.5 text-xs font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">{{ $role->name }}</code></td>
                                <td class="tabular font-bold">{{ $role->permissions_count }}</td>
                                <td class="tabular font-bold">{{ $role->users_count }}</td>
                                <td>
                                    <span class="pill-status {{ $role->status === 'ACTIVE' ? 'pill-active' : 'pill-inactive' }}">
                                        <span class="dot" aria-hidden="true"></span>{{ $role->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        @can('role.edit')
                                            <a href="{{ route('roles.edit', $role) }}" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.edit') }}</a>
                                        @endcan
                                        @can('role.delete')
                                            @if (! $role->is_super_admin)
                                                <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                                      onsubmit="return confirm('Delete role {{ $role->label }}?');">
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

    <div class="mt-6">{{ $roles->links() }}</div>

    {{-- ================= Floating Action Button ================= --}}
    @can('role.create')
        <a href="{{ route('roles.create') }}" class="fab-3d" title="{{ __('admin.roles.add_role') }}">
            <span class="text-xl leading-none" aria-hidden="true">＋</span> {{ __('admin.roles.add_role') }}
        </a>
    @endcan
@endsection
