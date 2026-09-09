<?php

use App\Models\User;

test('Super Admin can reach the Super Admin dashboard', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->get(route('super-admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('super-admin/Dashboard'));
});

test('Officer cannot reach the Super Admin dashboard', function () {
    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->get(route('super-admin.dashboard'))
        ->assertForbidden();
});
