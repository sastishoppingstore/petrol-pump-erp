@extends('layouts.app')

@section('title', 'Branches')
@section('breadcrumb')
    <li class="breadcrumb-item active">Branches</li>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h4 mb-0">Branches</h1>
        @can('branch.create')
            <a href="{{ route('branches.create') }}" class="btn btn-primary">Add Branch</a>
        @endcan
    </div>

    <div class="erp-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Location</th>
                        <th>Phone</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($branches as $branch)
                        <tr>
                            <td class="fw-semibold">{{ $branch->code }}</td>
                            <td>{{ $branch->name }}</td>
                            <td class="text-muted">
                                {{ collect([$branch->city, $branch->state])->filter()->join(', ') ?: '—' }}
                            </td>
                            <td>{{ $branch->phone ?: '—' }}</td>
                            <td>{{ $branch->users_count }}</td>
                            <td>
                                <span class="badge bg-{{ $branch->isActive() ? 'success' : 'secondary' }}">
                                    {{ $branch->status }}
                                </span>
                            </td>
                            <td class="text-end text-nowrap">
                                @can('branch.edit')
                                    <a href="{{ route('branches.edit', $branch) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
                                @endcan
                                @can('branch.delete')
                                    <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete branch {{ $branch->name }}? This cannot be undone.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No branches yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">{{ $branches->links() }}</div>
@endsection
