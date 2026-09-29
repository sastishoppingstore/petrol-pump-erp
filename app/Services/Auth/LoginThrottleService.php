<?php

namespace App\Services\Auth;

use App\Models\LoginAttempt;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Login throttling backed by the append-only `login_attempts` table.
 *
 * Rule (spec section 1): after N consecutive failed attempts the email
 * address is locked for M minutes. Counters reset on a successful login.
 */
class LoginThrottleService
{
    public function __construct(
        private readonly int $maxAttempts,
        private readonly int $lockMinutes,
    ) {
    }

    public static function fromConfig(): self
    {
        return new self(
            maxAttempts: (int) config('erp.login.max_attempts', 5),
            lockMinutes: (int) config('erp.login.lock_minutes', 15),
        );
    }

    /**
     * Record a login attempt (successful or not).
     */
    public function record(string $email, Request $request, bool $successful, ?string $reason = null): void
    {
        LoginAttempt::create([
            'email' => mb_strtolower(trim($email)),
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 255),
            'successful' => $successful,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * How many consecutive failures for this email since the last success.
     */
    public function attemptsRemaining(string $email): int
    {
        $attempts = $this->recentFailures($email);

        return max(0, $this->maxAttempts - $attempts);
    }

    public function tooManyAttempts(string $email): bool
    {
        return $this->recentFailures($email) >= $this->maxAttempts;
    }

    /**
     * Seconds until the lockout expires (0 when not locked).
     */
    public function secondsUntilAvailable(string $email): int
    {
        if (! $this->tooManyAttempts($email)) {
            return 0;
        }

        $lastFailure = LoginAttempt::query()
            ->where('email', mb_strtolower(trim($email)))
            ->where('successful', false)
            ->latest('created_at')
            ->first();

        if (! $lastFailure) {
            return 0;
        }

        $unlockAt = $lastFailure->created_at->copy()->addMinutes($this->lockMinutes);
        $remaining = Carbon::now()->diffInSeconds($unlockAt, false);

        return $remaining > 0 ? (int) $remaining : 0;
    }

    /**
     * Clear the counter for this email (called after a successful login).
     */
    public function clear(string $email): void
    {
        // Nothing to delete: login_attempts is append-only. A success simply
        // resets the window, which the query below accounts for.
    }

    private function recentFailures(string $email): int
    {
        $email = mb_strtolower(trim($email));

        $lastSuccess = LoginAttempt::query()
            ->where('email', $email)
            ->where('successful', true)
            ->latest('created_at')
            ->value('created_at');

        return LoginAttempt::query()
            ->where('email', $email)
            ->where('successful', false)
            ->when(
                $lastSuccess,
                fn ($q) => $q->where('created_at', '>', $lastSuccess)
            )
            ->count();
    }
}
