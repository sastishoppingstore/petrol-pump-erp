@extends('layouts.app')

@section('title', 'Roles')
@section('breadcrumb')
    <li class="breadcrumb-item active">Roles</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Roles</h1>
        @can('role.create')
            <a href="{{ route('roles.create') }}" class="btn btn-primary">Add Role</a>
        @endcan
    </div>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Role</th>
                        <th>Name</th>
                        <th>Permissions</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($roles as $role)
                        <tr>
                            <td class="fw-semibold">
                                {{ $role->label }}
                                @if ($role->is_super_admin)
                                    <span class="badge bg-danger ms-1">Super Admin</span>
                                @endif
                            </td>
                            <td class="text-muted"><code>{{ $role->name }}</code></td>
                            <td>{{ $role->permissions_count }}</td>
                            <td>{{ $role->users_count }}</td>
                            <td>
                                <span class="badge bg-{{ $role->status === 'ACTIVE' ? 'success' : 'secondary' }}">
                                    {{ $role->status }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @can('role.edit')
                                    <a href="{{ route('roles.edit', $role) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                                @can('role.delete')
                                    @if (! $role->is_super_admin)
                                        <form method="POST" action="{{ route('roles.destroy', $role) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete role {{ $role->label }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                No roles found. Run <code>php artisan db:seed</code> to load the default matrix.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $roles->links() }}</div>
@endsection
