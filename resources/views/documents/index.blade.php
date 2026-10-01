@extends('layouts.app')

@section('title', 'Document Vault / دستاویزات')
@section('breadcrumb')
    <li class="text-slate-500">Document Vault</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header (centered) --}}
    <div class="page-head">
        <h1>Document Vault / دستاویزات
            <span class="ml-2 align-middle rounded bg-vital-primary/10 px-2.5 py-0.5 text-xs font-semibold text-vital-primary dark:bg-vital-primary/20">
                Compliance
            </span>
        </h1>
        <p>OGRA licence, dealership agreement, NOCs aur calibration certificates — expiry se pehle alert ke saath mehfooz.</p>
        <div class="page-actions">
            <a href="{{ route('documents.create') }}" class="btn-3d btn-3d-success">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Upload Document
            </a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-tile-3d stat-navy">
            <div class="stat-label">Total Documents</div>
            <div class="stat-value tabular">{{ $stats['total'] ?? 0 }}</div>
            <div class="stat-sub">Vault me mehfooz</div>
        </div>
        <div class="stat-tile-3d stat-green">
            <div class="stat-label">Valid</div>
            <div class="stat-value tabular">{{ $stats['valid'] ?? 0 }}</div>
            <div class="stat-sub">Expiry 30 din se zyada door</div>
        </div>
        <div class="stat-tile-3d stat-slate">
            <div class="stat-label">Expiring Soon</div>
            <div class="stat-value tabular">{{ $stats['expiring'] ?? 0 }}</div>
            <div class="stat-sub">Aglay 30 din me expire</div>
        </div>
        <div class="stat-tile-3d stat-red">
            <div class="stat-label">Expired</div>
            <div class="stat-value tabular">{{ $stats['expired'] ?? 0 }}</div>
            <div class="stat-sub">Foran renew karein</div>
        </div>
    </div>

    {{-- Filters --}}
    <form method="GET" action="{{ route('documents.index') }}" class="glass-card p-4">
        <div class="grid gap-3 sm:grid-cols-4">
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Category / زمرہ</label>
                <select name="category" class="input-3d w-full text-center text-sm">
                    <option value="">All Categories</option>
                    @foreach ($categories as $key => $label)
                        <option value="{{ $key }}" @selected(request('category') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Expiry Status</label>
                <select name="status" class="input-3d w-full text-center text-sm">
                    <option value="">All Statuses</option>
                    <option value="valid" @selected(request('status') === 'valid')>Valid</option>
                    <option value="expiring" @selected(request('status') === 'expiring')>Expiring (≤ 30 days)</option>
                    <option value="expired" @selected(request('status') === 'expired')>Expired</option>
                    <option value="no-expiry" @selected(request('status') === 'no-expiry')>No Expiry Date</option>
                </select>
            </div>
            <div class="field-3d">
                <label class="mb-1 block text-center text-xs font-semibold uppercase text-slate-500">Search Title / تلاش</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Document title" class="input-3d w-full text-center text-sm">
            </div>
            <div class="flex items-end justify-center gap-2">
                <button type="submit" class="btn-3d btn-3d-navy w-full">Filter</button>
                <a href="{{ route('documents.index') }}" class="btn-3d btn-3d-ghost">Reset</a>
            </div>
        </div>
    </form>

    {{-- Documents table --}}
    <div class="glass-card overflow-hidden">
        <div class="table-3d">
            <table>
                <thead>
                    <tr>
                        <th>Document / دستاویز</th>
                        <th>Category</th>
                        <th>Issue Date</th>
                        <th>Expiry Date</th>
                        <th>Status</th>
                        <th>Uploaded By</th>
                        <th>Actions</th>
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
                                    @if ($status === 'valid') Valid / درست
                                    @elseif ($status === 'expiring') Expiring Soon / جلد ختم
                                    @elseif ($status === 'expired') Expired / معیاد ختم
                                    @else No Expiry / بغیر معیاد
                                    @endif
                                </span>
                            </td>
                            <td class="text-xs text-slate-500">
                                {{ $document->uploader?->name ?? '—' }}
                                <div class="text-[11px] text-slate-400">{{ $document->created_at?->format('d M Y') }}</div>
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                <div class="flex items-center justify-center gap-1.5">
                                    <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank" class="btn-3d btn-3d-ghost btn-3d-sm">View</a>
                                    <a href="{{ asset('storage/' . $document->file_path) }}" download class="btn-3d btn-3d-navy btn-3d-sm">Download</a>
                                    <form action="{{ route('documents.destroy', $document) }}" method="POST" class="inline"
                                          onsubmit="return confirm('Delete document &quot;{{ $document->title }}&quot;? Ye file bhi delete ho jayegi.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-3d btn-3d-primary btn-3d-sm">Delete</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 text-center text-slate-500">
                                Koi document nahi mila. Pehla document upload karne ke liye "Upload Document" dabayein.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-slate-100 dark:border-slate-800">{{ $documents->links() }}</div>
    </div>

    <a href="{{ route('documents.create') }}" class="fab-3d" title="Upload a document">
        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
    </a>
</div>
@endsection
