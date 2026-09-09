<?php

namespace Database\Factories;

use App\Enums\MembershipRole;
use App\Models\Membership;
use App\Models\Property;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected $model = Membership::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'property_id' => Property::factory(),
            'membership_application_id' => null,
            'role' => MembershipRole::Owner,
            'started_at' => now(),
            'ended_at' => null,
            'ended_by_user_id' => null,
            'end_reason' => null,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => MembershipRole::Owner,
        ]);
    }

    public function resident(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => MembershipRole::Resident,
        ]);
    }

    public function ended(?string $reason = 'Ended'): static
    {
        return $this->state(fn (array $attributes) => [
            'ended_at' => now(),
            'end_reason' => $reason,
        ]);
    }
}
