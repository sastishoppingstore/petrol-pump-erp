<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SendDailyClosingReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:send-scheduled {--branch= : Branch ID (optional)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send scheduled daily closing reports via email and SMS (scheduler: reports:send-scheduled, daily 23:00)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📧 Sending daily closing reports...');
        
        $service = app(\App\Services\Reports\DailyClosingReportService::class);
        
        if ($this->option('branch')) {
            $branch = \App\Models\Branch::find($this->option('branch'));
            if (!$branch) {
                $this->error('❌ Branch not found');
                return 1;
            }
            $service->generateAndSendReport($branch);
            $this->info("✅ Report sent for {$branch->name}");
        } else {
            $service->sendScheduledReports();
            $this->info('✅ Daily reports sent for all branches');
        }
        
        return 0;
    }
}
