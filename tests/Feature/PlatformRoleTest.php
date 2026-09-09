<?php

use App\Enums\PlatformRole;
use App\Models\User;

test('plain User Account receives the user platform role', function () {
    $user = User::factory()->create();

    expect($user->hasRole(PlatformRole::User))->toBeTrue()
        ->and($user->platformLevel())->toBe(PlatformRole::User->level())
        ->and($user->canAccessOfficerSurfaces())->toBeFalse()
        ->and($user->canAccessAdministratorSurfaces())->toBeFalse();
});

test('Officer can reach Officer surfaces and not Administrator surfaces', function () {
    $user = User::factory()->officer()->create();

    expect($user->canAccessOfficerSurfaces())->toBeTrue()
        ->and($user->canAccessAdministratorSurfaces())->toBeFalse()
        ->and($user->isSuperAdmin())->toBeFalse();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('administrator.dashboard'))
        ->assertForbidden();
});

test('Administrator inherits Officer surfaces', function () {
    $user = User::factory()->administrator()->create();

    expect($user->canAccessOfficerSurfaces())->toBeTrue()
        ->and($user->canAccessAdministratorSurfaces())->toBeTrue();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertOk();

    $this->actingAs($user)
        ->get(route('administrator.dashboard'))
        ->assertOk();
});

test('Super Admin is a Spatie role stack without is_super_admin column', function () {
    $user = User::factory()->superAdmin()->create();

    expect($user->isSuperAdmin())->toBeTrue()
        ->and($user->hasRole(PlatformRole::SuperAdmin))->toBeTrue()
        ->and($user->hasRole(PlatformRole::User))->toBeTrue()
        ->and($user->hasRole(PlatformRole::Officer))->toBeFalse()
        ->and($user->canAccessOfficerSurfaces())->toBeTrue()
        ->and(array_key_exists('is_super_admin', $user->getAttributes()))->toBeFalse();
});

test('Member role skips membership onboarding redirect', function () {
    $user = User::factory()->member()->create();

    expect($user->isMembershipHolder())->toBeTrue()
        ->and($user->mustCompleteMembershipOnboarding())->toBeFalse();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
