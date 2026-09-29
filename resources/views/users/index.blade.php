@extends('layouts.app')

@section('title', 'Users')
@section('breadcrumb')
    <li class="breadcrumb-item active">Users</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Users</h1>
        @can('user.create')
            <a href="{{ route('users.create') }}" class="btn btn-primary">Add User</a>
        @endcan
    </div>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Employee Code</th>
                        <th>Email</th>
                        <th>Roles</th>
                        <th>Last Login</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td class="fw-semibold">{{ $user->name }}</td>
                            <td>{{ $user->employee_code ?: '—' }}</td>
                            <td class="text-muted">{{ $user->email }}</td>
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="badge bg-secondary">{{ $role->label }}</span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="text-muted small">
                                {{ $user->last_login_at?->format('d M Y H:i') ?: 'Never' }}
                            </td>
                            <td>
                                <span class="badge bg-{{ $user->isActive() ? 'success' : 'danger' }}">
                                    {{ $user->status }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @can('user.edit')
                                    <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                                @can('user.delete')
                                    @if ($user->id !== auth()->id())
                                        <form method="POST" action="{{ route('users.destroy', $user) }}"
                                              class="d-inline"
                                              onsubmit="return confirm('Delete user {{ $user->name }}?');">
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
                            <td colspan="7" class="text-center text-muted py-4">No users yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $users->links() }}</div>
@endsection
