<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class NotificationPreferencesController extends Controller
{
    protected $notificationService;
    protected $settingsService;
    
    public function __construct()
    {
        $this->middleware('auth');
        $this->notificationService = app(\App\Services\Notifications\NotificationService::class);
        $this->settingsService = app(\App\Services\Admin\SettingsService::class);
    }
    
    /**
     * Show notification preferences form
     */
    public function show()
    {
        $subscriptions = $this->notificationService->getSubscriptions(auth()->user());
        
        $globalSettings = [
            'email_enabled' => $this->settingsService->get('email_enabled'),
            'sms_enabled' => $this->settingsService->get('sms_enabled'),
            'remember_low_stock' => $this->settingsService->get('remember_low_stock'),
            'remember_variance_alerts' => $this->settingsService->get('remember_variance_alerts'),
            'remember_overdue_credit' => $this->settingsService->get('remember_overdue_credit'),
            'remember_pending_approvals' => $this->settingsService->get('remember_pending_approvals'),
            'alert_keep_days' => $this->settingsService->get('alert_keep_days'),
        ];
        
        return view('admin.notifications.preferences', compact('subscriptions', 'globalSettings'));
    }
    
    /**
     * Update notification preferences
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $notifications = $request->input('notifications', []);
        
        // Update notification subscriptions
        foreach ($notifications as $type => $config) {
            $this->notificationService->subscribe(
                user: $user,
                type: $type,
                email: $config['email_addr'] ?? $user->email,
                phone: $config['phone'] ?? null,
                emailEnabled: isset($config['email']),
                smsEnabled: isset($config['sms'])
            );
            
            // Update active status
            $subscription = \App\Models\NotificationSubscription::where('user_id', $user->id)
                ->where('notification_type', $type)
                ->first();
            
            if ($subscription) {
                $subscription->update(['active' => isset($config['active'])]);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully'
        ]);
    }
}
