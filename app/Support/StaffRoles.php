<?php

namespace App\Support;

use App\Enums\PlatformRole;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class StaffRoles
{
    /** @return list<string> */
    public static function builtInNames(): array
    {
        return array_map(fn (PlatformRole $role): string => $role->value, PlatformRole::ordered());
    }

    /** @return Builder<Role> */
    public static function query(): Builder
    {
        return Role::query()->where('guard_name', 'web')
            ->whereNotIn('name', [PlatformRole::SuperAdmin->value, PlatformRole::Member->value, PlatformRole::User->value]);
    }

    public static function isCustom(Role $role): bool
    {
        return $role->guard_name === 'web' && ! in_array($role->name, self::builtInNames(), true);
    }

    public static function isEditable(Role $role): bool
    {
        return $role->guard_name === 'web' && ! in_array($role->name, [
            PlatformRole::SuperAdmin->value, PlatformRole::Member->value, PlatformRole::User->value,
        ], true);
    }

    /** @return list<mixed> */
    public static function nameRules(?Role $role = null): array
    {
        return ['bail', 'required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9]*(?:-[a-z0-9]+)*$/',
            Rule::notIn(self::builtInNames()), Rule::unique('roles', 'name')->where('guard_name', 'web')->ignore($role)];
    }

    public static function validatedName(string $name, ?Role $role = null): string
    {
        $name = Str::slug($name);
        Validator::make(['name' => $name], ['name' => self::nameRules($role)])->validate();

        return $name;
    }
}
