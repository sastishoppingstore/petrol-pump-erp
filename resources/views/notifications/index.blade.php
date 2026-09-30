@extends('layouts.app')

@section('title', 'اطلاعات / Notifications')
@section('breadcrumb')
    <li class="text-slate-500">Notifications</li>
@endsection

@section('content')
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">اطلاعات اور انتباہات / Notifications</h1>
                @if ($unreadCount > 0)
                    <span class="rounded-full bg-red-100 px-3 py-0.5 text-xs font-bold text-red-800 dark:bg-red-900/40 dark:text-red-300">
                        {{ $unreadCount }} نئی اطلاعات
                    </span>
                @endif
            </div>
            <p class="mt-1 text-sm text-slate-500">
                Low stock alerts, shift cash variances, pending approvals &amp; customer credit overdue warnings.
            </p>
        </div>

        @if ($unreadCount > 0)
            <form action="{{ route('notifications.read-all') }}" method="POST">
                @csrf
                <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-700 dark:bg-slate-700 dark:hover:bg-slate-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    سب پڑھا ہوا نشان زد کریں / Mark All Read
                </button>
            </form>
        @endif
    </div>

    {{-- Filter Toolbar --}}
    <div class="flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
        <a href="{{ route('notifications.index') }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ !request('filter') ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
            تمام / All
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ request('filter') === 'unread' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
            غیر خواندہ / Unread ({{ $unreadCount }})
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'read']) }}" class="rounded-lg px-3 py-1.5 text-xs font-semibold {{ request('filter') === 'read' ? 'bg-red-600 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300' }}">
            پڑھی ہوئی / Read
        </a>
    </div>

    {{-- Notification List --}}
    <div class="space-y-3">
        @forelse ($notifications as $notification)
            <div class="flex items-start justify-between gap-4 rounded-xl border p-4 shadow-sm transition {{ $notification->isRead() ? 'border-slate-200 bg-white opacity-80 dark:border-slate-800 dark:bg-slate-900' : 'border-red-200 bg-red-50/40 dark:border-red-950 dark:bg-red-950/20' }}">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5 rounded-lg p-2 {{ $notification->level === 'CRITICAL' ? 'bg-red-100 text-red-600 dark:bg-red-900/40' : ($notification->level === 'WARNING' ? 'bg-amber-100 text-amber-600 dark:bg-amber-900/40' : 'bg-blue-100 text-blue-600 dark:bg-blue-900/40') }}">
                        @if ($notification->level === 'CRITICAL')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @elseif ($notification->level === 'WARNING')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h4 class="font-bold text-slate-900 dark:text-white">{{ $notification->title }}</h4>
                            <span class="rounded px-1.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $notification->level === 'CRITICAL' ? 'bg-red-200 text-red-800' : ($notification->level === 'WARNING' ? 'bg-amber-200 text-amber-800' : 'bg-blue-200 text-blue-800') }}">
                                {{ $notification->level }}
                            </span>
                            @if (!$notification->isRead())
                                <span class="h-2 w-2 rounded-full bg-red-600"></span>
                            @endif
                        </div>
                        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $notification->message }}</p>
                        <div class="mt-2 flex items-center gap-3 text-xs text-slate-400">
                            <span>{{ $notification->created_at->diffForHumans() }}</span>
                            <span>•</span>
                            <span>{{ $notification->module }}</span>
                        </div>
                    </div>
                </div>

                @if (!$notification->isRead())
                    <form action="{{ route('notifications.read', $notification) }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-lg border border-slate-300 px-3 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            پڑھ لیا / Read
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white p-12 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <h3 class="mt-2 text-sm font-semibold text-slate-900 dark:text-white">کوئی نئی اطلاع نہیں ہے</h3>
                <p class="mt-1 text-xs text-slate-500">تمام آپریشنز معمول کے مطابق چل رہے ہیں۔</p>
            </div>
        @endforelse
    </div>

    <div>
        {{ $notifications->links() }}
    </div>
</div>
@endsection
