<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            $permissions = [
                'properties.read',
                'properties.create',
                'properties.edit',
                'properties.delete',
                'properties.deactivate',
                'properties.reactivate',
                'properties.import',
                'properties.import-sample',
                'properties.profiles.read',
                'properties.profiles.edit',
                'invitations.read',
                'invitations.create',
                'invitations.revoke',
                'invitations.credentials',
                'memberships.read',
                'memberships.change-role',
                'memberships.end',
                'suspends.read',
                'suspends.create',
                'suspends.edit',
            ];

            foreach ($permissions as $name) {
                DB::table('permissions')->insertOrIgnore([
                    'name' => $name, 'guard_name' => 'web',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $permissionIds = DB::table('permissions')->where('guard_name', 'web')
                ->whereIn('name', $permissions)->where('name', '!=', 'properties.import')->pluck('id');
            foreach (['officer', 'administrator'] as $name) {
                $roleId = DB::table('roles')->where('name', $name)->where('guard_name', 'web')->value('id');
                foreach ($permissionIds as $permissionId) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'role_id' => $roleId, 'permission_id' => $permissionId,
                    ]);
                }
            }
        });
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')->where('guard_name', 'web')->whereIn('name', [
            'properties.read',
            'properties.create',
            'properties.edit',
            'properties.delete',
            'properties.deactivate',
            'properties.reactivate',
            'properties.import',
            'properties.import-sample',
            'properties.profiles.read',
            'properties.profiles.edit',
            'invitations.read',
            'invitations.create',
            'invitations.revoke',
            'invitations.credentials',
            'memberships.read',
            'memberships.change-role',
            'memberships.end',
            'suspends.read',
            'suspends.create',
            'suspends.edit',
        ])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
