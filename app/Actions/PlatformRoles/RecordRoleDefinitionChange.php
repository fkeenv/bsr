<?php

namespace App\Actions\PlatformRoles;

use App\Models\RolePermissionChange;
use App\Models\User;
use Spatie\Permission\Models\Role;

/** @phpstan-type RoleDefinitionSnapshot array{name: string, grants: list<string>} */
class RecordRoleDefinitionChange
{
    /**
     * @param  RoleDefinitionSnapshot|null  $previousState
     * @param  RoleDefinitionSnapshot|null  $newState
     */
    public function handle(User $actor, Role $role, string $action, ?array $previousState, ?array $newState): void
    {
        RolePermissionChange::query()->create([
            'actor_user_id' => $actor->id, 'role_id' => $role->id, 'role_name' => $role->name,
            'action' => $action,
            'previous_role_name' => $previousState['name'] ?? null,
            'new_role_name' => $newState['name'] ?? null,
            'previous_grants' => $previousState['grants'] ?? [],
            'new_grants' => $newState['grants'] ?? [],
        ]);
    }
}
