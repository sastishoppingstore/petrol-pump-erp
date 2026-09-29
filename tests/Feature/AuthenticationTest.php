<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
    }

    public function test_user_can_log_in_with_valid_credentials(): void
    {
        $user = User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_is_case_insensitive_on_email(): void
    {
        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        $this->post('/login', [
            'email' => '  ADMIN@TEST.COM ',
            'password' => 'secret1234',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_wrong_password_is_rejected_and_not_authenticated(): void
    {
        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        $response = $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_disabled_user_cannot_log_in_even_with_correct_password(): void
    {
        User::factory()->disabled()->withRole(Role::ADMIN)->create([
            'email' => 'disabled@test.com',
            'password' => 'secret1234',
        ]);

        $this->post('/login', [
            'email' => 'disabled@test.com',
            'password' => 'secret1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->assertSame(
            1,
            DB::table('login_attempts')
                ->where('email', 'disabled@test.com')
                ->where('failure_reason', 'DISABLED')
                ->count()
        );
    }

    public function test_session_id_is_regenerated_on_login(): void
    {
        User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        // Grab a session id as a guest.
        $this->get('/login');
        $before = session()->getId();

        $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        $this->assertNotSame(
            $before,
            session()->getId(),
            'Session id must be regenerated on login to prevent session fixation.'
        );
    }

    public function test_last_login_details_are_recorded(): void
    {
        $user = User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        $this->post('/login', [
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        $user->refresh();

        $this->assertNotNull($user->last_login_at);
        $this->assertSame('127.0.0.1', $user->last_login_ip);
    }

    public function test_user_can_log_out(): void
    {
        $user = User::factory()->withRole(Role::ADMIN)->create([
            'email' => 'admin@test.com',
            'password' => 'secret1234',
        ]);

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/branches')->assertRedirect(route('login'));
        $this->get('/users')->assertRedirect(route('login'));
        $this->get('/roles')->assertRedirect(route('login'));
    }

    public function test_forgot_password_screen_renders(): void
    {
        $this->get('/forgot-password')->assertOk()->assertSee('Forgot your password?');
    }

    public function test_password_hash_is_never_exposed_in_user_payload(): void
    {
        $user = User::factory()->withRole(Role::ADMIN)->create();

        $this->assertArrayNotHasKey('password', $user->toArray());
        $this->assertArrayNotHasKey('remember_token', $user->toArray());
        $this->assertArrayNotHasKey('last_login_ip', $user->toArray());
    }

    public function test_user_model_hides_password_in_json_serialization(): void
    {
        $user = User::factory()->withRole(Role::ADMIN)->create([
            'password' => 'secret1234',
        ]);

        $json = json_encode($user->toArray());

        $this->assertStringNotContainsString('secret1234', $json);
        $this->assertStringNotContainsString('$2y$', $json);
    }
}
