<?php

use App\Models\User;

test('plain User Account is sent to Membership Application onboarding', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('membership-application.create'));

    $this->actingAs($user)
        ->get(route('membership-application.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('membership-application/Create'));
});

test('Super Admin can visit the dashboard without Membership onboarding', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk();
});
