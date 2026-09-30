<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Notification::query()->latest();

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('level')) {
            $query->where('level', $request->input('level'));
        }

        if ($request->input('filter') === 'unread') {
            $query->whereNull('read_at');
        } elseif ($request->input('filter') === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(20)->withQueryString();
        $unreadCount = Notification::whereNull('read_at')->count();

        return view('notifications.index', compact('notifications', 'unreadCount'));
    }

    public function markAsRead(Notification $notification): RedirectResponse
    {
        $notification->update(['read_at' => now()]);

        return back()->with('success', 'اطلاع کو پڑھا ہوا نشان زد کر دیا گیا ہے۔');
    }

    public function markAllAsRead(): RedirectResponse
    {
        Notification::whereNull('read_at')->update(['read_at' => now()]);

        return back()->with('success', 'تمام اطلاعات کو پڑھا ہوا نشان زد کر دیا گیا ہے۔');
    }
}
