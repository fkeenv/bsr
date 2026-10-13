<?php

namespace Database\Factories;

use App\Models\StaffRoleAssignmentChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StaffRoleAssignmentChange> */
class StaffRoleAssignmentChangeFactory extends Factory
{
    /** @return array<string,mixed> */
    public function definition(): array
    {
        return ['actor_user_id' => User::factory(), 'user_id' => User::factory(), 'action' => 'assign', 'previous_assignments' => [], 'new_assignments' => ['officer'], 'reason' => null];
    }
}
