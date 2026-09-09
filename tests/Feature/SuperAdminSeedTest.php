<?php

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Hash;

/**
 * @param  array<string, string|null>  $variables
 */
function setSuperAdminEnv(array $variables): void
{
    foreach ($variables as $key => $value) {
        if ($value === null) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);

            continue;
        }

        putenv("{$key}={$value}");
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

test('seeding creates an authenticatable Super Admin', function () {
    setSuperAdminEnv([
        'SUPER_ADMIN_NAME' => 'Super Admin',
        'SUPER_ADMIN_EMAIL' => 'superadmin@example.com',
        'SUPER_ADMIN_PASSWORD' => 'secret-password',
        'SUPER_ADMIN_MOBILE_NUMBER' => '+639171234567',
    ]);

    $this->seed(SuperAdminSeeder::class);

    $user = User::query()->where('email', 'superadmin@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('Super Admin')
        ->and($user->mobile_number)->toBe('+639171234567')
        ->and($user->is_super_admin)->toBeTrue()
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();

    expect(auth()->attempt([
        'email' => 'superadmin@example.com',
        'password' => 'secret-password',
    ]))->toBeTrue();
});

test('seeding upserts the Super Admin by email', function () {
    setSuperAdminEnv([
        'SUPER_ADMIN_NAME' => 'Super Admin',
        'SUPER_ADMIN_EMAIL' => 'superadmin@example.com',
        'SUPER_ADMIN_PASSWORD' => 'secret-password',
        'SUPER_ADMIN_MOBILE_NUMBER' => '+639171234567',
    ]);

    $this->seed(SuperAdminSeeder::class);

    setSuperAdminEnv([
        'SUPER_ADMIN_NAME' => 'Updated Super Admin',
        'SUPER_ADMIN_PASSWORD' => 'new-secret-password',
        'SUPER_ADMIN_MOBILE_NUMBER' => '+639179999999',
    ]);

    $this->seed(SuperAdminSeeder::class);

    expect(User::query()->where('email', 'superadmin@example.com')->count())->toBe(1);

    $user = User::query()->where('email', 'superadmin@example.com')->first();

    expect($user->name)->toBe('Updated Super Admin')
        ->and($user->mobile_number)->toBe('+639179999999')
        ->and($user->is_super_admin)->toBeTrue()
        ->and(Hash::check('new-secret-password', $user->password))->toBeTrue();
});
