<?php

namespace App\Services\Notifications;

use App\Services\Admin\SettingsService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * SMS Service - Multiple Providers (Jazz, Zong, Telenor)
 * Controlled by admin settings
 *
 * STANDING POLICY (user rule): security one-time codes (login OTP,
 * password-reset codes, approval PINs waghera) hamesha EMAIL par jate
 * hain — kabhi SMS par nahi. Ye service sirf general/reminder
 * notifications (wasooli reminders, alerts) ke liye hai. Security
 * category wala send() call refuse ho jata hai (neeche dekhein).
 */
class SmsService
{
    /**
     * Message categories. 'security' kabhi SMS par nahi bheja jata —
     * aisi call SmsService::send() me hi refuse ho jati hai.
     */
    public const CATEGORY_GENERAL = 'general';
    public const CATEGORY_SECURITY = 'security';

    protected SettingsService $settings;
    
    public function __construct(SettingsService $settings)
    {
        $this->settings = $settings;
    }
    
    /**
     * Send SMS via configured provider
     *
     * @param string $category SmsService::CATEGORY_GENERAL ya CATEGORY_SECURITY.
     *                         'security' category (OTP / verification codes)
     *                         hamesha refuse hoti hai — wo sirf email par jate hain.
     */
    public function send($phone, $message, $category = self::CATEGORY_GENERAL)
    {
        if ($category === self::CATEGORY_SECURITY) {
            Log::warning('SMS refused: security one-time codes are email-only (standing policy)', [
                'phone' => $phone,
            ]);
            return false;
        }

        if (!$this->isEnabled()) {
            Log::info('SMS disabled in settings');
            return false;
        }
        
        $provider = $this->settings->get('sms_provider', 'jazz');
        
        return match ($provider) {
            'jazz' => $this->sendViaJazz($phone, $message),
            'zong' => $this->sendViaZong($phone, $message),
            'telenor' => $this->sendViaTelenor($phone, $message),
            default => false,
        };
    }
    
    /**
     * Send via Jazz (Mobilink) API
     */
    private function sendViaJazz($phone, $message)
    {
        try {
            $apiKey = decrypt($this->settings->get('sms_api_key', ''));
            if (!$apiKey) {
                Log::warning('Jazz SMS API key not configured');
                return false;
            }
            
            // Jazz API endpoint (example)
            $response = Http::timeout(10)->post('https://api.jazzweb.com/send', [
                'phone' => $this->formatPhone($phone),
                'message' => $message,
                'api_key' => $apiKey,
            ]);
            
            if ($response->successful()) {
                Log::info('SMS sent via Jazz', ['phone' => $phone, 'length' => strlen($message)]);
                return true;
            } else {
                Log::error('Jazz SMS failed', ['response' => $response->body()]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Jazz SMS error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send via Zong API
     */
    private function sendViaZong($phone, $message)
    {
        try {
            $apiKey = decrypt($this->settings->get('sms_api_key', ''));
            if (!$apiKey) {
                Log::warning('Zong SMS API key not configured');
                return false;
            }
            
            $response = Http::timeout(10)->post('https://api.zong.com.pk/send', [
                'phone' => $this->formatPhone($phone),
                'text' => $message,
                'api_key' => $apiKey,
            ]);
            
            if ($response->successful()) {
                Log::info('SMS sent via Zong', ['phone' => $phone]);
                return true;
            } else {
                Log::error('Zong SMS failed', ['response' => $response->body()]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Zong SMS error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send via Telenor API
     */
    private function sendViaTelenor($phone, $message)
    {
        try {
            $apiKey = decrypt($this->settings->get('sms_api_key', ''));
            if (!$apiKey) {
                Log::warning('Telenor SMS API key not configured');
                return false;
            }
            
            $response = Http::timeout(10)->post('https://api.telenor.com.pk/sms/send', [
                'msisdn' => $this->formatPhone($phone),
                'message' => $message,
                'api_key' => $apiKey,
            ]);
            
            if ($response->successful()) {
                Log::info('SMS sent via Telenor', ['phone' => $phone]);
                return true;
            } else {
                Log::error('Telenor SMS failed', ['response' => $response->body()]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Telenor SMS error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check if SMS is enabled
     */
    private function isEnabled()
    {
        return $this->settings->get('sms_enabled', false);
    }
    
    /**
     * Format phone number to 92XXXXXXXXXX
     */
    private function formatPhone($phone)
    {
        // Remove common formatting
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // If starts with 0, replace with 92
        if (str_starts_with($phone, '0')) {
            $phone = '92' . substr($phone, 1);
        }
        
        // If doesn't start with 92, add it
        if (!str_starts_with($phone, '92')) {
            $phone = '92' . $phone;
        }
        
        return $phone;
    }
}
