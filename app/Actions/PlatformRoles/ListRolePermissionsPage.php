<?php

namespace App\Actions\PlatformRoles;

use App\Data\PermissionOptionData;
use App\Data\RolePermissionData;
use App\Enums\AnnouncementPermission;
use App\Enums\PlatformRole;
use Spatie\Permission\Models\Role;

class ListRolePermissionsPage
{
    /** @return array{roles: list<RolePermissionData>, permissions: list<PermissionOptionData>} */
    public function handle(): array
    {
        return [
            'roles' => array_values(Role::query()->where('guard_name', 'web')
                ->whereIn('name', [PlatformRole::Officer->value, PlatformRole::Administrator->value])
                ->with('permissions')->orderBy('name')->get()
                ->map(fn (Role $role): RolePermissionData => RolePermissionData::fromModel($role))->all()),
            'permissions' => array_map(PermissionOptionData::fromPermission(...), AnnouncementPermission::cases()),
        ];
    }
}
