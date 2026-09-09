<?php

use App\Models\User;

test('Super Admin can reach the Officer stub surface', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertOk();
});

test('Officer can reach the Officer stub surface', function () {
    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertOk();
});

test('Super Admin can reach the Administrator stub surface', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('administrator.dashboard'))
        ->assertOk();
});

test('plain User Account cannot reach the Officer stub surface', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('officer.dashboard'))
        ->assertRedirect(route('membership-application.create'));
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

test('onboarding shares authorization capability flags for a plain User Account', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('membership-application.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('membership-application/Create')
            ->where('auth.user.is_super_admin', false)
            ->where('auth.capabilities.isSuperAdmin', false)
            ->where('auth.capabilities.canAccessOfficer', false)
            ->where('auth.capabilities.canAccessAdministrator', false)
            ->where('auth.capabilities.isMembershipHolder', false)
        );
});

test('plain User Account is redirected away from the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('membership-application.create'));
});
