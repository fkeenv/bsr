<?php

use App\Models\Property;
use App\Models\User;

test('Officer can create a Property with Block Lot and optional fields', function () {
    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->post(route('officer.properties.store'), [
            'block' => '12',
            'lot' => '3',
            'street_address' => '12 Rose St',
            'recorded_owner_name' => 'Juan Dela Cruz',
            'opening_balance' => '0',
        ])
        ->assertRedirect(route('officer.properties.index'));

    $this->assertDatabaseHas('properties', [
        'block' => '12',
        'lot' => '3',
        'street_address' => '12 Rose St',
        'recorded_owner_name' => 'Juan Dela Cruz',
        'is_active' => true,
    ]);
});

test('Officer can edit address and recorded owner but not Block Lot', function () {
    $user = User::factory()->officer()->create();
    $property = Property::factory()->create([
        'block' => '1',
        'lot' => '1',
        'street_address' => 'Old',
        'recorded_owner_name' => 'Old Owner',
    ]);

    $this->actingAs($user)
        ->put(route('officer.properties.update', $property), [
            'block' => '99',
            'lot' => '99',
            'street_address' => 'New Address',
            'recorded_owner_name' => 'New Owner',
            'opening_balance' => '0',
        ])
        ->assertRedirect(route('officer.properties.index'));

    $property->refresh();

    expect($property->block)->toBe('1')
        ->and($property->lot)->toBe('1')
        ->and($property->street_address)->toBe('New Address')
        ->and($property->recorded_owner_name)->toBe('New Owner');
});

test('duplicate Block Lot is rejected', function () {
    $user = User::factory()->officer()->create();
    Property::factory()->create(['block' => '2', 'lot' => '4']);

    $this->actingAs($user)
        ->from(route('officer.properties.create'))
        ->post(route('officer.properties.store'), [
            'block' => '2',
            'lot' => '4',
        ])
        ->assertRedirect(route('officer.properties.create'))
        ->assertSessionHasErrors('lot');
});

test('roster lists Properties and filters by Block', function () {
    $user = User::factory()->officer()->create();
    Property::factory()->create(['block' => '1', 'lot' => '1']);
    Property::factory()->create(['block' => '1', 'lot' => '2']);
    Property::factory()->create(['block' => '2', 'lot' => '1']);

    $this->actingAs($user)
        ->get(route('officer.properties.index', ['block' => '1']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/properties/Index')
            ->has('properties', 2)
            ->where('table.values.block', '1')
            ->where('table.searchables', ['name', 'block', 'lot'])
            ->where('table.filters', ['status', 'block'])
        );
});

test('roster searches across owner name Block and Lot', function () {
    $user = User::factory()->officer()->create();
    Property::factory()->create([
        'block' => '1',
        'lot' => '1',
        'street_address' => null,
        'recorded_owner_name' => 'Ana Reyes',
    ]);
    Property::factory()->create([
        'block' => '9',
        'lot' => '9',
        'street_address' => null,
        'recorded_owner_name' => 'Ben Cruz',
    ]);

    $this->actingAs($user)
        ->get(route('officer.properties.index', ['search' => 'Ana']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/properties/Index')
            ->has('properties', 1)
            ->where('properties.0.recorded_owner_name', 'Ana Reyes')
            ->where('table.values.search', 'Ana')
        );
});

test('roster filters by status', function () {
    $user = User::factory()->officer()->create();
    Property::factory()->create(['block' => '1', 'lot' => '1', 'is_active' => true]);
    Property::factory()->inactive()->create(['block' => '2', 'lot' => '2']);

    $this->actingAs($user)
        ->get(route('officer.properties.index', ['status' => 'inactive']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/properties/Index')
            ->has('properties', 1)
            ->where('properties.0.block', '2')
            ->where('table.values.status', 'inactive')
        );
});

test('soft inactive removes a Property from the active levy set without deleting it', function () {
    $user = User::factory()->officer()->create();
    $property = Property::factory()->create(['block' => '3', 'lot' => '3']);

    $this->actingAs($user)
        ->post(route('officer.properties.deactivate', $property))
        ->assertRedirect(route('officer.properties.index'));

    $property->refresh();

    expect($property->is_active)->toBeFalse()
        ->and(Property::query()->active()->whereKey($property)->exists())->toBeFalse()
        ->and(Property::query()->whereKey($property)->exists())->toBeTrue();
});

test('inactive Property can be reactivated', function () {
    $user = User::factory()->officer()->create();
    $property = Property::factory()->inactive()->create();

    $this->actingAs($user)
        ->post(route('officer.properties.activate', $property))
        ->assertRedirect(route('officer.properties.index'));

    expect($property->fresh()->is_active)->toBeTrue();
});

test('uncharged Property can be deleted', function () {
    $user = User::factory()->officer()->create();
    $property = Property::factory()->create();

    $this->actingAs($user)
        ->delete(route('officer.properties.destroy', $property))
        ->assertRedirect(route('officer.properties.index'));

    $this->assertDatabaseMissing('properties', ['id' => $property->id]);
});

test('charged Property cannot be deleted and must stay as inactive history', function () {
    $user = User::factory()->officer()->create();
    $property = Property::factory()->charged()->create();

    $this->actingAs($user)
        ->delete(route('officer.properties.destroy', $property))
        ->assertRedirect(route('officer.properties.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('properties', ['id' => $property->id]);

    $this->actingAs($user)
        ->post(route('officer.properties.deactivate', $property))
        ->assertRedirect(route('officer.properties.index'));

    expect($property->fresh()->is_active)->toBeFalse()
        ->and(Property::query()->whereKey($property)->exists())->toBeTrue();
});

test('plain User Account cannot manage the Property roster', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('officer.properties.index'))
        ->assertRedirect(route('membership-application.create'));
});
