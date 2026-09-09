<?php

use App\Actions\Charges\GenerateChargesForPeriod;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\FeeType;
use App\Models\Property;
use App\Models\Suspend;
use App\Models\User;
use App\Support\BillingPeriod;
use Illuminate\Support\Facades\Artisan;

test('generation creates a Charge with snapshotted Fee Type amounts for each active Property', function () {
    $property = Property::factory()->create();
    Property::factory()->inactive()->create();
    FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);
    FeeType::factory()->create(['name' => 'Garbage collection', 'amount' => '200.00']);
    FeeType::factory()->retired()->create(['name' => 'Retired fee', 'amount' => '50.00']);

    $created = app(GenerateChargesForPeriod::class)->handle(new BillingPeriod(2026, 9));

    expect($created)->toBe(1)
        ->and(Charge::query()->count())->toBe(1);

    $charge = Charge::query()->first();

    expect($charge->property_id)->toBe($property->id)
        ->and($charge->year)->toBe(2026)
        ->and($charge->month)->toBe(9)
        ->and($charge->lines)->toHaveCount(2)
        ->and($property->fresh()->hasBeenCharged())->toBeTrue();

    $this->assertDatabaseHas('charge_lines', [
        'charge_id' => $charge->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);
    $this->assertDatabaseHas('charge_lines', [
        'charge_id' => $charge->id,
        'fee_type_name' => 'Garbage collection',
        'amount' => '200.00',
    ]);
});

test('Suspend omits the Fee Type line for covered Billing Periods', function () {
    $property = Property::factory()->create();
    $guard = FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);
    FeeType::factory()->create(['name' => 'Garbage collection', 'amount' => '200.00']);

    Suspend::factory()->create([
        'property_id' => $property->id,
        'fee_type_id' => $guard->id,
        'starts_year' => 2026,
        'starts_month' => 9,
        'ends_year' => 2026,
        'ends_month' => 9,
    ]);

    app(GenerateChargesForPeriod::class)->handle(new BillingPeriod(2026, 9));

    $charge = Charge::query()->where('property_id', $property->id)->first();

    expect($charge->lines)->toHaveCount(1)
        ->and($charge->lines->first()->fee_type_name)->toBe('Garbage collection');
});

test('generation snapshots amounts and does not rewrite when Fee Type amount later changes', function () {
    $property = Property::factory()->create();
    $feeType = FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);

    app(GenerateChargesForPeriod::class)->handle(new BillingPeriod(2026, 9));

    $feeType->update(['amount' => '300.00']);

    app(GenerateChargesForPeriod::class)->handle(new BillingPeriod(2026, 9));

    expect(Charge::query()->count())->toBe(1)
        ->and(ChargeLine::query()->where('fee_type_name', 'Guard')->value('amount'))->toBe('200.00');
});

test('generation fills in missing Charges only and never overwrites existing ones', function () {
    $existingProperty = Property::factory()->create();
    $newProperty = Property::factory()->create();
    FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);

    $existing = Charge::factory()->create([
        'property_id' => $existingProperty->id,
        'year' => 2026,
        'month' => 9,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $existing->id,
        'fee_type_name' => 'Hand edited',
        'amount' => '1.00',
    ]);

    $created = app(GenerateChargesForPeriod::class)->handle(new BillingPeriod(2026, 9));

    expect($created)->toBe(1)
        ->and(Charge::query()->count())->toBe(2)
        ->and($existing->fresh()->lines()->pluck('fee_type_name')->all())->toBe(['Hand edited']);

    $this->assertDatabaseHas('charges', [
        'property_id' => $newProperty->id,
        'year' => 2026,
        'month' => 9,
    ]);
});

test('Officer can manually generate Charges for a Billing Period', function () {
    $user = User::factory()->officer()->create();
    Property::factory()->create();
    FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);

    $this->actingAs($user)
        ->post(route('officer.charges.generate'), [
            'year' => 2026,
            'month' => 9,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    // Officer factory creates a live Membership (and thus a roster Property).
    expect(Charge::query()->count())->toBe(Property::query()->active()->count());
});

test('artisan charges:generate creates Charges for the given Billing Period', function () {
    Property::factory()->create();
    FeeType::factory()->create(['name' => 'Guard', 'amount' => '200.00']);

    Artisan::call('charges:generate', [
        '--year' => 2026,
        '--month' => 9,
    ]);

    expect(Charge::query()->count())->toBe(1);
});
