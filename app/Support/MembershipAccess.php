<?php

namespace App\Support;

use App\Enums\HouseholdPermission;
use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\User;

class MembershipAccess
{
    public static function canChangeRole(?User $actor, Membership $membership): bool
    {
        if (! PlatformAccess::allowsStaff($actor, HouseholdPermission::MembershipsChangeRole)) {
            return false;
        }

        return $actor !== null && ($actor->isSuperAdmin()
            || $actor->id !== $membership->user_id
            || ! $actor->roles()->where('name', PlatformRole::Administrator->value)->where('guard_name', 'web')->exists());
    }

    public static function canEnd(?User $actor, Membership $membership): bool
    {
        return $actor !== null && ($actor->id === $membership->user_id
            || PlatformAccess::allowsStaff($actor, HouseholdPermission::MembershipsEnd));
    }
}
