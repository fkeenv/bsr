<?php

use App\Enums\MembershipRole;
use App\Enums\PlatformRole;
use App\Models\User;
use App\Support\PlatformAccess;

test('workspace access preserves the existing role hierarchy', function (PlatformRole $role, array $expected) {
    $user = User::factory()->create();
    $user->assignPlatformRole($role);

    expect(PlatformAccess::canAccessSurface($user, 'super-admin'))->toBe($expected[0])
        ->and(PlatformAccess::canAccessSurface($user, 'administrator'))->toBe($expected[1])
        ->and(PlatformAccess::canAccessSurface($user, 'officer'))->toBe($expected[2]);
})->with([
    'Super Admin' => [PlatformRole::SuperAdmin, [true, true, true]],
    'Administrator' => [PlatformRole::Administrator, [false, true, true]],
    'Officer' => [PlatformRole::Officer, [false, false, true]],
    'Member' => [PlatformRole::Member, [false, false, false]],
    'User' => [PlatformRole::User, [false, false, false]],
]);

test('navigation capabilities distinguish role assignment from live Membership', function () {
    $user = User::factory()->create();
    $user->assignPlatformRole(PlatformRole::Member);

    expect(PlatformAccess::capabilities($user))->toBe([
        'isSuperAdmin' => false,
        'canAccessOfficer' => false,
        'canAccessAdministrator' => false,
        'isMembershipHolder' => false,
    ]);
});

test('workspace access denies guests and unknown surfaces', function () {
    $user = User::factory()->superAdmin()->create();

    expect(PlatformAccess::canAccessSurface(null, 'officer'))->toBeFalse()
        ->and(PlatformAccess::canAccessSurface($user, 'unknown'))->toBeFalse()
        ->and(PlatformAccess::canAccessSurface($user, 'member'))->toBeFalse();
});

test('accounts without recognized roles have no workspace access', function () {
    $user = User::factory()->create();
    $user->syncRoles([]);

    expect($user->platformLevel())->toBeNull()
        ->and($user->hasPlatformLevelAtMost(4))->toBeFalse()
        ->and(PlatformAccess::canAccessSurface($user, 'officer'))->toBeFalse();
});

test('staff eligibility requires ownership while Super Admin is exempt', function (string $account, bool $expected) {
    $user = match ($account) {
        'Super Admin' => User::factory()->superAdmin()->create(),
        'Owner' => User::factory()->member()->create(),
        'Resident' => User::factory()->member()->create(),
        'User' => User::factory()->create(),
    };

    if ($account === 'Resident') {
        $user->memberships()->sole()->update(['role' => MembershipRole::Resident]);
    }

    expect(PlatformAccess::canHoldStaffRole($user))->toBe($expected);
})->with([
    'Super Admin without Membership' => ['Super Admin', true],
    'live Owner' => ['Owner', true],
    'resident only' => ['Resident', false],
    'no Membership' => ['User', false],
]);
