<?php

namespace App\Support;

use App\Enums\AnnouncementPermission;
use App\Enums\EventPermission;
use App\Enums\FinancialPermission;
use App\Enums\HouseholdPermission;
use App\Enums\PlatformRole;
use App\Models\User;
use Spatie\Permission\Models\Role;

class PlatformAccess
{
    /** @return list<string> */
    public static function staffPermissions(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        if (self::canAccessSurface($user, 'super-admin')) {
            return StaffPermissionCatalog::values();
        }

        if (! $user->hasLiveOwnerMembership()) {
            return [];
        }

        return array_values(StaffRoles::query()
            ->whereIn('id', $user->roles()->select('roles.id')->getQuery())
            ->with('permissions')
            ->get()
            ->flatMap(fn (Role $role) => $role->permissions->pluck('name'))
            ->filter(fn (string $name): bool => in_array($name, StaffPermissionCatalog::values(), true))
            ->unique()
            ->values()
            ->all());
    }

    /** @return list<string> */
    public static function announcementPermissions(?User $user): array
    {
        return array_values(array_intersect(self::staffPermissions($user), AnnouncementPermission::values()));
    }

    public static function allowsStaff(?User $user, HouseholdPermission|FinancialPermission|EventPermission $permission): bool
    {
        return in_array($permission->value, self::staffPermissions($user), true);
    }

    public static function allowsAnnouncement(?User $user, AnnouncementPermission $permission): bool
    {
        return in_array($permission->value, self::announcementPermissions($user), true);
    }

    /**
     * @return array{isSuperAdmin: bool, canAccessOfficer: bool, canAccessAdministrator: bool, isMembershipHolder: bool}
     */
    public static function capabilities(User $user): array
    {
        return [
            'isSuperAdmin' => self::canAccessSurface($user, 'super-admin'),
            'canAccessOfficer' => self::canAccessSurface($user, 'officer'),
            'canAccessAdministrator' => self::canAccessSurface($user, 'administrator'),
            'isMembershipHolder' => $user->hasLiveMembership(),
        ];
    }

    public static function canAccessSurface(?User $user, string $surface): bool
    {
        if ($user === null) {
            return false;
        }

        $isSuperAdmin = $user->roles()->where('guard_name', 'web')->where('name', PlatformRole::SuperAdmin->value)->exists();

        return match ($surface) {
            'super-admin' => $isSuperAdmin,
            'administrator' => $isSuperAdmin || ($user->hasLiveOwnerMembership()
                && $user->roles()->where('guard_name', 'web')->where('name', PlatformRole::Administrator->value)->exists()),
            'officer' => $isSuperAdmin || ($user->hasLiveOwnerMembership()
                && StaffRoles::query()->whereIn('id', $user->roles()->select('roles.id')->getQuery())->exists()),
            default => false,
        };
    }

    public static function canHoldStaffRole(User $user): bool
    {
        return self::canAccessSurface($user, 'super-admin') || $user->hasLiveOwnerMembership();
    }
}
