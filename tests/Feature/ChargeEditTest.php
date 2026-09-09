<?php

use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\FeeType;
use App\Models\Property;
use App\Models\User;

test('Officer can edit Charge lines on an unfrozen Charge', function () {
    $user = User::factory()->officer()->create();
    $property = Property::factory()->create();
    $guard = FeeType::factory()->create(['name' => 'Guard']);
    $garbage = FeeType::factory()->create(['name' => 'Garbage collection']);

    $charge = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 9,
    ]);
    $line = ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'fee_type_id' => $guard->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);

    $this->actingAs($user)
        ->put(route('officer.charges.update', $charge), [
            'lines' => [
                [
                    'id' => $line->id,
                    'fee_type_id' => $guard->id,
                    'fee_type_name' => 'Guard',
                    'amount' => '180.00',
                ],
                [
                    'fee_type_id' => $garbage->id,
                    'fee_type_name' => 'Garbage collection',
                    'amount' => '200.00',
                ],
            ],
        ])
        ->assertRedirect(route('officer.charges.edit', $charge));

    $charge->refresh();

    expect($charge->lines)->toHaveCount(2)
        ->and($charge->lines->firstWhere('fee_type_name', 'Guard')?->amount)->toBe('180.00')
        ->and($charge->lines->firstWhere('fee_type_name', 'Garbage collection')?->amount)->toBe('200.00');
});

test('Officer can omit a Fee Type line from an unfrozen Charge', function () {
    $user = User::factory()->officer()->create();
    $charge = Charge::factory()->create();
    $keep = ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'fee_type_name' => 'Garbage collection',
        'amount' => '200.00',
    ]);

    $this->actingAs($user)
        ->put(route('officer.charges.update', $charge), [
            'lines' => [
                [
                    'id' => $keep->id,
                    'fee_type_id' => $keep->fee_type_id,
                    'fee_type_name' => 'Guard',
                    'amount' => '200.00',
                ],
            ],
        ])
        ->assertRedirect(route('officer.charges.edit', $charge));

    expect($charge->fresh()->lines)->toHaveCount(1)
        ->and($charge->fresh()->lines->first()->fee_type_name)->toBe('Guard');
});

test('frozen Charge rejects line edits', function () {
    $user = User::factory()->officer()->create();
    $charge = Charge::factory()->frozen()->create();
    $line = ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);

    $this->actingAs($user)
        ->from(route('officer.charges.edit', $charge))
        ->put(route('officer.charges.update', $charge), [
            'lines' => [
                [
                    'id' => $line->id,
                    'fee_type_id' => $line->fee_type_id,
                    'fee_type_name' => 'Guard',
                    'amount' => '50.00',
                ],
            ],
        ])
        ->assertRedirect(route('officer.charges.edit', $charge))
        ->assertSessionHas('error');

    expect($line->fresh()->amount)->toBe('200.00');
});

test('Charge freeze hook locks the Charge and unfreeze restores editability', function () {
    $charge = Charge::factory()->create();

    expect($charge->isFrozen())->toBeFalse();

    $charge->freeze();

    expect($charge->fresh()->isFrozen())->toBeTrue();

    $charge->unfreeze();

    expect($charge->fresh()->isFrozen())->toBeFalse();
});
