<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password = null;

    public function definition(): array
    {
        return [
            'employee_code' => strtoupper(Str::random(6)),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('03#########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => User::STATUS_ACTIVE,
            'must_change_password' => false,
            'remember_token' => Str::random(10),
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn () => ['status' => User::STATUS_DISABLED]);
    }

    /**
     * Attach a role, creating the row if the seeders have not run.
     */
    public function withRole(string $roleName): static
    {
        return $this->afterCreating(function (User $user) use ($roleName) {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                [
                    'label' => ucfirst(strtolower($roleName)),
                    'is_super_admin' => $roleName === Role::ADMIN,
                    'status' => 'ACTIVE',
                ]
            );

            $user->roles()->syncWithoutDetaching([$role->id]);
        });
    }

    public function superAdmin(): static
    {
        return $this->withRole(Role::ADMIN);
    }
}
