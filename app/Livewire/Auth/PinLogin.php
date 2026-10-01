<?php

namespace App\Livewire\Auth;

use App\Mail\PinLoginOtpMail;
use App\Models\User;
use App\Services\Auth\LoginThrottleService;
use App\Services\System\MailSettingsService;
use App\Services\System\SettingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Component;

/**
 * Mobile-First Forecourt Cashier PIN Login Screen.
 * 4-6 digit tactile numeric keypad + cashier avatar selection.
 *
 * PINs are set BY THE ADMIN (Users screen) — there is no self-service
 * default PIN (the old "1234" fallback was removed, D-017).
 *
 * Optional email OTP (D-017): when the admin turns ON the setting
 * 'pin_login_otp', a verified PIN is followed by a 6-digit code sent
 * to the worker's email (never SMS — D-016 policy). Default: OFF.
 */
class PinLogin extends Component
{
    /** OTP kitne minutes valid rehta hai. */
    private const OTP_TTL_MINUTES = 10;

    /** OTP ki ghalat koshishon ki had — is ke baad code khatam. */
    private const OTP_MAX_ATTEMPTS = 5;

    /** Resend ke darmiyan kam az kam waqfa (seconds). */
    private const OTP_RESEND_WAIT_SECONDS = 60;

    public ?int $selectedUserId = null;
    public string $pin = '';
    public string $errorMessage = '';
    public bool $isLocked = false;

    // --- Email OTP step (sirf jab setting on ho) ---
    public bool $otpStep = false;
    public string $otp = '';
    public string $otpError = '';
    public string $otpMaskedEmail = '';
    public string $otpNotice = '';

    public function mount(): void
    {
        // If already logged in, redirect to dashboard
        if (Auth::check()) {
            $this->redirect(route('dashboard'), navigate: true);
            return;
        }

        // Auto-select first active cashier if available
        $defaultUser = $this->cashiers->first();
        if ($defaultUser) {
            $this->selectedUserId = $defaultUser->id;
        }
    }

    public function getCashiersProperty()
    {
        return User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->whereNotNull('pin')
            ->orderBy('name')
            ->get();
    }

