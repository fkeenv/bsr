<?php

namespace App\Actions\PlatformRoles;

use App\Models\User;
use App\Support\StaffRoles;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RenameStaffRole
{
    public function __construct(private RecordRoleDefinitionChange $recordChange) {}

    public function handle(User $actor, Role $role, string $name): Role
    {
        abort_unless($actor->isSuperAdmin(), 403);

        return DB::transaction(function () use ($actor, $role, $name): Role {
            $lockedRole = Role::query()->lockForUpdate()->findOrFail($role->id);
            abort_unless(StaffRoles::isCustom($lockedRole), 403);
            $name = StaffRoles::validatedName($name, $lockedRole);
            $previousName = $lockedRole->name;
            if ($previousName === $name) {
                return $lockedRole;
            }
            $grants = array_values($lockedRole->permissions->map(fn (Permission $permission): string => $permission->name)->sort()->all());
            $lockedRole->fill(['name' => $name])->save();
            $this->recordChange->handle($actor, $lockedRole, 'rename',
                ['name' => $previousName, 'grants' => $grants], ['name' => $name, 'grants' => $grants]);

            return $lockedRole;
        });
    }
}
