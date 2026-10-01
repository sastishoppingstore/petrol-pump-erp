<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\Tank;
use App\Models\CustomerPayment;
use App\Models\Cheque;
use App\Models\Notification;
use App\Services\System\NotificationService;
use App\Support\Money;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncNotificationsCommand extends Command
{
    protected $signature = 'erp:sync-notifications';
    protected $description = 'Sync system notifications (low stock, overdue, variance) - cron job';

    public function handle(NotificationService $notifications)
    {
        try {
            $this->info('Syncing notifications...');

            // Low stock alerts
            $this->syncLowStockNotifications();

            // Overdue credit alerts
            $this->syncOverdueCreditNotifications();

            // Bank reconciliation pending
            $this->syncBankReconciliationNotifications();

            // Cheque due / overdue alerts (notifyOnce — dedupe-safe)
            $this->syncChequeDueNotifications($notifications);

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

    /**
     * Cheque due alerts — admins/managers ko notifyOnce se:
     *  - agle 3 din me due hone wale pending cheques (ek dafa per cheque)
     *  - overdue pending cheques (rozana reminder, dedupe key me date hai)
     *
     * "Pending" = abhi tak clear/bounce/cancel nahi hue: received cheques
     * RECEIVED/DEPOSITED, issued cheques ISSUED/PRESENTED halat me.
     */
    protected function syncChequeDueNotifications(NotificationService $notifications)
    {
        $pendingStatuses = [
            Cheque::STATUS_RECEIVED,
            Cheque::STATUS_DEPOSITED,
            Cheque::STATUS_ISSUED,
            Cheque::STATUS_PRESENTED,
        ];

        $today = now()->toDateString();
        $horizon = now()->addDays(3)->toDateString();

        $cheques = Cheque::query()
            ->whereIn('status', $pendingStatuses)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $horizon)
            ->orderBy('due_date')
            ->get();

        if ($cheques->isEmpty()) {
            return;
        }

        $recipients = DB::table('user_roles')
            ->join('roles', 'roles.id', '=', 'user_roles.role_id')
            ->whereIn('roles.name', ['ADMIN', 'MANAGER'])
            ->distinct()
            ->pluck('user_roles.user_id');

        if ($recipients->isEmpty()) {
            return;
        }

        foreach ($cheques as $cheque) {
            $isOverdue = $cheque->due_date->lt(now()->startOfDay());
            $party = $cheque->payee_name ?: '—';
            $detail = sprintf(
                'Cheque #%s (%s, %s) of Rs. %s — %s — due %s.',
                $cheque->cheque_number,
                $cheque->bank_name,
                $cheque->type === Cheque::TYPE_ISSUED ? 'issued to ' . $party : 'received from ' . $party,
                Money::format(Money::n($cheque->amount)),
                $cheque->type === Cheque::TYPE_ISSUED ? 'payment due' : 'deposit/clearance due',
                $cheque->due_date->format('d M Y'),
            );

            foreach ($recipients as $userId) {
                if ($isOverdue) {
                    $notifications->notifyOnce(
                        userId: (int) $userId,
                        type: 'CHEQUE_OVERDUE',
                        title: 'Cheque overdue: #' . $cheque->cheque_number,
                        message: $detail . ' This cheque is OVERDUE — please follow up today.',
                        // Rozana reminder: key me aaj ki date hai.
                        dedupeKey: 'cheque:' . $cheque->id . ':overdue:' . $today,
                        level: Notification::LEVEL_CRITICAL,
                        module: 'cheques',
                        referenceType: Cheque::class,
                        referenceId: $cheque->id,
                    );
                } else {
                    $notifications->notifyOnce(
                        userId: (int) $userId,
                        type: 'CHEQUE_DUE',
                        title: 'Cheque due soon: #' . $cheque->cheque_number,
                        message: $detail,
                        // Har cheque ke liye sirf ek dafa (due date key me hai).
                        dedupeKey: 'cheque:' . $cheque->id . ':due-soon:' . $cheque->due_date->toDateString(),
                        level: Notification::LEVEL_WARNING,
                        module: 'cheques',
                        referenceType: Cheque::class,
                        referenceId: $cheque->id,
                    );
                }
            }
        }
    }
}
