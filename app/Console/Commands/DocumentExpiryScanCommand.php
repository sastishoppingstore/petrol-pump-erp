<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\StationDocument;
use App\Services\System\NotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Document Vault — rozana expiry escalation scan.
 *
 * Upload ke waqt sirf ek heads-up milta tha; ye command tamam active
 * documents ko roz dobara scan karke teen tiers par admins/managers ko
 * alert karta hai:
 *   - 30 din baqi  → tier "30d"  (WARNING)
 *   - 7 din baqi   → tier "7d"   (WARNING, zyada urgent)
 *   - expiry guzar gayi → tier "expired" (CRITICAL)
 *
 * Har tier ki dedupe key alag hai aur expiry_date key ka hissa hai —
 * document renew ho (nayi expiry date) to alerts dobara ja sakte hain,
 * warna har tier sirf ek dafa jata hai. Command rozana 08:05 par
 * bootstrap/app.php ke schedule se chalta hai.
 */
class DocumentExpiryScanCommand extends Command
{
    protected $signature = 'documents:scan-expiry';
    protected $description = 'Scan Document Vault for expiring/expired documents and alert admins (30d / 7d / expired tiers)';

    public function handle(NotificationService $notifications): int
    {
        try {
            $this->info('Scanning documents for expiry...');

            $today = now()->startOfDay();

            $documents = StationDocument::query()
                ->whereNotNull('expiry_date')
                ->whereDate('expiry_date', '<=', $today->copy()->addDays(30))
                ->orderBy('expiry_date')
                ->get();

            if ($documents->isEmpty()) {
                $this->info('✓ No documents near expiry.');
                return self::SUCCESS;
            }

            $recipients = DB::table('user_roles')
                ->join('roles', 'roles.id', '=', 'user_roles.role_id')
                ->whereIn('roles.name', ['ADMIN', 'MANAGER'])
                ->distinct()
                ->pluck('user_roles.user_id');

            if ($recipients->isEmpty()) {
                $this->warn('No admin/manager recipients found — skipping notifications.');
                return self::SUCCESS;
            }

            $created = 0;

            foreach ($documents as $document) {
                $expiry = $document->expiry_date->copy()->startOfDay();
                $daysLeft = (int) $today->diffInDays($expiry, false);

                [$tier, $level, $title, $message] = match (true) {
                    $daysLeft < 0 => [
                        'expired',
                        Notification::LEVEL_CRITICAL,
                        'Document EXPIRED: ' . $document->title,
                        sprintf(
                            'Document "%s" (%s) expired on %s — %d day(s) ago. Please renew it from the Document Vault immediately.',
                            $document->title,
                            $document->categoryLabel(),
                            $expiry->format('d M Y'),
                            abs($daysLeft),
                        ),
                    ],
                    $daysLeft <= 7 => [
                        '7d',
                        Notification::LEVEL_WARNING,
                        'Document expiring this week: ' . $document->title,
                        sprintf(
                            'Document "%s" (%s) expires on %s — only %d day(s) left. Renewal should be started now.',
                            $document->title,
                            $document->categoryLabel(),
                            $expiry->format('d M Y'),
                            $daysLeft,
                        ),
                    ],
                    default => [
                        '30d',
                        Notification::LEVEL_WARNING,
                        'Document expiring soon: ' . $document->title,
                        sprintf(
                            'Document "%s" (%s) expires on %s — %d day(s) left. Please plan its renewal.',
                            $document->title,
                            $document->categoryLabel(),
                            $expiry->format('d M Y'),
                            $daysLeft,
                        ),
                    ],
                };

                foreach ($recipients as $userId) {
                    $notification = $notifications->notifyOnce(
                        userId: (int) $userId,
                        type: 'DOCUMENT_EXPIRY',
                        title: $title,
                        message: $message,
                        // Tier-wise alag key: 30d → 7d → expired har stage
                        // par ek nayi notification ja sakti hai, lekin ek
                        // stage dobara spam nahi karti. Expiry date key me
                        // hai taake renewal ke baad nayi date par alerts
                        // phir se ja sakain.
                        dedupeKey: 'document:' . $document->id . ':expiry-tier:' . $tier . ':' . $expiry->toDateString(),
                        level: $level,
                        module: 'DocumentVault',
                        referenceType: StationDocument::class,
                        referenceId: $document->id,
                    );

                    if ($notification) {
                        $created++;
                    }
                }
            }

            $this->info("✓ Document expiry scan complete — {$created} notification(s) created.");
            Log::info('Document expiry scan complete', ['created' => $created, 'scanned' => $documents->count()]);

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("✗ Document expiry scan failed: {$e->getMessage()}");
            Log::error('Document expiry scan failed', ['error' => $e->getMessage()]);
            return self::FAILURE;
        }
    }
}
