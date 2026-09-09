<?php

namespace Database\Factories;

use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'mobile_number' => fake()->numerify('+639#########'),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'two_factor_secret' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at' => null,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->ensureRolesExist();

            if ($user->roles()->exists()) {
                return;
            }

            $user->assignRole(PlatformRole::User);
        });
    }

    public function superAdmin(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->ensureRolesExist();
            $user->syncRoles([
                PlatformRole::SuperAdmin,
                PlatformRole::User,
            ]);
        });
    }

    public function administrator(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->ensureRolesExist();
            $user->syncRoles([
                PlatformRole::Administrator,
                PlatformRole::Member,
                PlatformRole::User,
            ]);
        });
    }

    public function officer(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->ensureRolesExist();
            $user->syncRoles([
                PlatformRole::Officer,
                PlatformRole::Member,
                PlatformRole::User,
            ]);
        });
    }

    public function member(): static
    {
        return $this->afterCreating(function (User $user): void {
            $this->ensureRolesExist();
            $user->syncRoles([
                PlatformRole::Member,
                PlatformRole::User,
            ]);
        });
    }

    private function ensureRolesExist(): void
    {
        foreach (PlatformRole::ordered() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Indicate that the model has two-factor authentication configured.
     */
    public function withTwoFactor(): static
    {
        return $this->state(fn (array $attributes) => [
            'two_factor_secret' => encrypt('secret'),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-1'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }
}
