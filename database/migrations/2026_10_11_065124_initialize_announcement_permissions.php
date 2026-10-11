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
                'announcements.read', 'announcements.create', 'announcements.edit',
                'announcements.publish', 'announcements.pin', 'announcements.attachments',
            ];

            foreach ($permissions as $name) {
                DB::table('permissions')->insertOrIgnore([
                    'name' => $name, 'guard_name' => 'web',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }

            $permissionIds = DB::table('permissions')->where('guard_name', 'web')->whereIn('name', $permissions)->pluck('id');

            foreach (['officer', 'administrator'] as $name) {
                DB::table('roles')->insertOrIgnore([
                    'name' => $name, 'guard_name' => 'web',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
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
            'announcements.read', 'announcements.create', 'announcements.edit',
            'announcements.publish', 'announcements.pin', 'announcements.attachments',
        ])->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
