<?php

use App\Enums\PlatformRole;
use App\Models\Membership;
use App\Models\Property;
use App\Models\PropertyProfile;
use App\Models\User;

function createPropertyProfileHolder(Property $property): User
{
    $holder = User::factory()->create();
    Membership::factory()->resident()->create([
        'user_id' => $holder->id,
        'property_id' => $property->id,
    ]);
    $holder->assignPlatformRole(PlatformRole::Member);

    return $holder;
}

test('live Membership holder can open an empty shared Property profile', function () {
    $property = Property::factory()->create();
    $holder = createPropertyProfileHolder($property);

    $this->actingAs($holder)
        ->get(route('property-profile.edit', $property))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('property-profile/Edit')
            ->where('profile.property_id', $property->id)
            ->where('profile.property_label', 'Block '.$property->block.' · Lot '.$property->lot)
            ->where('canAccessOfficerProperties', false)
            ->where('profile.saved_at', null)
            ->where('profile.household_members', [])
            ->where('profile.emergency_contacts', [])
            ->where('profile.vehicles', []));
});

test('Membership holders on one Property read the same saved profile', function () {
    $property = Property::factory()->create();
    $firstHolder = createPropertyProfileHolder($property);
    $secondHolder = createPropertyProfileHolder($property);
    $profileData = [
        'household_members' => [
            ['name' => 'Ana Reyes'],
        ],
        'emergency_contacts' => [
            [
                'name' => 'Ben Reyes',
                'contact_number' => '+639171234567',
                'relationship' => 'Spouse',
            ],
        ],
        'vehicles' => [
            [
                'year' => 2019,
                'make' => 'Toyota',
                'model' => 'Vios',
                'plate' => 'ABC1234',
                'sticker_number' => 'STK-001',
            ],
        ],
    ];

    $this->actingAs($firstHolder)
        ->from(route('property-profile.edit', $property))
        ->put(route('property-profile.update', $property), $profileData)
        ->assertRedirect(route('property-profile.edit', $property))
        ->assertSessionHas('success', 'Property profile saved.');

    $profile = PropertyProfile::query()->whereBelongsTo($property)->firstOrFail();
    $this->assertModelExists($profile);
    expect($profile->saved_at)->not->toBeNull();

    $this->actingAs($secondHolder)
        ->get(route('property-profile.edit', $property))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('profile.household_members', $profileData['household_members'])
            ->where('profile.emergency_contacts', $profileData['emergency_contacts'])
            ->where('profile.vehicles', $profileData['vehicles'])
            ->where('profile.saved_at', $profile->saved_at->toIso8601String()));
});

test('Officer can maintain a shared profile for any Property', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();

    $this->actingAs($officer)
        ->get(route('property-profile.edit', $property))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('canAccessOfficerProperties', true));

    $this->actingAs($officer)
        ->from(route('property-profile.edit', $property))
        ->put(route('property-profile.update', $property), [
            'household_members' => [['name' => 'Officer recorded household']],
            'emergency_contacts' => [],
            'vehicles' => [],
        ])
        ->assertRedirect(route('property-profile.edit', $property));

    $profile = PropertyProfile::query()->whereBelongsTo($property)->firstOrFail();

    expect($profile->household_members)->toBe([
        ['name' => 'Officer recorded household'],
    ]);
});

test('Membership holder receives 404 for another Property profile', function () {
    $accessibleProperty = Property::factory()->create();
    $hiddenProperty = Property::factory()->create();
    $holder = createPropertyProfileHolder($accessibleProperty);

    $this->actingAs($holder)
        ->get(route('property-profile.edit', $hiddenProperty))
        ->assertNotFound();

    $this->actingAs($holder)
        ->put(route('property-profile.update', $hiddenProperty), [
            'household_members' => [],
            'emergency_contacts' => [],
            'vehicles' => [],
        ])
        ->assertNotFound();

    expect(PropertyProfile::query()->whereBelongsTo($hiddenProperty)->exists())->toBeFalse();
});

test('User Account without a live Membership receives 404 for a Property profile', function () {
    $user = User::factory()->create();
    $property = Property::factory()->create();

    $this->actingAs($user)
        ->get(route('property-profile.edit', $property))
        ->assertNotFound();

    $this->actingAs($user)
        ->put(route('property-profile.update', $property), [
            'household_members' => [],
            'emergency_contacts' => [],
            'vehicles' => [],
        ])
        ->assertNotFound();

    expect(PropertyProfile::query()->whereBelongsTo($property)->exists())->toBeFalse();
});

