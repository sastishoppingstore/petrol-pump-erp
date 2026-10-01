@extends('layouts.app')

@section('title', 'اطلاعات / Notifications')
@section('breadcrumb')
    <li>/</li>
    <li class="font-semibold text-slate-700 dark:text-slate-300">Notifications</li>
@endsection

{{--
    Notifications — 2026 redesign.
    Page head centered; filter pills glass box me; har notification ek
    glass-card. Tamam routes (read-all, read, filter) aur Urdu labels
    pehle jaisay hi hain.
--}}
@section('content')
    <div class="page-head">
        <h1>🔔 اطلاعات اور انتباہات / Notifications
            @if ($unreadCount > 0)
                <span class="ml-1 align-middle rounded-full bg-red-500/15 px-3 py-1 text-xs font-black text-red-600 dark:text-red-400">{{ $unreadCount }} نئی اطلاعات</span>
            @endif
        </h1>
        <p>Low stock alerts, shift cash variances, pending approvals &amp; customer credit overdue warnings.</p>
        @if ($unreadCount > 0)
            <div class="page-actions">
                <form action="{{ route('notifications.read-all') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn-3d btn-3d-navy">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        سب پڑھا ہوا نشان زد کریں / Mark All Read
                    </button>
                </form>
            </div>
        @endif
    </div>

    {{-- Filter Toolbar --}}
    <div class="glass-card mx-auto mb-6 flex max-w-xl flex-wrap items-center justify-center gap-2 p-3">
        <a href="{{ route('notifications.index') }}" class="rounded-full px-4 py-1.5 text-xs font-bold transition {{ !request('filter') ? 'bg-gradient-to-b from-vital-primary to-vital-darkred text-white shadow' : 'bg-slate-900/5 text-slate-600 hover:bg-slate-900/10 dark:bg-white/10 dark:text-slate-300' }}">
            تمام / All
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" class="rounded-full px-4 py-1.5 text-xs font-bold transition {{ request('filter') === 'unread' ? 'bg-gradient-to-b from-vital-primary to-vital-darkred text-white shadow' : 'bg-slate-900/5 text-slate-600 hover:bg-slate-900/10 dark:bg-white/10 dark:text-slate-300' }}">
            غیر خواندہ / Unread ({{ $unreadCount }})
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'read']) }}" class="rounded-full px-4 py-1.5 text-xs font-bold transition {{ request('filter') === 'read' ? 'bg-gradient-to-b from-vital-primary to-vital-darkred text-white shadow' : 'bg-slate-900/5 text-slate-600 hover:bg-slate-900/10 dark:bg-white/10 dark:text-slate-300' }}">
            پڑھی ہوئی / Read
        </a>
    </div>

    {{-- Notification List --}}
    <div class="mx-auto max-w-4xl space-y-4">
        @forelse ($notifications as $notification)
            <div class="glass-card card-3d p-5 {{ $notification->isRead() ? 'opacity-80' : '' }}">
                <div class="flex flex-col items-center gap-3 text-center">
                    <div class="rounded-2xl p-2.5 shadow {{ $notification->level === 'CRITICAL' ? 'bg-gradient-to-br from-red-400 to-red-600 text-white' : ($notification->level === 'WARNING' ? 'bg-gradient-to-br from-amber-400 to-amber-600 text-white' : 'bg-gradient-to-br from-sky-400 to-sky-600 text-white') }}">
                        @if ($notification->level === 'CRITICAL')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        @elseif ($notification->level === 'WARNING')
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @else
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-2">
                        <h4 class="font-black text-slate-900 dark:text-white">{{ $notification->title }}</h4>
                        <span class="rounded-full px-2 py-0.5 text-[10px] font-black uppercase tracking-wider {{ $notification->level === 'CRITICAL' ? 'bg-red-500/15 text-red-600 dark:text-red-400' : ($notification->level === 'WARNING' ? 'bg-amber-500/15 text-amber-600 dark:text-amber-400' : 'bg-sky-500/15 text-sky-600 dark:text-sky-400') }}">
                            {{ $notification->level }}
                        </span>
                        @if (!$notification->isRead())
                            <span class="h-2 w-2 rounded-full bg-red-600"></span>
                        @endif
                    </div>

                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $notification->message }}</p>

                    <div class="flex items-center justify-center gap-3 text-xs text-slate-400">
                        <span>{{ $notification->created_at->diffForHumans() }}</span>
                        <span>•</span>
                        <span>{{ $notification->module }}</span>
                    </div>

                    @if (!$notification->isRead())
                        <form action="{{ route('notifications.read', $notification) }}" method="POST">
                            @csrf
                            <button type="submit" class="btn-3d btn-3d-ghost btn-3d-sm">
                                پڑھ لیا / Read
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="glass-card p-12 text-center">
                <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                <h3 class="mt-3 text-sm font-black text-slate-900 dark:text-white">کوئی نئی اطلاع نہیں ہے</h3>
                <p class="mt-1 text-xs text-slate-500">تمام آپریشنز معمول کے مطابق چل رہے ہیں۔</p>
            </div>
        @endforelse
    </div>

    <div class="mt-6">
        {{ $notifications->links() }}
    </div>
@endsection
