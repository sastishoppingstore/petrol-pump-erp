@extends('layouts.app')

@section('title', __('admin.backups.title'))
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">{{ __('admin.backups.breadcrumb') }}</li>
@endsection

{{--
    Backups — 2026 redesign.
    Page head centered; create buttons .btn-3d; saved backups .table-3d
    glass-card me. Tamam POST routes, download URL ($backupService) aur
    flash alerts pehle jaisay hi hain.
--}}
@section('content')
    <div class="page-head">
        <h1>💾 {{ __('admin.backups.heading') }}</h1>
        <p style="font-family: 'Jameel Noori Nastaleeq', Tahoma;">{{ __('admin.backups.subtitle') }}</p>
        <div class="page-actions">
            <form method="POST" action="{{ route('backups.database') }}">
                @csrf
                <button type="submit" class="btn-3d btn-3d-ghost">
                    💾 {{ __('admin.backups.create_db') }}
                </button>
            </form>
            <form method="POST" action="{{ route('backups.full') }}">
                @csrf
                <button type="submit" class="btn-3d btn-3d-primary">
                    📦 {{ __('admin.backups.create_full') }}
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" class="mx-auto mb-6 flex max-w-3xl items-center justify-between gap-3 rounded-2xl border border-emerald-300/60 bg-emerald-50 px-5 py-3 text-center text-sm font-semibold text-emerald-800 shadow-sm dark:bg-emerald-500/10 dark:text-emerald-300" role="alert">
            <span class="flex-1">{{ session('success') }}</span>
            <button type="button" @click="show = false" class="font-black text-emerald-500 hover:text-emerald-700" aria-label="{{ __('ui.actions.close') }}">✕</button>
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }" x-show="show" class="mx-auto mb-6 flex max-w-3xl items-center justify-between gap-3 rounded-2xl border border-red-300/60 bg-red-50 px-5 py-3 text-center text-sm font-semibold text-red-800 shadow-sm dark:bg-red-500/10 dark:text-red-300" role="alert">
            <span class="flex-1">{{ session('error') }}</span>
            <button type="button" @click="show = false" class="font-black text-red-500 hover:text-red-700" aria-label="{{ __('ui.actions.close') }}">✕</button>
        </div>
    @endif

    <div class="glass-card overflow-hidden">
        <h2 class="border-b border-slate-200/70 px-5 py-4 text-center text-base font-black text-slate-800 dark:border-slate-700/60 dark:text-slate-100">{{ __('admin.backups.saved') }}</h2>
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('admin.backups.file_name') }}</th>
                        <th>{{ __('admin.amanat.type') }}</th>
                        <th>{{ __('admin.backups.file_size') }}</th>
                        <th>{{ __('admin.backups.created_at') }}</th>
                        <th>{{ __('admin.backups.created_by') }}</th>
                        <th>{{ __('admin.common.status') }}</th>
                        <th>{{ __('admin.backups.download') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $b)
                        <tr>
                            <td class="font-mono font-bold text-slate-800 dark:text-slate-100">{{ $b->file_name }}</td>
                            <td>
                                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-black uppercase tracking-wide {{ $b->backup_type === 'FULL' ? 'bg-vital-primary/15 text-vital-darkred dark:text-red-300' : 'bg-slate-900/5 text-slate-600 dark:bg-white/10 dark:text-slate-300' }}">
                                    {{ $b->backup_type }}
                                </span>
                            </td>
                            <td class="tabular">{{ $b->formatted_size }}</td>
                            <td>{{ $b->created_at->format('d M Y, h:i A') }}</td>
                            <td>{{ $b->creator?->name ?? __('admin.audit_logs.system') }}</td>
                            <td>
                                <span class="pill-status pill-active"><span class="dot" aria-hidden="true"></span>{{ $b->status }}</span>
                            </td>
                            <td>
                                <a href="{{ $backupService->getDownloadUrl($b) }}" class="btn-3d btn-3d-primary btn-3d-sm">
                                    ⬇️ {{ __('admin.backups.download_signed') }}
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-10 text-slate-400">{{ __('admin.backups.none') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-200/70 p-4 dark:border-slate-700/60">
            {{ $backups->links() }}
        </div>
    </div>
@endsection
