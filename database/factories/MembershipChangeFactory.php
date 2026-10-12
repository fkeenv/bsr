<?php

namespace Database\Factories;

use App\Models\Membership;
use App\Models\MembershipChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MembershipChange> */
class MembershipChangeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'membership_id' => Membership::factory(),
            'actor_user_id' => User::factory()->superAdmin(),
            'user_id' => fn (array $attributes): int => Membership::query()->whereKey($attributes['membership_id'])->sole()->user_id,
            'action' => 'end',
            'previous_state' => ['role' => 'owner', 'ended_at' => null, 'staff_roles' => ['officer']],
            'new_state' => ['role' => 'owner', 'ended_at' => now()->toIso8601String(), 'staff_roles' => []],
        ];
    }
}
