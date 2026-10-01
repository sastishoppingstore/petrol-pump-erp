@extends('layouts.app')

@section('title', __('admin.documents.title'))
@section('breadcrumb')
    <li class="text-slate-500">{{ __('admin.documents.title') }}</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>{{ __('admin.documents.title') }}
            <span class="ml-2 align-middle rounded bg-vital-primary/10 px-2.5 py-0.5 text-xs font-semibold text-vital-primary dark:bg-vital-primary/20">
                {{ __('admin.documents.badge') }}
            </span>
        </h1>
        <p>{{ __('admin.documents.subtitle') }}</p>
        <div class="page-actions">
            <a href="{{ route('documents.create') }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                {{ __('admin.documents.upload_btn') }}
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">{{ __('admin.documents.stat_total') }}</div>
            <div class="stat-value tabular">{{ $stats['total'] ?? 0 }}</div>
            <div class="stat-sub">{{ __('admin.documents.stat_total_sub') }}</div>
        </div>
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">{{ __('admin.documents.stat_valid') }}</div>
            <div class="stat-value tabular">{{ $stats['valid'] ?? 0 }}</div>
            <div class="stat-sub">{{ __('admin.documents.stat_valid_sub') }}</div>
        </div>
        <div class="stat-tile-3d stat-slate">
            <div class="stat-label">{{ __('admin.documents.stat_expiring') }}</div>
            <div class="stat-value tabular">{{ $stats['expiring'] ?? 0 }}</div>
            <div class="stat-sub">{{ __('admin.documents.stat_expiring_sub') }}</div>
        </div>
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">{{ __('admin.documents.stat_expired') }}</div>
            <div class="stat-value tabular">{{ $stats['expired'] ?? 0 }}</div>
            <div class="stat-sub">{{ __('admin.documents.stat_expired_sub') }}</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('documents.index') }}" class="glass-card p-4">
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.documents.category') }}</label>
                <select name="category" class="input-3d w-full text-center text-sm">
                    <option value="">{{ __('admin.documents.all_categories') }}</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.documents.expiry_status') }}</label>
                <select name="status" class="input-3d w-full text-center text-sm">
                    <option value="">{{ __('admin.employees.all_statuses') }}</option>
                    <option value="valid" @selected(request('status') === 'valid')>{{ __('admin.documents.opt_valid') }}</option>
                    <option value="expiring" @selected(request('status') === 'expiring')>{{ __('admin.documents.opt_expiring') }}</option>
                    <option value="expired" @selected(request('status') === 'expired')>{{ __('admin.documents.opt_expired') }}</option>
                    <option value="no-expiry" @selected(request('status') === 'no-expiry')>{{ __('admin.documents.opt_no_expiry') }}</option>
                </select>
            </div>
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">{{ __('admin.documents.search_title') }}</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="{{ __('admin.documents.search_placeholder') }}" class="input-3d w-full text-center text-sm">
            </div>
            <div class="flex items-end justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-navy w-full">{{ __('ui.actions.filter') }}</button>
                <a href="{{ route('documents.index') }}" class="btn-3d btn-3d-ghost">{{ __('admin.employees.reset') }}</a>
            </div>
        </div>
    </form>

    {{-- Documents table --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.documents.document') }}</th>
                        <th>{{ __('admin.documents.category') }}</th>
                        <th>{{ __('admin.documents.issue_date') }}</th>
                        <th>{{ __('admin.documents.expiry_date') }}</th>
                        <th>{{ __('admin.common.status') }}</th>
                        <th>{{ __('admin.documents.uploaded_by') }}</th>
                        <th>{{ __('admin.common.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($documents as $document)
                        @php $status = $document->expiryStatus(); @endphp
                        <tr>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white">{{ $document->title }}</div>
                                @if ($document->note)
                                    <div class="text-xs text-slate-400">{{ $document->note }}</div>
                                @endif
                                <div class="text-xs text-slate-400">{{ $document->branch?->name }}</div>
                            </td>
                            <td class="text-xs text-slate-600 dark:text-slate-300">{{ $document->categoryLabel() }}</td>
                            <td class="text-xs text-slate-500">{{ $document->issue_date?->format('d M Y') ?? '—' }}</td>
                            <td class="text-xs font-medium {{ $status === 'expired' ? 'text-red-600 font-bold' : ($status === 'expiring' ? 'text-amber-600 font-bold' : 'text-slate-600 dark:text-slate-300') }}">
                                {{ $document->expiry_date?->format('d M Y') ?? '—' }}
                            </td>
                            <td>
                                <span @class([
                                    'rounded px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-100 text-emerald-800' => $status === 'valid',
                                    'bg-amber-100 text-amber-800' => $status === 'expiring',
                                    'bg-red-100 text-red-800' => $status === 'expired',
                                    'bg-slate-100 text-slate-600' => $status === 'no-expiry',
                                ])>
                                    @if ($status === 'valid') {{ __('admin.documents.status_valid') }}
                                    @elseif ($status === 'expiring') {{ __('admin.documents.status_expiring') }}
                                    @elseif ($status === 'expired') {{ __('admin.documents.status_expired') }}
                                    @else {{ __('admin.documents.status_no_expiry') }}
                                    @endif
                                </span>
                            </td>
                            <td class="text-xs text-slate-500">
                                {{ $document->uploader?->name ?? '—' }}
                                <div class="text-[11px] text-slate-400">{{ $document->created_at?->format('d M Y') }}</div>
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm">{{ __('ui.actions.view') }}</a>
                                    <a href="{{ asset('storage/' . $document->file_path) }}" download class="btn-3d btn-3d-navy btn-3d-sm">{{ __('admin.documents.download') }}</a>
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete document &quot;{{ $document->title }}&quot;? Ye file bhi delete ho jayegi.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">{{ __('ui.actions.delete') }}</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                {{ __('admin.documents.none') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $documents->links() }}</div>
    </div>

    <a href="{{ route('documents.create') }}" class="fab-3d" title="{{ __('admin.documents.fab_title') }}">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    </a>
</div>
@endsection
