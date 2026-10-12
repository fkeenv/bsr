<?php

namespace App\Actions\PlatformRoles;

use App\Data\PermissionOptionData;
use App\Data\RolePermissionData;
use App\Support\StaffPermissionCatalog;
use App\Support\StaffRoles;
use Spatie\Permission\Models\Role;

class ListRolePermissionsPage
{
    /** @return array{roles: list<RolePermissionData>, permissions: list<PermissionOptionData>} */
    public function handle(): array
    {
        return [
            'roles' => array_values(StaffRoles::query()->with('permissions')->withCount('users')->orderBy('name')->get()
                ->map(fn (Role $role): RolePermissionData => RolePermissionData::fromModel($role))->all()),
            'permissions' => array_map(PermissionOptionData::fromPermission(...), StaffPermissionCatalog::permissions()),
        ];
    }
}
