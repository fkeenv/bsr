<?php

namespace App\Support;

use App\Enums\AnnouncementPermission;
use App\Enums\HouseholdPermission;

class StaffPermissionCatalog
{
    /** @return list<AnnouncementPermission|HouseholdPermission> */
    public static function permissions(): array
    {
        return [...AnnouncementPermission::cases(), ...HouseholdPermission::cases()];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (AnnouncementPermission|HouseholdPermission $permission): string => $permission->value, self::permissions());
    }
}
