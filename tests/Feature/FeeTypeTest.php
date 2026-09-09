<?php

use App\Models\FeeType;
use App\Models\User;
use Database\Seeders\FeeTypeSeeder;

test('Officer can create a Fee Type', function () {
    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->post(route('officer.fee-types.store'), [
            'name' => 'Garbage Collection Fee',
            'amount' => '200',
        ])
        ->assertRedirect(route('officer.fee-types.index'));

    $this->assertDatabaseHas('fee_types', [
        'name' => 'Garbage Collection Fee',
        'amount' => '200.00',
    ]);
});

test('Officer can update a Fee Type name and amount', function () {
    $user = User::factory()->officer()->create();
    $feeType = FeeType::factory()->create([
        'name' => 'Guard',
        'amount' => '200.00',
    ]);

    $this->actingAs($user)
        ->put(route('officer.fee-types.update', $feeType), [
            'name' => 'Guard duty',
            'amount' => '250.50',
        ])
        ->assertRedirect(route('officer.fee-types.index'));

    $feeType->refresh();

    expect($feeType->name)->toBe('Guard duty')
        ->and($feeType->amount)->toBe('250.50');
});

test('Fee Type list shows seeded Guard and Garbage collection at 200', function () {
    $this->seed(FeeTypeSeeder::class);

    $user = User::factory()->officer()->create();

    $this->actingAs($user)
        ->get(route('officer.fee-types.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/fee-types/Index')
            ->has('feeTypes', 2)
            ->where('feeTypes.0.name', 'Garbage collection')
            ->where('feeTypes.0.amount', '200.00')
            ->where('feeTypes.1.name', 'Guard')
            ->where('feeTypes.1.amount', '200.00')
        );
});

test('duplicate Fee Type name is rejected', function () {
    $user = User::factory()->officer()->create();
    FeeType::factory()->create(['name' => 'Guard']);

    $this->actingAs($user)
        ->from(route('officer.fee-types.create'))
        ->post(route('officer.fee-types.store'), [
            'name' => 'Guard',
            'amount' => '200',
        ])
        ->assertRedirect(route('officer.fee-types.create'))
        ->assertSessionHasErrors('name');
});

test('plain User Account cannot manage Fee Types', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('officer.fee-types.index'))
        ->assertRedirect(route('membership-application.create'));
});
