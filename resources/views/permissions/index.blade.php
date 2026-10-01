@extends('layouts.app')

@section('title', __('admin.permissions.title'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.permissions.title') }}</li>
@endsection

{{--
    Permissions — 2026 redesign.
    Page head centered; module-wise matrix .table-3d wrapper me, tamam cells
    center. Sirf read-only overview hai — permissions Roles screen se
    grant hoti hain.
--}}
@section('content')
    <div class="page-head">
        <h1>🔑 {{ __('admin.permissions.title') }}</h1>
        <p>
            {!! __('admin.permissions.intro', ['link' => '<a href="' . route('roles.index') . '" class="font-bold text-vital-primary hover:underline">' . __('admin.roles.title') . '</a>']) !!}
        </p>
    </div>

    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.permissions.permission') }}</th>
                        <th>{{ __('admin.common.description') }}</th>
                        @foreach ($roles as $role)
                            <th title="{{ $role->label }}">{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grouped as $module => $permissions)
                        <tr>
                            <td colspan="{{ 2 + $roles->count() }}" class="!bg-gradient-to-r !from-vital-primary/10 !to-vital-darkred/10 py-2.5 text-xs font-black uppercase tracking-widest text-vital-darkred dark:!from-vital-primary/20 dark:!to-vital-darkred/20 dark:text-red-300">
                                {{ ucfirst($module) }}
                            </td>
                        </tr>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td><code class="rounded bg-slate-900/5 px-1.5 py-0.5 text-xs font-bold text-slate-600 dark:bg-white/10 dark:text-slate-300">{{ $permission->name }}</code></td>
                                <td class="text-sm text-slate-500">{{ $permission->label }}</td>
                                @foreach ($roles as $role)
                                    <td>
                                        @if (isset($rolePermissionMap[$role->name][$permission->name]))
                                            <span class="text-base font-black text-emerald-500" aria-label="{{ __('admin.permissions.granted') }}">✓</span>
                                        @else
                                            <span class="text-slate-300 dark:text-slate-600" aria-label="{{ __('admin.permissions.not_granted') }}">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ 2 + $roles->count() }}" class="py-10 text-slate-400">
                                {!! __('admin.permissions.none', ['cmd' => '<code class="rounded bg-slate-900/5 px-1.5 py-0.5 text-xs dark:bg-white/10">php artisan db:seed</code>']) !!}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
