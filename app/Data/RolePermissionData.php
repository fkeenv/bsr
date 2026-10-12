<?php

namespace App\Data;

use App\Support\StaffPermissionCatalog;
use App\Support\StaffRoles;
use Illuminate\Support\Str;
use Spatie\LaravelData\Data;
use Spatie\Permission\Models\Role;

class RolePermissionData extends Data
{
    /** @param list<string> $grants */
    public function __construct(
        public string $name,
        public string $label,
        public array $grants,
        public bool $is_builtin,
        public int $assigned_count,
        public int|string $id,
    ) {}

    public static function fromModel(Role $role): self
    {
        return new self(
            $role->name,
            Str::headline($role->name),
            array_values($role->permissions->pluck('name')->filter(
                fn (string $name): bool => in_array($name, StaffPermissionCatalog::values(), true),
            )->values()->all()),
            ! StaffRoles::isCustom($role),
            (int) $role->getAttribute('users_count'),
            $role->id,
        );
    }
}
