<?php

namespace Tests\Feature;

use App\Livewire\Auth\PinLogin;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PinLoginTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();

        $this->branch = Branch::factory()->create([
            'name' => 'Mehar Filling Station',
            'code' => 'MEHAR1',
            'status' => Branch::STATUS_ACTIVE,
        ]);

        $this->cashier = User::factory()->withRole(Role::CASHIER)->create([
            'name' => 'Kashif Cashier',
            'employee_code' => 'CSH-101',
            'email' => 'kashif@meharpetrol.com',
            'status' => User::STATUS_ACTIVE,
        ]);
        $this->cashier->branches()->attach($this->branch->id, ['is_default' => true]);
        $this->cashier->setPin('1234');
        $this->cashier->save();
    }

    public function test_pin_login_page_renders_with_keypad_and_avatars(): void
    {
        $response = $this->get('/pin-login');

        $response->assertOk();
        $response->assertSee('کیشئر لاگ اِن (PIN)');
        $response->assertSee('VITAL PETROLEUM');
        $response->assertSee('مہر فلنگ اسٹیشن');
        $response->assertSee('Kashif Cashier');
        $response->assertSee('CSH-101');
    }

    public function test_cashier_can_login_with_valid_pin(): void
    {
        Livewire::test(PinLogin::class)
            ->set('selectedUserId', $this->cashier->id)
            ->call('enterDigit', '1')
            ->call('enterDigit', '2')
            ->call('enterDigit', '3')
            ->call('enterDigit', '4')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->cashier);
    }

    public function test_cashier_login_fails_with_invalid_pin(): void
    {
        Livewire::test(PinLogin::class)
            ->set('selectedUserId', $this->cashier->id)
            ->call('enterDigit', '9')
            ->call('enterDigit', '9')
            ->call('enterDigit', '9')
            ->call('enterDigit', '9')
            ->assertSee('غلط پن کوڈ');

        $this->assertGuest();
    }

    public function test_auto_lock_pin_verification_endpoint(): void
    {
        $this->actingAs($this->cashier);

        // Correct PIN
        $response = $this->postJson(route('user.verify-pin'), [
            'pin' => '1234',
        ]);
        $response->assertOk()->assertJson(['valid' => true]);

        // Wrong PIN
        $response = $this->postJson(route('user.verify-pin'), [
            'pin' => '9999',
        ]);
        $response->assertStatus(422)->assertJson(['valid' => false]);
    }
}