    public function getAllActiveUsersProperty()
    {
        return User::query()
            ->where('status', User::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();
    }

    public function selectUser(int $userId): void
    {
        $this->selectedUserId = $userId;
        $this->pin = '';
        $this->errorMessage = '';
        $this->resetOtpStep();
    }

    public function enterDigit(string $digit): void
    {
        // OTP step me digits OTP box me jate hain (6 par auto-verify).
        if ($this->otpStep) {
            if (strlen($this->otp) < 6) {
                $this->otp .= $digit;
                $this->otpError = '';
                if (strlen($this->otp) === 6) {
                    $this->verifyOtp();
                }
            }
            return;
        }

        if (strlen($this->pin) < 6) {
            $this->pin .= $digit;
            $this->errorMessage = '';

            // 4/5 digits par khamoshi se try karo (PIN itni length ka ho
            // sakta hai); 6 digits par final try. Is tarah 4, 5 aur 6 —
            // teeno lambai ke PIN kaam karte hain.
            $len = strlen($this->pin);
            if ($len >= 4 && $len < 6) {
                $this->attemptLogin(silent: true);
            } elseif ($len === 6) {
                $this->attemptLogin();
            }
        }
    }

    public function backspace(): void
    {
        if ($this->otpStep) {
            $this->otp = substr($this->otp, 0, -1);
            $this->otpError = '';
            return;
        }

        if (strlen($this->pin) > 0) {
            $this->pin = substr($this->pin, 0, -1);
            $this->errorMessage = '';
        }
    }

    public function clearPin(): void
    {
        if ($this->otpStep) {
            $this->otp = '';
            $this->otpError = '';
            return;
        }

        $this->pin = '';
        $this->errorMessage = '';
    }

    /** OTP step se wapas PIN step par. */
    public function backToPin(): void
    {
        $this->resetOtpStep();
        $this->pin = '';
    }

    public function attemptLogin(bool $silent = false): void
    {
        if (! $this->selectedUserId) {
            $this->errorMessage = 'براہ کرم اپنا نام منتخب کریں۔ / Please select a cashier.';
            return;
        }

        $user = User::find($this->selectedUserId);
        if (! $user || ! $user->isActive()) {
            $this->errorMessage = 'یہ اکاؤنٹ غیر فعال ہے۔ / Account is disabled.';
            $this->pin = '';
            return;
        }

        // Check throttle on user email / code
        $throttle = app(LoginThrottleService::class);
        $key = 'pin_' . $user->email;

        if ($throttle->tooManyAttempts($key)) {
            $seconds = $throttle->secondsUntilAvailable($key);
            $this->errorMessage = "بہت زیادہ غلط کوششیں! براہ کرم {$seconds} سیکنڈ بعد کوشش کریں۔ / Locked: Too many failed attempts.";
            $this->pin = '';
            return;
        }

        // PIN admin set karta hai (Users screen). PIN hi na ho to
        // koi default nahi — worker admin se rabta kare (D-017).
        if (! $user->hasPin()) {
            $throttle->record($key, request(), false, 'NO_PIN');
            $this->errorMessage = __('ui.pinlogin.pin_not_set');
            $this->pin = '';
            return;
        }

        if (! $user->verifyPin($this->pin)) {
            // Silent try (4/5 digits): PIN lamba ho sakta hai — digits
            // mehfooz rakho, na error dikhao na throttle gino. Final
            // (6-digit) try par asal ghalti wala rawaiya.
            if ($silent) {
                return;
            }

            $throttle->record($key, request(), false, 'BAD_PIN');
            $this->errorMessage = 'غلط پن کوڈ! دوبارہ درج کریں۔ / Invalid PIN. Try again.';
            $this->pin = '';
            $this->dispatch('pin-error');
            return;
        }

        // PIN verified — throttle saaf.
        $throttle->clear($key);

        // Optional email OTP (admin setting) — on ho to pehle code
        // bhej kar OTP step dikhao, warna seedha login.
        if ($this->otpEnabled() && $this->startOtpStep($user)) {
            return;
        }

        $this->completeLogin($user);
    }

    /**
     * OTP step: code bana kar cache (hashed, 10 min) + email.
     * Email mumkin na ho (address na ho / SMTP fail) to false —
     * caller PIN-only login kar deta hai taake pump band na ho;
     * wajah log me likhi jati hai.
     */
    private function startOtpStep(User $user): bool
    {
        if (empty($user->email)) {
            Log::warning('PIN-login OTP skipped: worker has no email address', [
                'user_id' => $user->id,
            ]);
            return false;
        }

        try {
            $otp = (string) random_int(100000, 999999);

            Cache::put($this->otpCacheKey($user), Hash::make($otp), now()->addMinutes(self::OTP_TTL_MINUTES));
            Cache::put($this->otpSentAtKey($user), now()->timestamp, now()->addMinutes(self::OTP_TTL_MINUTES));
            Cache::forget($this->otpAttemptsKey($user));

            app(MailSettingsService::class)->apply();

            $stationName = app(SettingService::class)->stationIdentity()['station_name_en'] ?? 'Mehar Filling Station';

            Mail::to($user->email)->send(new PinLoginOtpMail($user, $otp, self::OTP_TTL_MINUTES, $stationName));
        } catch (\Throwable $e) {
            Log::warning('PIN-login OTP email failed — falling back to PIN-only login', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            Cache::forget($this->otpCacheKey($user));
            return false;
        }

        $this->otpStep = true;
        $this->otp = '';
        $this->otpError = '';
        $this->otpMaskedEmail = $this->maskEmail($user->email);
        $this->otpNotice = __('ui.pinlogin.otp_sent', ['email' => $this->otpMaskedEmail]);

        Log::info('PIN-login OTP sent', ['user_id' => $user->id]);

        return true;
    }

    /** OTP verify — sahi ho to asal login mukammal. */
    public function verifyOtp(): void
    {
        $user = $this->selectedUserId ? User::find($this->selectedUserId) : null;
        if (! $user || ! $this->otpStep) {
            return;
        }

        $hash = Cache::get($this->otpCacheKey($user));
        if (! $hash) {
            $this->otpError = __('ui.pinlogin.otp_expired');
            $this->otp = '';
            return;
        }

        $attempts = (int) Cache::get($this->otpAttemptsKey($user), 0);
        if ($attempts >= self::OTP_MAX_ATTEMPTS) {
            Cache::forget($this->otpCacheKey($user));
            $this->otpError = __('ui.pinlogin.otp_expired');
            $this->otp = '';
            return;
        }

        if (! Hash::check($this->otp, $hash)) {
            Cache::put($this->otpAttemptsKey($user), $attempts + 1, now()->addMinutes(self::OTP_TTL_MINUTES));
            $this->otpError = __('ui.pinlogin.otp_invalid');
            $this->otp = '';
            $this->dispatch('pin-error');
            return;
        }

        // Code sahi — saaf karo aur login mukammal karo.
        Cache::forget($this->otpCacheKey($user));
        Cache::forget($this->otpAttemptsKey($user));
        Cache::forget($this->otpSentAtKey($user));

        Log::info('PIN-login OTP verified', ['user_id' => $user->id]);

        $this->completeLogin($user);
    }

    /** Naya code bhejo (60 second ke waqfe ke baad). */
    public function resendOtp(): void
    {
        $user = $this->selectedUserId ? User::find($this->selectedUserId) : null;
        if (! $user || ! $this->otpStep) {
            return;
        }

        $sentAt = (int) Cache::get($this->otpSentAtKey($user), 0);
        if ($sentAt && (now()->timestamp - $sentAt) < self::OTP_RESEND_WAIT_SECONDS) {
            $this->otpError = __('ui.pinlogin.otp_resend_wait');
            return;
        }

        if (! $this->startOtpStep($user)) {
            // Resend par email fail — login band na karo, PIN step par wapas.
            $this->resetOtpStep();
            $this->errorMessage = __('ui.pinlogin.otp_send_failed');
        }
    }

    /** Asal login — PIN (aur zaroorat ho to OTP) ke baad. */
    private function completeLogin(User $user): void
    {
        Auth::login($user, remember: true);
        session()->regenerate();

        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => request()->ip(),
            'login_attempt_count' => 0,
        ])->save();

        Log::info('Cashier logged in via PIN', [
            'user_id' => $user->id,
            'ip' => request()->ip(),
            'otp_used' => $this->otpStep,
        ]);

        $this->redirect(route('dashboard'), navigate: true);
    }

