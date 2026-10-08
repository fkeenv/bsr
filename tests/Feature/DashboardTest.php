<?php

use App\Models\Membership;
use App\Models\Property;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->superAdmin()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shares only the accounts live Memberships with each Property identity and role', function () {
    $user = User::factory()->create();
    $owner = Membership::factory()->for($user)->owner()->create([
        'property_id' => Property::factory()->create(['block' => '3', 'lot' => '12'])->id,
    ]);
    $resident = Membership::factory()->for($user)->resident()->create([
        'property_id' => Property::factory()->create(['block' => '8', 'lot' => '4'])->id,
    ]);
    Membership::factory()->for($user)->ended()->create();
    Membership::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('memberships', 2)
            ->where('memberships.0.id', $resident->id)
            ->where('memberships.0.property_id', $resident->property_id)
            ->where('memberships.0.property_label', 'Block 8 · Lot 4')
            ->where('memberships.0.role', 'resident')
            ->where('memberships.1.id', $owner->id)
            ->where('memberships.1.property_id', $owner->property_id)
            ->where('memberships.1.property_label', 'Block 3 · Lot 12')
            ->where('memberships.1.role', 'owner')
            ->where('auth.capabilities.isMembershipHolder', true));
});
