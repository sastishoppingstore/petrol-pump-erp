<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Tank;
use App\Models\CustomerPayment;
use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncNotificationsCommand extends Command
{
    protected $signature = 'erp:sync-notifications';
    protected $description = 'Sync system notifications (low stock, overdue, variance) - cron job';

    public function handle()
    {
        try {
            $this->info('Syncing notifications...');

            // Low stock alerts
            $this->syncLowStockNotifications();

            // Overdue credit alerts
            $this->syncOverdueCreditNotifications();

            // Bank reconciliation pending
            $this->syncBankReconciliationNotifications();

            $this->info('✓ Notifications synced.');
            Log::info('Notifications synchronized');
            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Sync failed: {$e->getMessage()}");
            Log::error('Notification sync failed', ['error' => $e->getMessage()]);
            return 1;
        }
    }

    protected function syncLowStockNotifications()
    {
        $tanks = Tank::where('current_stock', '<=', $this->laravel['config']['app.low_stock_threshold'])
            ->get();

        foreach ($tanks as $tank) {
            $existingNotification = Notification::where('reference_type', 'Tank')
                ->where('reference_id', $tank->id)
                ->where('type', 'LOW_STOCK')
                ->where('read_at', null)
                ->where('created_at', '>', now()->subHours(24))
                ->first();

            if (!$existingNotification) {
                Notification::create([
                    'branch_id' => $tank->branch_id,
                    'user_id' => null,
                    'type' => 'LOW_STOCK',
                    'title' => "Low Stock Alert",
                    'message' => "Tank {$tank->name} ({$tank->fuel_product_id}) is below minimum level: {$tank->current_stock} litres",
                    'reference_type' => 'Tank',
                    'reference_id' => $tank->id,
                ]);
            }
        }
    }

    protected function syncOverdueCreditNotifications()
    {
        $overdueDays = setting('credit_overdue_days', 30);
        $cutoffDate = now()->subDays($overdueDays);

        $overduePayments = CustomerPayment::where('payment_date', '<', $cutoffDate)
            ->where('status', 'PENDING')
            ->get();

        foreach ($overduePayments as $payment) {
            $existingNotification = Notification::where('reference_type', 'CustomerPayment')
                ->where('reference_id', $payment->id)
                ->where('type', 'OVERDUE_CREDIT')
                ->where('read_at', null)
                ->first();

            if (!$existingNotification) {
                Notification::create([
                    'branch_id' => $payment->branch_id,
                    'user_id' => null,
                    'type' => 'OVERDUE_CREDIT',
                    'title' => "Overdue Credit",
                    'message' => "Customer payment of {$payment->amount} is overdue since {$payment->payment_date}",
                    'reference_type' => 'CustomerPayment',
                    'reference_id' => $payment->id,
                ]);
            }
        }
    }

    protected function syncBankReconciliationNotifications()
    {
        $unreconciled = \App\Models\BankTransaction::where('reconciled', false)
            ->where('created_at', '<', now()->subDays(3))
            ->count();

        if ($unreconciled > 0) {
            Notification::create([
                'branch_id' => 1,
                'user_id' => null,
                'type' => 'BANK_RECONCILIATION',
                'title' => "Bank Reconciliation Pending",
                'message' => "{$unreconciled} unreconciled bank transactions (older than 3 days)",
                'reference_type' => 'BankTransaction',
                'reference_id' => 0,
            ]);
        }
    }
}
