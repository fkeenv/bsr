<?php

namespace Database\Seeders;

use App\Enums\PlatformRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = config('bsr.super_admin.email');

        if (! is_string($email) || $email === '') {
            return;
        }

        $this->call(RoleSeeder::class);

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => config('bsr.super_admin.name'),
            'password' => config('bsr.super_admin.password'),
            'mobile_number' => config('bsr.super_admin.mobile_number'),
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();

        $user->syncRoles([
            PlatformRole::SuperAdmin,
            PlatformRole::User,
        ]);
    }
}
