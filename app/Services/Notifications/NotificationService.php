<?php

namespace App\Services\Notifications;

use App\Models\NotificationSubscription;
use App\Models\User;
use App\Services\Admin\SettingsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notification Management Service
 * - Send alerts via SMS/Email
 * - Manage subscriptions
 * - Track "remember" settings
 * - Auto-cleanup old alerts
 */
class NotificationService
{
    /**
     * Security notification types — inhe kabhi SMS par nahi bheja jata,
     * sirf email par (user ki standing policy). Abhi koi security type
     * system me mojood nahi; ye list future OTP/2FA flows ke liye
     * reserved hai taake wo ghalti se bhi SMS par na jayein.
     */
    public const SECURITY_TYPES = [
        'SECURITY_OTP',
        'LOGIN_CODE',
        'PASSWORD_RESET_CODE',
        'APPROVAL_CODE',
    ];

    /**
     * Kya ye notification type security category ki hai?
     */
    public static function isSecurityType($type): bool
    {
        return in_array($type, self::SECURITY_TYPES, true);
    }

    protected SettingsService $settings;
    protected SmsService $sms;
    
    public function __construct(SettingsService $settings, SmsService $sms)
    {
        $this->settings = $settings;
        $this->sms = $sms;
    }
    
    /**
     * Send low stock alert
     */
    public function alertLowStock($tank, $currentLevel, $threshold)
    {
        $message = "⚠️ Low Stock Alert: {$tank->name} ({$tank->fuel->name}) - {$currentLevel}L (threshold: {$threshold}L)";
        
        return $this->sendToAdmins('low_stock', $message, [
            'tank_id' => $tank->id,
            'current_level' => $currentLevel,
            'threshold' => $threshold,
        ]);
    }
    
    /**
     * Send shift variance alert
     */
    public function alertShiftVariance($shift, $variance, $threshold)
    {
        $message = "⚠️ Shift Variance Alert: {$shift->employee->name} - Rs {$variance} (threshold: Rs {$threshold})";
        
        return $this->sendToAdmins('shift_variance', $message, [
            'shift_id' => $shift->id,
            'variance' => $variance,
            'threshold' => $threshold,
        ]);
    }
    
    /**
     * Send overdue credit alert
     */
    public function alertOverdueCredit($customer, $outstandingBalance, $days)
    {
        $message = "⚠️ Overdue Credit Alert: {$customer->name} - Rs {$outstandingBalance} outstanding (>{$days} days overdue)";
        
        return $this->sendToAdmins('overdue_credit', $message, [
            'customer_id' => $customer->id,
            'outstanding' => $outstandingBalance,
            'days' => $days,
        ]);
    }
    
    /**
     * Send pending approvals alert
     */
    public function alertPendingApproval($type, $count)
    {
        $message = "⚠️ {$count} pending {$type} approvals awaiting your action";
        
        return $this->sendToAdmins('pending_approval', $message, [
            'type' => $type,
            'count' => $count,
        ]);
    }
    
    /**
     * Send to all admin users subscribed to this notification
     */
    private function sendToAdmins($notificationType, $message, $data = [])
    {
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'ADMIN');
        })->get();
        
        $subscriptions = NotificationSubscription::where('notification_type', $notificationType)
            ->where('active', true)
            ->get();
        
        foreach ($admins as $admin) {
            // Check if user is subscribed
            $subscription = $subscriptions->firstWhere('user_id', $admin->id);
            if (!$subscription) continue;

            $isSecurity = self::isSecurityType($notificationType);

            // Security codes (OTP waghera) hamesha email par — subscription
            // ki email_enabled setting se qata nazar, kyunke ye lazmi hain.
            if (($subscription->email_enabled || $isSecurity) && $this->settings->get('email_enabled')) {
                $this->sendEmail($admin, $notificationType, $message, $data);
            }

            // Security types kabhi SMS par nahi (standing policy).
            if (!$isSecurity && $subscription->sms_enabled && $this->settings->get('sms_enabled')) {
                $this->sendSms($admin, $notificationType, $message);
            }
        }

        return true;
    }
    
    /**
     * Send email notification
     */
    private function sendEmail($user, $type, $message, $data = [])
    {
        try {
            // Queue the email
            Mail::queue('emails.notification', [
                'user' => $user,
                'type' => $type,
                'message' => $message,
                'data' => $data,
            ], function ($mail) use ($user) {
                $mail->to($user->email)
                    ->subject('🔔 Mehar Filling Station Alert');
            });
            
            Log::info("Email notification queued", ['user' => $user->email, 'type' => $type]);
            return true;
        } catch (\Exception $e) {
            Log::error("Email notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send SMS notification
     *
     * Security types yahan refuse hote hain — wo sirf email par jate hain
     * (user ki standing policy). Defense-in-depth: SmsService::send()
     * bhi CATEGORY_SECURITY wali call refuse karta hai.
     */
    private function sendSms($user, $type, $message)
    {
        if (self::isSecurityType($type)) {
            Log::warning('SMS refused for security notification type (email-only policy)', ['type' => $type]);
            return false;
        }

        try {
            $subscription = NotificationSubscription::where('user_id', $user->id)
                ->where('notification_type', $type)
                ->first();
            
            if ($subscription && $subscription->phone) {
                return $this->sms->send($subscription->phone, $message);
            }
            return false;
        } catch (\Exception $e) {
            Log::error("SMS notification failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Subscribe user to notification type
     */
    public function subscribe($user, $type, $email = null, $phone = null, $emailEnabled = true, $smsEnabled = false)
    {
        return NotificationSubscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'notification_type' => $type,
            ],
            [
                'email' => $email,
                'phone' => $phone,
                'email_enabled' => $emailEnabled,
                'sms_enabled' => $smsEnabled,
                'active' => true,
            ]
        );
    }
    
    /**
     * Unsubscribe from notification
     */
    public function unsubscribe($user, $type)
    {
        return NotificationSubscription::where('user_id', $user->id)
            ->where('notification_type', $type)
            ->update(['active' => false]);
    }
    
    /**
     * Get user subscriptions
     */
    public function getSubscriptions($user)
    {
        return NotificationSubscription::where('user_id', $user->id)->get();
    }
    
    /**
     * Clean up old alerts (older than configured days)
     */
    public function cleanupOldAlerts()
    {
        $keepDays = $this->settings->get('alert_keep_days', 30);
        
        // Delete old notifications (if we had a notifications_sent table)
        // For now, this is just a placeholder for future expansion
        
        Log::info("Alert cleanup completed - keeping last {$keepDays} days");
        return true;
    }
    
    /**
     * Check if a notification type should be remembered/kept
     */
    public function shouldRemember($type)
    {
        $rememberKey = "remember_{$type}";
        return $this->settings->get($rememberKey, false);
    }
}
