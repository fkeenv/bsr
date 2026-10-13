<?php

namespace App\Support;

use App\Enums\PlatformRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class StaffRoleAssignments
{
    public static function canManage(?User $actor, User $subject, Role $role): bool
    {
        if ($actor === null || ! StaffRoles::isEditable($role)) {
            return false;
        }
        if ($actor->isSuperAdmin()) {
            return true;
        }

        return $actor->canAccessAdministratorSurfaces() && $actor->id !== $subject->id && ! $subject->isSuperAdmin()
            && $role->name !== PlatformRole::Administrator->value;
    }

    /** @return list<string> */
    public static function snapshot(User $subject): array
    {
        return array_values(StaffRoles::query()->whereIn('id', $subject->roles()->select('roles.id')->getQuery())->orderBy('name')->pluck('name')->all());
    }
}
