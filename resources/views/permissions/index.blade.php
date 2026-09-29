@extends('layouts.app')

@section('title', 'Permissions')
@section('breadcrumb')
    <li class="breadcrumb-item active">Permissions</li>
@endsection

@section('content')
    <h1 class="h4 mb-3">Permissions</h1>
    <p class="text-muted">
        Every permission in the system and which roles hold it. Permissions are granted on the
        <a href="{{ route('roles.index') }}">Roles</a> screen.
    </p>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Permission</th>
                        <th>Description</th>
                        @foreach ($roles as $role)
                            <th class="text-center" title="{{ $role->label }}">{{ $role->name }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($grouped as $module => $permissions)
                        <tr class="table-light">
                            <td colspan="{{ 2 + $roles->count() }}">
                                <strong>{{ ucfirst($module) }}</strong>
                            </td>
                        </tr>
                        @foreach ($permissions as $permission)
                            <tr>
                                <td><code>{{ $permission->name }}</code></td>
                                <td class="text-muted small">{{ $permission->label }}</td>
                                @foreach ($roles as $role)
                                    <td class="text-center">
                                        @if (isset($rolePermissionMap[$role->name][$permission->name]))
                                            <span class="text-success fw-bold" aria-label="granted">✓</span>
                                        @else
                                            <span class="text-muted" aria-label="not granted">—</span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="{{ 2 + $roles->count() }}" class="text-center text-muted py-4">
                                No permissions found. Run <code>php artisan db:seed</code> first.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
