<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Property;
use App\Models\PropertyInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PropertyInvitation>
 */
class PropertyInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'property_id' => Property::factory(),
            'role' => MembershipRole::Owner,
            'created_by_user_id' => User::factory(),
            'token_hash' => hash('sha256', Str::random(64)),
            'expires_at' => now()->addDays(30),
            'consumed_at' => null,
            'revoked_at' => null,
            'revoked_by_user_id' => null,
        ];
    }

    public function consumed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'consumed_at' => now(),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'expires_at' => now()->subSecond(),
        ]);
    }

    public function revoked(?User $revokedBy = null): static
    {
        $revokedByUserId = $revokedBy === null ? User::factory() : $revokedBy->id;

        return $this->state(fn (array $attributes): array => [
            'revoked_at' => now(),
            'revoked_by_user_id' => $revokedByUserId,
        ]);
    }
}
