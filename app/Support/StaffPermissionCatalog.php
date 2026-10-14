<?php

namespace App\Support;

use App\Enums\AnnouncementPermission;
use App\Enums\EventPermission;
use App\Enums\FinancialPermission;
use App\Enums\HouseholdPermission;

class StaffPermissionCatalog
{
    /** @return list<AnnouncementPermission|HouseholdPermission|FinancialPermission|EventPermission> */
    public static function permissions(): array
    {
        return [...AnnouncementPermission::cases(), ...HouseholdPermission::cases(), ...FinancialPermission::cases(), ...EventPermission::cases()];
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (AnnouncementPermission|HouseholdPermission|FinancialPermission|EventPermission $permission): string => $permission->value, self::permissions());
    }
}
