<?php

namespace Tests\Feature;

use App\Models\LoginAttempt;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\LoginThrottleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    private function attemptLogin(string $email = 'user@test.com', string $password = 'wrong-password'): void
    {
        $this->post('/login', ['email' => $email, 'password' => $password]);
    }

    public function test_five_failed_attempts_lock_the_account(): void
    {
        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ]);

        // The first five failures are ordinary credential errors.
        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin();
        }

        $this->assertSame(
            5,
            DB::table('login_attempts')->where('email', 'user@test.com')->where('successful', false)->count()
        );

        // The sixth request is refused *before* the password is even checked.
        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_locked_out_user_cannot_log_in_with_the_correct_password(): void
    {
        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin();
        }

        // Correct password, still refused: this is the point of the lockout.
        $response = $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->assertStringContainsString(
            'Too many failed login attempts',
            session('errors')->first('email')
        );
    }

    public function test_lockout_is_scoped_to_one_email(): void
    {
        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'locked@test.com',
            'password' => 'secret1234',
        ]);

        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'other@test.com',
            'password' => 'secret1234',
        ]);

        for ($i = 0; $i < 5; $i++) {
            $this->attemptLogin('locked@test.com');
        }

        // A different account is unaffected.
        $this->post('/login', [
            'email' => 'other@test.com',
            'password' => 'secret1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_a_successful_login_resets_the_failure_counter(): void
    {
        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ]);

        // Three failures...
        for ($i = 0; $i < 3; $i++) {
            $this->attemptLogin();
        }

        // ...then a success, which closes the window.
        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ])->assertRedirect(route('dashboard'));

        $this->post('/logout');

        // Three more failures must NOT lock the account, because they come
        // after the successful login.
        for ($i = 0; $i < 3; $i++) {
            $this->attemptLogin();
        }

        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_successful_login_is_recorded_in_login_attempts(): void
    {
        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ]);

        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ]);

        $this->assertSame(1, LoginAttempt::where('email', 'user@test.com')->where('successful', true)->count());
    }

    public function test_throttle_configuration_is_respected(): void
    {
        config(['erp.login.max_attempts' => 2, 'erp.login.lock_minutes' => 15]);

        $this->app->forgetInstance(LoginThrottleService::class);

        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ]);

        $this->attemptLogin();
        $this->attemptLogin();

        $this->post('/login', [
            'email' => 'user@test.com',
            'password' => 'secret1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }
}
