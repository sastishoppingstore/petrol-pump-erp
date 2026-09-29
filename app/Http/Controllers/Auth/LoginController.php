<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\Auth\LoginThrottleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Handles the login screen. Throttling, session regeneration, remember-me
 * and last-login bookkeeping all live here; the throttle maths itself is in
 * LoginThrottleService.
 */
class LoginController extends Controller
{
    public function __construct(
        private readonly LoginThrottleService $throttle,
    ) {
    }

    public function show(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request): RedirectResponse
    {
        $email = (string) $request->validated('email');

        if ($this->throttle->tooManyAttempts($email)) {
            $seconds = $this->throttle->secondsUntilAvailable($email);
            $minutes = max(1, (int) ceil($seconds / 60));

            $this->throttle->record($email, $request, false, 'LOCKED');

            throw ValidationException::withMessages([
                'email' => "Too many failed login attempts. Please try again in {$minutes} minute(s).",
            ]);
        }

        $credentials = [
            'email' => $email,
            'password' => $request->validated('password'),
        ];

        $user = User::where('email', $email)->first();

        // Disabled accounts must never authenticate, even with a correct password.
        if ($user && ! $user->isActive()) {
            $this->throttle->record($email, $request, false, 'DISABLED');

            // Deliberately the same message as a bad password, so this cannot
            // be used to enumerate which accounts exist.
            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if (! Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            $this->throttle->record($email, $request, false, 'BAD_CREDENTIALS');

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        $this->throttle->record($email, $request, true);
        $this->throttle->clear($email);

        // New session id on privilege change (defends against session fixation).
        $request->session()->regenerate();

        $user = $request->user();
        $user->forceFill([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
            'login_attempt_count' => 0,
        ])->save();

        Log::info('User logged in', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }
}