    private function otpEnabled(): bool
    {
        $flag = strtolower(trim((string) (app(SettingService::class)->get('pin_login_otp', '0') ?? '0')));

        return ! in_array($flag, ['', '0', '0.0', '0.00', '0.0000', 'false', 'off', 'no'], true);
    }

    private function otpCacheKey(User $user): string
    {
        return 'pin_otp_' . $user->id;
    }

    private function otpAttemptsKey(User $user): string
    {
        return 'pin_otp_attempts_' . $user->id;
    }

    private function otpSentAtKey(User $user): string
    {
        return 'pin_otp_sent_at_' . $user->id;
    }

    private function resetOtpStep(): void
    {
        $this->otpStep = false;
        $this->otp = '';
        $this->otpError = '';
        $this->otpNotice = '';
        $this->otpMaskedEmail = '';
    }

    private function maskEmail(string $email): string
    {
        [$name, $domain] = array_pad(explode('@', $email, 2), 2, '');

        return mb_substr($name, 0, 2) . '***@' . $domain;
    }

    public function render()
    {
        $selectedUser = $this->selectedUserId ? User::find($this->selectedUserId) : null;

        // Display users who have PINs or all active users if none have PINs yet
        $usersList = $this->cashiers->isNotEmpty() ? $this->cashiers : $this->allActiveUsers;

        return view('livewire.auth.pin-login', [
            'users' => $usersList,
            'selectedUser' => $selectedUser,
        ])->layout('layouts.guest');
    }
}
