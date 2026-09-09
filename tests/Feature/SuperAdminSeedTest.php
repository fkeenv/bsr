<?php

use App\Models\User;
use Database\Seeders\SuperAdminSeeder;
use Illuminate\Support\Facades\Hash;

test('seeding creates an authenticatable Super Admin', function () {
    config([
        'bsr.super_admin.name' => 'Super Admin',
        'bsr.super_admin.email' => 'superadmin@example.com',
        'bsr.super_admin.password' => 'secret-password',
        'bsr.super_admin.mobile_number' => '+639171234567',
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
    config([
        'bsr.super_admin.name' => 'Super Admin',
        'bsr.super_admin.email' => 'superadmin@example.com',
        'bsr.super_admin.password' => 'secret-password',
        'bsr.super_admin.mobile_number' => '+639171234567',
    ]);

    $this->seed(SuperAdminSeeder::class);

    config([
        'bsr.super_admin.name' => 'Updated Super Admin',
        'bsr.super_admin.password' => 'new-secret-password',
        'bsr.super_admin.mobile_number' => '+639179999999',
    ]);

    $this->seed(SuperAdminSeeder::class);

    expect(User::query()->where('email', 'superadmin@example.com')->count())->toBe(1);

    $user = User::query()->where('email', 'superadmin@example.com')->first();

    expect($user->name)->toBe('Updated Super Admin')
        ->and($user->mobile_number)->toBe('+639179999999')
        ->and($user->is_super_admin)->toBeTrue()
        ->and(Hash::check('new-secret-password', $user->password))->toBeTrue();
});
