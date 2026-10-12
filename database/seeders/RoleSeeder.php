<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Support\StaffPermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        foreach (StaffPermissionCatalog::permissions() as $permission) {
            Permission::findOrCreate($permission->value, 'web');
        }

        foreach (PlatformRole::ordered() as $role) {
            Role::findOrCreate($role->value, 'web');
        }
    }
}
