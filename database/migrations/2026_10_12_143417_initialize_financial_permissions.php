<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSIONS = [
        'payments.read',
        'payments.create',
        'payments.confirm',
        'payments.reject',
        'payments.void',
        'charges.read',
        'charges.edit',
        'charges.generate',
        'unpaid.read',
        'printed-bills.single',
        'printed-bills.batch',
        'fee-types.read',
        'fee-types.create',
        'fee-types.edit',
        'levy-settings.read',
        'levy-settings.edit',
        'bill-settings.read',
        'bill-settings.edit',
    ];

    public function up(): void
    {
        DB::transaction(function (): void {
            foreach (self::PERMISSIONS as $name) {
                DB::table('permissions')->insertOrIgnore([
                    'name' => $name, 'guard_name' => 'web',
                    'created_at' => now(), 'updated_at' => now(),
                ]);
            }
            $permissionIds = DB::table('permissions')->where('guard_name', 'web')
                ->whereIn('name', self::PERMISSIONS)->pluck('id');
            $roleIds = DB::table('roles')->where('guard_name', 'web')->whereIn('name', ['officer', 'administrator'])->pluck('id');
            foreach ($roleIds as $roleId) {
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
        DB::table('permissions')->where('guard_name', 'web')->whereIn('name', self::PERMISSIONS)->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
