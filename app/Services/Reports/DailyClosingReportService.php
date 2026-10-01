<?php

namespace App\Services\Reports;

use App\Models\AutoReport;
use App\Models\Branch;
use App\Services\Admin\SettingsService;
use App\Services\Notifications\NotificationService;
use App\Services\Notifications\SmsService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Daily Closing Report Service
 * Generates and sends automated daily closing reports to admins
 * Triggered by scheduler at configured time (default 23:00)
 */
class DailyClosingReportService
{
    protected SettingsService $settings;
    protected NotificationService $notification;
    protected SmsService $sms;
    protected ReportGenerationService $reportService;
    
    public function __construct(
        SettingsService $settings,
        NotificationService $notification,
        SmsService $sms,
        ReportGenerationService $reportService
    ) {
        $this->settings = $settings;
        $this->notification = $notification;
        $this->sms = $sms;
        $this->reportService = $reportService;
    }
    
    /**
     * Generate and send daily closing reports for all branches
     * Called by scheduler at configured time
     */
    public function sendScheduledReports()
    {
        $branches = Branch::where('status', Branch::STATUS_ACTIVE)->get();
        
        foreach ($branches as $branch) {
            $this->generateAndSendReport($branch);
        }
        
        Log::info('Scheduled daily closing reports sent for all branches');
    }
    
    /**
     * Generate and send report for a specific branch
     */
    public function generateAndSendReport(Branch $branch)
    {
        try {
            // Generate 24h report
            $report = $this->reportService->generateReport($branch, '24h');
            
            if (!$report) {
                Log::error('Failed to generate daily report for branch', ['branch' => $branch->name]);
                return false;
            }
            
            // Get admin recipients
            $recipients = $this->getAdminRecipients($branch);
            
            if ($recipients->isEmpty()) {
                Log::warning('No admin recipients found for daily report', ['branch' => $branch->name]);
                return false;
            }
            
            // Send email
            if ($this->settings->get('email_enabled')) {
                $this->sendEmailReport($report, $recipients);
            }
            
            // Send SMS
            if ($this->settings->get('sms_enabled')) {
                $this->sendSmsReport($report, $recipients);
            }
            
            // Mark as sent.
            // recipient_email / recipient_phone model par 'array' cast
            // hai — array hi pass karo, toJson() string double-encode
            // ho jati hai.
            $report->update([
                'status' => 'sent_email',
                'sent_at' => now(),
                'recipient_email' => $recipients->pluck('email')->all(),
                'recipient_phone' => $recipients->pluck('phone')->filter()->values()->all(),
            ]);
            
            Log::info('Daily closing report sent', [
                'branch' => $branch->name,
                'recipients' => $recipients->count(),
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error('Daily closing report failed: ' . $e->getMessage(), [
                'branch' => $branch->name,
            ]);
            return false;
        }
    }
    
    /**
     * Send email report to recipients
     */
    private function sendEmailReport($report, $recipients)
    {
        foreach ($recipients as $user) {
            try {
                Mail::queue('emails.daily-closing-report', [
                    'report' => $report,
                    'user' => $user,
                    'summary' => $report->summary_json,
                ], function ($mail) use ($user, $report) {
                    $mail->to($user->email)
                        ->subject("📊 Daily Closing Report - {$report->branch->name} - " . now()->format('M d, Y'));
                });
                
                Log::info('Daily report email queued', ['user' => $user->email]);
            } catch (\Exception $e) {
                Log::error('Failed to queue daily report email: ' . $e->getMessage());
            }
        }
    }
    
    /**
     * Send SMS report summary
     */
    private function sendSmsReport($report, $recipients)
    {
        $summary = $report->summary_json;

        // Purani (double-encoded) rows ka guard
        if (is_string($summary)) {
            $summary = json_decode($summary, true) ?: [];
        }
        
        // Build SMS message (keep it short for SMS)
        $message = sprintf(
            "📊 %s Daily Report\nSales: Rs %s\nFuel: %s L\nMargin: %s%%\nNet: Rs %s",
            $report->branch->name,
            $summary['total_sales'],
            $summary['total_litres'],
            $summary['gross_margin_percentage'],
            $summary['net_profit']
        );
        
        foreach ($recipients as $user) {
            if ($user->phone) {
                $this->sms->send($user->phone, $message);
            }
        }
    }
    
    /**
     * Get admin users who should receive the report
     * Based on notification subscriptions
     */
    private function getAdminRecipients($branch)
    {
        $subscriptions = \App\Models\NotificationSubscription::whereHas('user.roles', function ($query) {
            $query->where('name', 'ADMIN');
        })
        ->where('active', true)
        ->get()
        ->pluck('user')
        ->unique('id');
        
        return $subscriptions;
    }
    
    /**
     * Get report summary for display
     */
    public function getReportSummary($report)
    {
        $summary = $report->summary_json;

        // Purani (double-encoded) rows ka guard
        if (is_string($summary)) {
            $summary = json_decode($summary, true) ?: [];
        }
        
        return [
            'branch' => $summary['branch'],
            'period' => $summary['period_start'] . ' to ' . $summary['period_end'],
            'sales' => [
                'total' => $summary['total_sales'],
                'litres' => $summary['total_litres'],
                'cash' => $summary['cash_sales'],
                'card' => $summary['card_sales'],
                'credit' => $summary['credit_sales'],
                'transactions' => $summary['transaction_count'],
            ],
            'profitability' => [
                'revenue' => $summary['total_revenue'],
                'cogs' => $summary['cogs'],
                'gross_margin' => $summary['gross_margin'],
                'gross_margin_pct' => $summary['gross_margin_percentage'],
                'expenses' => $summary['expenses'],
                'net_profit' => $summary['net_profit'],
                'net_profit_pct' => $summary['net_profit_percentage'],
            ],
            'stock' => [
                'purchases' => $summary['purchases'],
                'sales' => $summary['sales'],
                'variance' => $summary['variance'],
            ],
            'fuel_breakdown' => $summary['fuel_breakdown'],
        ];
    }
}
