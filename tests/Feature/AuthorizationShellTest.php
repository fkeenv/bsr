<?php

use App\Enums\PlatformRole;
use App\Models\User;

test('Super Admin can reach the Officer workspace', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('officer/Dashboard')
            ->where('auth.capabilities.canAccessOfficer', true)
            ->where('onboarding', null));
});

test('Officer can reach the Officer workspace', function () {
    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('officer/Dashboard')
            ->where('auth.capabilities.canAccessOfficer', true)
            ->where('onboarding', null));
});

test('Super Admin can reach the Administrator stub surface', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('administrator.dashboard'))
        ->assertOk();
});

test('plain User Account cannot reach the Officer workspace', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertForbidden();
});

test('dashboard shares authorization capability flags for Super Admin', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('auth.user.is_super_admin', true)
            ->where('auth.capabilities.isSuperAdmin', true)
            ->where('auth.capabilities.canAccessOfficer', true)
            ->where('auth.capabilities.canAccessAdministrator', true)
            ->where('auth.capabilities.isMembershipHolder', false)
        );
});

test('dashboard shares authorization capability flags for a plain User Account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('auth.user.is_super_admin', false)
            ->where('auth.capabilities.isSuperAdmin', false)
            ->where('auth.capabilities.canAccessOfficer', false)
            ->where('auth.capabilities.canAccessAdministrator', false)
            ->where('auth.capabilities.isMembershipHolder', false)
        );
});

test('dashboard capabilities identify eligible staff roles and Membership access', function (PlatformRole $role, bool $officerAccess, bool $administratorAccess) {
    $user = User::factory()->member()->create();
    $user->assignPlatformRole($role);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('auth.capabilities', [
                'isSuperAdmin' => false,
                'canAccessOfficer' => $officerAccess,
                'canAccessAdministrator' => $administratorAccess,
                'isMembershipHolder' => true,
            ]));
})->with([
    'Administrator' => [PlatformRole::Administrator, true, true],
    'Officer' => [PlatformRole::Officer, true, false],
    'Member' => [PlatformRole::Member, false, false],
]);
