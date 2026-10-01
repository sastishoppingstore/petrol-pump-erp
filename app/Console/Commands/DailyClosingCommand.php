<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Services\Shift\DailyClosingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class DailyClosingCommand extends Command
{
    protected $signature = 'erp:daily-closing {--branch=} {--date=}';
    protected $description = 'Run daily closing for a branch (cron job)';

    public function handle(DailyClosingService $closingService)
    {
        $branchId = $this->option('branch');
        $date = $this->option('date') ?? now()->toDateString();

        if (!$branchId) {
            $this->error('Branch ID required: php artisan erp:daily-closing --branch=1');
            return 1;
        }

        $branch = Branch::findOrFail($branchId);

        try {
            $this->info("Running daily closing for {$branch->name} on {$date}...");

            $result = $closingService->executeClosing($branchId, $date);

            $this->info("✓ Daily closing completed.");
            $this->info("  Total sales: {$result['total_sales']}");
            $this->info("  Fuel sold: {$result['fuel_sold']} litres");
            $this->info("  Variance: {$result['variance']}");

            Log::info('Daily closing completed', ['branch_id' => $branchId, 'date' => $date]);
            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Daily closing failed: {$e->getMessage()}");
            Log::error('Daily closing failed', ['branch_id' => $branchId, 'date' => $date, 'error' => $e->getMessage()]);
            return 1;
        }
    }
}
