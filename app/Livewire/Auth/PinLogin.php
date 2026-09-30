<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\Auth\LoginThrottleService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Mobile-First Forecourt Cashier PIN Login Screen.
 * 4-6 digit tactile numeric keypad + cashier avatar selection.
 */
class PinLogin extends Component
{
    public ?int $selectedUserId = null;
    public string $pin = '';
    public string $errorMessage = '';
    public bool $isLocked = false;

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
    }

    public function enterDigit(string $digit): void
    {
        if (strlen($this->pin) < 6) {
            $this->pin .= $digit;
            $this->errorMessage = '';

            // Auto-submit on 4 or 6 digits if user has a PIN of that length
            if (strlen($this->pin) >= 4) {
                $this->attemptLogin();
            }
        }
    }

    public function backspace(): void
    {
        if (strlen($this->pin) > 0) {
            $this->pin = substr($this->pin, 0, -1);
            $this->errorMessage = '';
        }
    }

    public function clearPin(): void
    {
        $this->pin = '';
        $this->errorMessage = '';
    }

    public function attemptLogin(): void
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

        if (! $user->verifyPin($this->pin)) {
            // If user doesn't have a PIN set yet, allow default 1234 for testing/convenience or report
            if (empty($user->pin) && $this->pin === '1234') {
                $user->setPin('1234');
                $user->save();
            } else {
                $throttle->record($key, request(), false, 'BAD_PIN');
                $this->errorMessage = 'غلط پن کوڈ! دوبارہ درج کریں۔ / Invalid PIN. Try again.';
                $this->pin = '';
                $this->dispatch('pin-error');
                return;
            }
        }

        // Successfully verified
        $throttle->clear($key);
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
        ]);

        $this->redirect(route('dashboard'), navigate: true);
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