test('ended Membership does not grant access to its former Property profile', function () {
    $liveProperty = Property::factory()->create();
    $formerProperty = Property::factory()->create();
    $holder = createPropertyProfileHolder($liveProperty);
    Membership::factory()->ended()->create([
        'user_id' => $holder->id,
        'property_id' => $formerProperty->id,
    ]);

    $this->actingAs($holder)
        ->get(route('property-profile.edit', $formerProperty))
        ->assertNotFound();
});

test('shared profile applies the existing household detail validation limits', function () {
    $property = Property::factory()->create();
    $holder = createPropertyProfileHolder($property);

    $this->actingAs($holder)
        ->from(route('property-profile.edit', $property))
        ->put(route('property-profile.update', $property), [
            'household_members' => [['name' => str_repeat('a', 256)]],
            'emergency_contacts' => [[
                'name' => str_repeat('b', 256),
                'contact_number' => str_repeat('1', 51),
                'relationship' => str_repeat('c', 256),
            ]],
            'vehicles' => [[
                'year' => 1899,
                'make' => str_repeat('d', 256),
                'model' => str_repeat('e', 256),
                'plate' => str_repeat('f', 51),
                'sticker_number' => str_repeat('g', 51),
            ]],
        ])
        ->assertRedirect(route('property-profile.edit', $property))
        ->assertSessionHasErrors([
            'household_members.0.name',
            'emergency_contacts.0.name',
            'emergency_contacts.0.contact_number',
            'emergency_contacts.0.relationship',
            'vehicles.0.year',
            'vehicles.0.make',
            'vehicles.0.model',
            'vehicles.0.plate',
            'vehicles.0.sticker_number',
        ]);

    expect(PropertyProfile::query()->whereBelongsTo($property)->exists())->toBeFalse();
});

test('saving replaces additions edits and removals in one shared profile', function () {
    $property = Property::factory()->create();
    $holder = createPropertyProfileHolder($property);
    PropertyProfile::factory()->for($property)->create([
        'household_members' => [
            ['name' => 'Old household name'],
            ['name' => 'Remove this household member'],
        ],
        'emergency_contacts' => [
            [
                'name' => 'Remove this contact',
                'contact_number' => '09170000000',
                'relationship' => 'Friend',
            ],
        ],
        'vehicles' => [
            [
                'year' => 2010,
                'make' => 'Old make',
                'model' => 'Old model',
                'plate' => 'OLD123',
                'sticker_number' => 'OLD-001',
            ],
        ],
    ]);

    $this->actingAs($holder)
        ->put(route('property-profile.update', $property), [
            'household_members' => [['name' => 'Updated household name']],
            'emergency_contacts' => [[
                'name' => 'New emergency contact',
                'contact_number' => '09171111111',
                'relationship' => 'Sibling',
            ]],
            'vehicles' => [],
        ])
        ->assertRedirect();

    $profile = PropertyProfile::query()->whereBelongsTo($property)->firstOrFail();

    expect(PropertyProfile::query()->whereBelongsTo($property)->count())->toBe(1);
    expect($profile->household_members)->toBe([
        ['name' => 'Updated household name'],
    ]);
    expect($profile->emergency_contacts)->toBe([
        [
            'name' => 'New emergency contact',
            'contact_number' => '09171111111',
            'relationship' => 'Sibling',
        ],
    ]);
    expect($profile->vehicles)->toBe([]);
});

test('all shared profile collections may be saved empty', function () {
    $property = Property::factory()->create();
    $holder = createPropertyProfileHolder($property);

    $this->actingAs($holder)
        ->put(route('property-profile.update', $property), [
            'household_members' => [],
            'emergency_contacts' => [],
            'vehicles' => [],
        ])
        ->assertRedirect();

    $profile = PropertyProfile::query()->whereBelongsTo($property)->firstOrFail();

    expect($profile->household_members)->toBe([]);
    expect($profile->emergency_contacts)->toBe([]);
    expect($profile->vehicles)->toBe([]);
    expect($profile->saved_at)->not->toBeNull();
});
