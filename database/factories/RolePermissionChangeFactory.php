<?php

namespace Database\Factories;

use App\Models\RolePermissionChange;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/** @extends Factory<RolePermissionChange> */
class RolePermissionChangeFactory extends Factory
{
    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'actor_user_id' => User::factory()->superAdmin(),
            'role_id' => fn (): int|string => Role::findByName('officer', 'web')->id,
            'role_name' => 'officer',
            'previous_grants' => ['announcements.read', 'announcements.create'],
            'new_grants' => ['announcements.read'],
        ];
    }
}
