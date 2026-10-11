<?php

namespace App\Data;

use App\Enums\AnnouncementPermission;
use Spatie\LaravelData\Data;

class PermissionOptionData extends Data
{
    public function __construct(
        public string $value,
        public string $label,
        public string $description,
    ) {}

    public static function fromPermission(AnnouncementPermission $permission): self
    {
        return new self($permission->value, $permission->label(), $permission->description());
    }
}
