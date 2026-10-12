<?php

namespace App\Data;

use App\Enums\AnnouncementPermission;
use App\Enums\FinancialPermission;
use App\Enums\HouseholdPermission;
use Spatie\LaravelData\Data;

class PermissionOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
        public string $description,
        public string $feature,
    ) {}

    public static function fromPermission(AnnouncementPermission|HouseholdPermission|FinancialPermission $permission): self
    {
        return new self($permission->value, $permission->label(), $permission->description(),
            $permission instanceof AnnouncementPermission ? 'Announcements' : $permission->feature());
    }
}
