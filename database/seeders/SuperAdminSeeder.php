<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Upsert the seeded Super Admin User Account.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL', 'keenvergara@gmail.com');

        if (! is_string($email) || $email === '') {
            return;
        }

        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => env('SUPER_ADMIN_NAME', 'Keen Vergara'),
            'password' => env('SUPER_ADMIN_PASSWORD', 'password'),
            'mobile_number' => env('SUPER_ADMIN_MOBILE_NUMBER', null),
            'is_super_admin' => true,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ])->save();
    }
}
