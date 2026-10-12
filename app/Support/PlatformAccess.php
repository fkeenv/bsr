<?php

namespace App\Support;

use App\Enums\AnnouncementPermission;
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

        return array_values(Role::query()
            ->whereIn('id', $user->roles()->select('roles.id')->getQuery())
            ->where('guard_name', 'web')
            ->whereIn('name', [PlatformRole::Officer->value, PlatformRole::Administrator->value])
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

    public static function allowsStaff(?User $user, HouseholdPermission|FinancialPermission $permission): bool
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

        return match ($surface) {
            'super-admin' => $user->hasRole(PlatformRole::SuperAdmin),
            'administrator' => self::hasPlatformLevelAtMost($user, PlatformRole::Administrator->level()),
            'officer' => self::hasPlatformLevelAtMost($user, PlatformRole::Officer->level()),
            default => false,
        };
    }

    public static function platformLevel(User $user): ?int
    {
        $levels = collect(PlatformRole::ordered())
            ->filter(fn (PlatformRole $role): bool => $user->hasRole($role))
            ->map(fn (PlatformRole $role): int => $role->level());

        return $levels->min();
    }

    public static function hasPlatformLevelAtMost(User $user, int $level): bool
    {
        $current = self::platformLevel($user);

        return $current !== null && $current <= $level;
    }

    public static function canHoldStaffRole(User $user): bool
    {
        return self::canAccessSurface($user, 'super-admin') || $user->hasLiveOwnerMembership();
    }
}
