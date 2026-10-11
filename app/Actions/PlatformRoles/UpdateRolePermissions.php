<?php

namespace App\Actions\PlatformRoles;

use App\Enums\AnnouncementPermission;
use App\Enums\PlatformRole;
use App\Models\RolePermissionChange;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UpdateRolePermissions
{
    /** @param list<string> $grants */
    public function handle(User $actor, Role $role, array $grants): void
    {
        abort_unless($actor->isSuperAdmin(), 403);

        if (array_diff($grants, AnnouncementPermission::values()) !== []) {
            throw ValidationException::withMessages(['grants' => 'Choose permissions from the available catalog.']);
        }

        DB::transaction(function () use ($actor, $role, $grants): void {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->id);
            abort_unless($lockedRole->guard_name === 'web' && in_array($lockedRole->name, [
                PlatformRole::Officer->value, PlatformRole::Administrator->value,
            ], true), 403);

            $previousGrants = $lockedRole->permissions()->pluck('name')->sort()->values()->all();
            $newGrants = array_values(array_unique([
                ...array_diff($previousGrants, AnnouncementPermission::values()),
                ...$grants,
            ]));
            sort($newGrants);

            if ($previousGrants === $newGrants) {
                return;
            }

            $lockedRole->syncPermissions($newGrants);

            RolePermissionChange::query()->create([
                'actor_user_id' => $actor->id,
                'role_id' => $lockedRole->id,
                'role_name' => $lockedRole->name,
                'previous_grants' => $previousGrants,
                'new_grants' => $newGrants,
            ]);
        });
    }
}
