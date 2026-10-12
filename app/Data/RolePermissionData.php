<?php

namespace App\Data;

use App\Support\StaffPermissionCatalog;
use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Role;

class RolePermissionData extends Data
{
    /** @param list<string> $grants */
    public function __construct(
        public string $name,
        public string $label,
        public array $grants,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            $role->name,
            ucfirst($role->name),
            array_values($role->permissions->pluck('name')->filter(
                fn (string $name): bool => in_array($name, StaffPermissionCatalog::values(), true),
            )->values()->all()),
        );
    }
}
