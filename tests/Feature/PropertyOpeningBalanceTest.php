<?php

use App\Actions\Properties\CreateProperty;
use App\Actions\Properties\UpdateProperty;
use App\Models\Property;
use App\Models\User;
use InvalidArgumentException;

test('Opening Balance defaults to zero when omitted on create', function () {
    $property = app(CreateProperty::class)->handle([
        'block' => '1',
        'lot' => '2',
    ]);

    expect($property->opening_balance)->toBe('0.00')
        ->and($property->openingBalanceIsFrozen())->toBeFalse();
});

test('Opening Balance accepts a non-negative amount on create', function () {
    $property = app(CreateProperty::class)->handle([
        'block' => '1',
        'lot' => '3',
        'opening_balance' => '1500.50',
    ]);

    expect($property->opening_balance)->toBe('1500.50');
});

test('Opening Balance rejects a negative amount', function () {
    app(CreateProperty::class)->handle([
        'block' => '1',
        'lot' => '4',
        'opening_balance' => '-1.00',
    ]);
})->throws(InvalidArgumentException::class);

test('Opening Balance freezes after the confirmed Payment hook runs', function () {
    $property = Property::factory()->withOpeningBalance('200.00')->create();

    $property->freezeOpeningBalance();

    expect($property->fresh()->openingBalanceIsFrozen())->toBeTrue();
});

test('frozen Opening Balance cannot be changed', function () {
    $property = Property::factory()
        ->withOpeningBalance('200.00')
        ->frozenOpeningBalance()
        ->create();

    app(UpdateProperty::class)->handle($property, [
        'street_address' => '123 Main',
        'recorded_owner_name' => 'Ada',
        'opening_balance' => '50.00',
    ]);
})->throws(InvalidArgumentException::class);

test('HTTP update does not change a frozen Opening Balance', function () {
    $user = User::factory()->superAdmin()->create();
    $property = Property::factory()
        ->withOpeningBalance('200.00')
        ->frozenOpeningBalance()
        ->create();

    $this->actingAs($user)
        ->put(route('officer.properties.update', $property), [
            'street_address' => 'Updated',
            'recorded_owner_name' => 'Ada',
            'opening_balance' => '1.00',
        ])
        ->assertRedirect(route('officer.properties.index'));

    $property->refresh();

    expect($property->street_address)->toBe('Updated')
        ->and($property->opening_balance)->toBe('200.00');
});

test('Super Admin can set Opening Balance when creating a Property via HTTP', function () {
    $user = User::factory()->superAdmin()->create();

    $this->actingAs($user)
        ->post(route('officer.properties.store'), [
            'block' => '5',
            'lot' => '6',
            'opening_balance' => '300.00',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('properties', [
        'block' => '5',
        'lot' => '6',
        'opening_balance' => '300.00',
    ]);
});
