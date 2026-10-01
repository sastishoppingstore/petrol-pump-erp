<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateAutoReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:generate {--branch= : Branch ID (optional, all if omitted)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate auto-reports (12h, 24h, 7d, 15d, 30d) as per admin settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Generating auto-reports...');
        
        $service = app(\App\Services\Reports\ReportGenerationService::class);
        
        if ($this->option('branch')) {
            $branch = \App\Models\Branch::find($this->option('branch'));
            if (!$branch) {
                $this->error('❌ Branch not found');
                return 1;
            }
            $count = $service->generateReportsForBranch($branch);
            $this->info("✅ {$count} report(s) generated for {$branch->name} (enabled periods only)");
        } else {
            $count = $service->generateAllScheduledReports();
            $this->info("✅ {$count} report(s) generated for all active branches (enabled periods only)");
        }
        
        return 0;
    }
}
