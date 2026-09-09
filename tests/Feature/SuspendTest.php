<?php

use App\Models\FeeType;
use App\Models\Property;
use App\Models\Suspend;
use App\Models\User;
use App\Support\BillingPeriod;

test('Officer can create a Suspend for a Property and Fee Type', function () {
    $user = User::factory()->officer()->create();
    $property = Property::factory()->create();
    $feeType = FeeType::factory()->create(['name' => 'Guard']);

    $this->actingAs($user)
        ->post(route('officer.suspends.store'), [
            'property_id' => $property->id,
            'fee_type_id' => $feeType->id,
            'starts_year' => 2026,
            'starts_month' => 3,
            'ends_year' => 2026,
            'ends_month' => 5,
        ])
        ->assertRedirect(route('officer.suspends.index'));

    $this->assertDatabaseHas('suspends', [
        'property_id' => $property->id,
        'fee_type_id' => $feeType->id,
        'starts_year' => 2026,
        'starts_month' => 3,
        'ends_year' => 2026,
        'ends_month' => 5,
    ]);
});

test('Officer can create a standing Suspend with no end Billing Period', function () {
    $user = User::factory()->officer()->create();
    $property = Property::factory()->create();
    $feeType = FeeType::factory()->create();

    $this->actingAs($user)
        ->post(route('officer.suspends.store'), [
            'property_id' => $property->id,
            'fee_type_id' => $feeType->id,
            'starts_year' => 2026,
            'starts_month' => 1,
        ])
        ->assertRedirect(route('officer.suspends.index'));

    $suspend = Suspend::query()->first();

    expect($suspend)->not->toBeNull()
        ->and($suspend->ends_year)->toBeNull()
        ->and($suspend->ends_month)->toBeNull()
        ->and($suspend->covers(new BillingPeriod(2026, 12)))->toBeTrue()
        ->and($suspend->covers(new BillingPeriod(2025, 12)))->toBeFalse();
});

test('Officer can update Suspend end Billing Period', function () {
    $user = User::factory()->officer()->create();
    $suspend = Suspend::factory()->create([
        'starts_year' => 2026,
        'starts_month' => 1,
        'ends_year' => null,
        'ends_month' => null,
    ]);

    $this->actingAs($user)
        ->put(route('officer.suspends.update', $suspend), [
            'property_id' => $suspend->property_id,
            'fee_type_id' => $suspend->fee_type_id,
            'starts_year' => 2026,
            'starts_month' => 1,
            'ends_year' => 2026,
            'ends_month' => 6,
        ])
        ->assertRedirect(route('officer.suspends.index'));

    $suspend->refresh();

    expect($suspend->ends_year)->toBe(2026)
        ->and($suspend->ends_month)->toBe(6)
        ->and($suspend->covers(new BillingPeriod(2026, 6)))->toBeTrue()
        ->and($suspend->covers(new BillingPeriod(2026, 7)))->toBeFalse();
});

test('Suspend covers inclusive start and end Billing Periods only', function () {
    $suspend = Suspend::factory()->create([
        'starts_year' => 2026,
        'starts_month' => 3,
        'ends_year' => 2026,
        'ends_month' => 5,
    ]);

    expect($suspend->covers(new BillingPeriod(2026, 2)))->toBeFalse()
        ->and($suspend->covers(new BillingPeriod(2026, 3)))->toBeTrue()
        ->and($suspend->covers(new BillingPeriod(2026, 4)))->toBeTrue()
        ->and($suspend->covers(new BillingPeriod(2026, 5)))->toBeTrue()
        ->and($suspend->covers(new BillingPeriod(2026, 6)))->toBeFalse();
});

test('plain User Account cannot manage Suspends', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('officer.suspends.index'))
        ->assertRedirect(route('membership-application.create'));
});
