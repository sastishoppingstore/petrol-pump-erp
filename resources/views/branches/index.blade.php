@extends('layouts.app')

@section('title', 'Branches')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Branches</li>
@endsection

{{--
    Branches — 2026 redesign.
    Page head centered, branches 3D cards grid + barri screen par centered
    table (.table-3d). Tamam routes, @can gates aur delete form ka
    confirm hook pehle jaisa hi hai.
--}}
@section('content')
    <div class="page-head">
        <h1>🏢 Branches</h1>
        <p>{{ $branches->total() }} branch{{ $branches->total() === 1 ? '' : 'es' }} • Station ki tamam locations</p>
        @can('branch.create')
            <div class="page-actions">
                <a href="{{ route('branches.create') }}" class="btn-3d btn-3d-primary hidden lg:inline-flex">
                    <span aria-hidden="true">＋</span> Add Branch
                </a>
            </div>
        @endcan
    </div>

    @forelse ($branches as $branch)
        @if ($loop->first)
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
        @endif

        <article class="glass-card card-3d relative overflow-hidden p-5 text-center">
            <div class="pointer-events-none absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r from-sky-400 to-sky-700" aria-hidden="true"></div>

            <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-400 to-sky-700 text-2xl shadow-lg" aria-hidden="true">🏢</div>

            <h2 class="mt-3 text-base font-black text-slate-900 dark:text-white">{{ $branch->name }}</h2>
            <p class="mt-1"><code class="rounded-lg bg-slate-900/5 px-2 py-0.5 text-xs font-bold text-slate-500 dark:bg-white/10 dark:text-slate-300">{{ $branch->code }}</code></p>

            <dl class="mt-4 space-y-2 border-t border-slate-200/70 pt-4 dark:border-slate-700/60">
                <div class="rounded-xl bg-slate-900/[0.03] px-2 py-2 dark:bg-white/5">
                    <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Location</dt>
                    <dd class="mt-0.5 text-sm font-bold text-slate-700 dark:text-slate-200">{{ collect([$branch->city, $branch->state])->filter()->join(', ') ?: '—' }}</dd>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                        <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Phone</dt>
                        <dd class="mt-0.5 text-xs font-bold text-slate-700 dark:text-slate-200">{{ $branch->phone ?: '—' }}</dd>
                    </div>
                    <div class="rounded-xl bg-slate-900/[0.03] px-1 py-2 dark:bg-white/5">
                        <dt class="text-[10px] font-bold uppercase tracking-wide text-slate-400">Users</dt>
                        <dd class="tabular mt-0.5 text-sm font-black text-slate-800 dark:text-slate-100">{{ $branch->users_count }}</dd>
                    </div>
                </div>
            </dl>

            <div class="mt-4 flex justify-center">
                <span class="pill-status {{ $branch->isActive() ? 'pill-active' : 'pill-inactive' }}">
                    <span class="dot" aria-hidden="true"></span>{{ $branch->status }}
                </span>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
                @can('branch.edit')
                    <a href="{{ route('branches.edit', $branch) }}" class="btn-3d btn-3d-ghost btn-3d-sm">✏️ Edit</a>
                @endcan
                @can('branch.delete')
                    <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                          onsubmit="return confirm('Delete branch {{ $branch->name }}? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn-3d btn-3d-sm bg-gradient-to-b from-red-400 to-red-600 shadow">Delete</button>
                    </form>
                @endcan
            </div>
        </article>

        @if ($loop->last)
            </div>
        @endif
    @empty
        <div class="glass-card p-10 text-center">
            <div class="text-4xl" aria-hidden="true">🏢</div>
            <p class="mt-3 font-semibold text-slate-600 dark:text-slate-300">No branches yet.</p>
            @can('branch.create')
                <a href="{{ route('branches.create') }}" class="btn-3d btn-3d-primary mt-4">Add the first one</a>
            @endcan
        </div>
    @endforelse

    {{-- ================= Centered data table (barri screens) ================= --}}
    @if ($branches->isNotEmpty())
        <div class="glass-card mt-6 hidden overflow-hidden xl:block">
            <div class="table-3d">
                <table>
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Location</th>
                            <th>Phone</th>
                            <th>Users</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($branches as $branch)
                            <tr>
                                <td class="font-bold text-slate-800 dark:text-slate-100">{{ $branch->code }}</td>
                                <td>{{ $branch->name }}</td>
                                <td class="text-slate-500">{{ collect([$branch->city, $branch->state])->filter()->join(', ') ?: '—' }}</td>
                                <td>{{ $branch->phone ?: '—' }}</td>
                                <td class="tabular font-bold">{{ $branch->users_count }}</td>
                                <td>
                                    <span class="pill-status {{ $branch->isActive() ? 'pill-active' : 'pill-inactive' }}">
                                        <span class="dot" aria-hidden="true"></span>{{ $branch->status }}
                                    </span>
                                </td>
                                <td>
                                    <div class="flex items-center justify-center gap-2">
                                        @can('branch.edit')
                                            <a href="{{ route('branches.edit', $branch) }}" class="btn-3d btn-3d-ghost btn-3d-sm">Edit</a>
                                        @endcan
                                        @can('branch.delete')
                                            <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                                                  onsubmit="return confirm('Delete branch {{ $branch->name }}? This cannot be undone.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn-3d btn-3d-sm bg-gradient-to-b from-red-400 to-red-600 shadow">Delete</button>
                                            </form>
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

    <div class="mt-6">{{ $branches->links() }}</div>

    {{-- ================= Floating Action Button ================= --}}
    @can('branch.create')
        <a href="{{ route('branches.create') }}" class="fab-3d" title="Add a new branch">
            <span class="text-xl leading-none" aria-hidden="true">＋</span> Add Branch
        </a>
    @endcan
@endsection
