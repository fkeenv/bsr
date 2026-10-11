<?php

namespace App\Support;

use App\Enums\PlatformRole;
use App\Models\User;

class PlatformAccess
{
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
