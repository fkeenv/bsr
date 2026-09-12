<?php

use App\Actions\Payments\ApplyPrepaidToProperty;
use App\Actions\Payments\ComputePropertyBalances;
use App\Enums\PaymentAllocationTarget;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PlatformRole;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('Member with a live Membership can declare a Payment on that Property', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '500.00']);
    Membership::factory()->owner()->create([
        'user_id' => $user->id,
        'property_id' => $property->id,
    ]);
    $user->assignPlatformRole(PlatformRole::Member);

    $screenshot = UploadedFile::fake()->image('receipt.jpg', 600, 800);

    $this->actingAs($user)
        ->post(route('payments.store'), [
            'property_id' => $property->id,
            'amount' => '500.00',
            'method' => PaymentMethod::GCash->value,
            'reference' => 'GC-12345',
            'screenshot' => $screenshot,
        ])
        ->assertRedirect();

    $payment = Payment::query()->where('property_id', $property->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->declared_by_user_id)->toBe($user->id)
        ->and($payment->amount)->toBe('500.00')
        ->and($payment->method)->toBe(PaymentMethod::GCash)
        ->and($payment->reference)->toBe('GC-12345')
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->screenshot_path)->not->toBeNull();

    Storage::disk('local')->assertExists($payment->screenshot_path);
});

test('Member cannot declare a Payment on a Property without a live Membership', function () {
    Storage::fake('local');

    $user = User::factory()->member()->create();
    $otherProperty = Property::factory()->create();

    $this->actingAs($user)
        ->post(route('payments.store'), [
            'property_id' => $otherProperty->id,
            'amount' => '100.00',
            'method' => PaymentMethod::Cash->value,
            'reference' => 'CASH-1',
            'screenshot' => UploadedFile::fake()->image('receipt.jpg'),
        ])
        ->assertForbidden();

    expect(Payment::query()->count())->toBe(0);
});

test('pending Payment declarations leave Outstanding Balance unchanged', function () {
    Storage::fake('local');

    $member = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '300.00']);
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $charge = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 8,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);

    $balances = app(ComputePropertyBalances::class);
    $before = $balances->handle($property);

    $this->actingAs($member)
        ->post(route('payments.store'), [
            'property_id' => $property->id,
            'amount' => '500.00',
            'method' => PaymentMethod::Bank->value,
            'reference' => 'BNK-1',
            'screenshot' => UploadedFile::fake()->image('receipt.jpg'),
        ])
        ->assertRedirect();

    expect($balances->handle($property->fresh()))->toBe($before)
        ->and($before['outstanding_balance'])->toBe('500.00');
});

test('Officer confirmation allocates Opening Balance then oldest Charge then Prepaid', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create(['opening_balance' => '100.00']);

    $older = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 7,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $older->id,
        'amount' => '200.00',
    ]);

    $newer = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 8,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $newer->id,
        'amount' => '200.00',
    ]);

    $payment = Payment::factory()->pending()->create([
        'property_id' => $property->id,
        'amount' => '450.00',
        'method' => PaymentMethod::Cash,
    ]);

    $this->actingAs($officer)
        ->post(route('officer.payments.confirm', $payment))
        ->assertRedirect();

    $payment->refresh();
    $property->refresh();
    $older->refresh();
    $newer->refresh();

    $balances = app(ComputePropertyBalances::class)->handle($property);

    expect($payment->status)->toBe(PaymentStatus::Confirmed)
        ->and($payment->confirmed_by_user_id)->toBe($officer->id)
        ->and($property->openingBalanceIsFrozen())->toBeTrue()
        ->and($older->isFrozen())->toBeTrue()
        ->and($newer->isFrozen())->toBeTrue()
        ->and($balances['remaining_opening_balance'])->toBe('0.00')
        ->and($balances['outstanding_balance'])->toBe('50.00')
        ->and($balances['prepaid_balance'])->toBe('0.00')
        ->and($payment->allocations)->toHaveCount(3);

    expect($payment->allocations->firstWhere('target', PaymentAllocationTarget::OpeningBalance)?->amount)->toBe('100.00')
        ->and($payment->allocations->firstWhere('charge_id', $older->id)?->amount)->toBe('200.00')
        ->and($payment->allocations->firstWhere('charge_id', $newer->id)?->amount)->toBe('150.00');
});

test('confirmed Payment leftover becomes Prepaid', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create(['opening_balance' => '100.00']);
    $charge = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 9,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'amount' => '200.00',
    ]);

    $payment = Payment::factory()->pending()->create([
        'property_id' => $property->id,
        'amount' => '400.00',
    ]);

    $this->actingAs($officer)
        ->post(route('officer.payments.confirm', $payment))
        ->assertRedirect();

    $balances = app(ComputePropertyBalances::class)->handle($property->fresh());

    expect($balances['outstanding_balance'])->toBe('0.00')
        ->and($balances['prepaid_balance'])->toBe('100.00')
        ->and($charge->fresh()->isFrozen())->toBeTrue();
});

test('Officer can reject a pending Payment with a reason', function () {
    $officer = User::factory()->officer()->create();
    $payment = Payment::factory()->pending()->create([
        'amount' => '250.00',
    ]);
    $property = $payment->property;
    $property->forceFill(['opening_balance' => '250.00'])->save();

    $before = app(ComputePropertyBalances::class)->handle($property);

    $this->actingAs($officer)
        ->post(route('officer.payments.reject', $payment), [
            'rejection_reason' => 'No matching bank transfer.',
        ])
        ->assertRedirect();

    $payment->refresh();

    expect($payment->status)->toBe(PaymentStatus::Rejected)
        ->and($payment->rejection_reason)->toBe('No matching bank transfer.')
        ->and($payment->rejected_by_user_id)->toBe($officer->id)
        ->and(app(ComputePropertyBalances::class)->handle($property->fresh()))->toBe($before);
});

test('Officer can create-and-confirm a Payment without a Member declaration', function () {
    Storage::fake('local');

    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create(['opening_balance' => '150.00']);

    $this->actingAs($officer)
        ->post(route('officer.payments.store'), [
            'property_id' => $property->id,
            'amount' => '150.00',
            'method' => PaymentMethod::Bank->value,
            'reference' => 'OTC-9',
        ])
        ->assertRedirect();

    $payment = Payment::query()->where('property_id', $property->id)->first();
    $balances = app(ComputePropertyBalances::class)->handle($property->fresh());

    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Confirmed)
        ->and($payment->declared_by_user_id)->toBeNull()
        ->and($payment->confirmed_by_user_id)->toBe($officer->id)
        ->and($balances['outstanding_balance'])->toBe('0.00')
        ->and($balances['remaining_opening_balance'])->toBe('0.00');
});

test('voiding a confirmed Payment reverses allocation and can unfreeze Charges', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create(['opening_balance' => '100.00']);
    $charge = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 9,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'amount' => '200.00',
    ]);

    $payment = Payment::factory()->pending()->create([
        'property_id' => $property->id,
        'amount' => '300.00',
    ]);

    $this->actingAs($officer)
        ->post(route('officer.payments.confirm', $payment))
        ->assertRedirect();

    expect(app(ComputePropertyBalances::class)->handle($property->fresh())['outstanding_balance'])->toBe('0.00')
        ->and($charge->fresh()->isFrozen())->toBeTrue();

    $this->actingAs($officer)
        ->post(route('officer.payments.void', $payment), [
            'void_reason' => 'Duplicate entry.',
        ])
        ->assertRedirect();

    $payment->refresh();
    $balances = app(ComputePropertyBalances::class)->handle($property->fresh());

    expect($payment->status)->toBe(PaymentStatus::Voided)
        ->and($payment->void_reason)->toBe('Duplicate entry.')
        ->and($payment->allocations()->count())->toBe(0)
        ->and($balances['outstanding_balance'])->toBe('300.00')
        ->and($balances['prepaid_balance'])->toBe('0.00')
        ->and($charge->fresh()->isFrozen())->toBeFalse();
});

test('confirmed Payments cannot be silently edited', function () {
    $officer = User::factory()->officer()->create();
    $payment = Payment::factory()->confirmed()->create([
        'amount' => '100.00',
        'reference' => 'ORIG',
    ]);

    $this->actingAs($officer)
        ->put('/officer/payments/'.$payment->id, [
            'amount' => '999.00',
            'reference' => 'CHANGED',
        ])
        ->assertNotFound();

    $payment->refresh();

    expect($payment->amount)->toBe('100.00')
        ->and($payment->reference)->toBe('ORIG');
});

test('Prepaid applies to new debt in Opening Balance then oldest Charge order', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create(['opening_balance' => '0.00']);

    $payment = Payment::factory()->pending()->create([
        'property_id' => $property->id,
        'amount' => '250.00',
    ]);

    $this->actingAs($officer)
        ->post(route('officer.payments.confirm', $payment))
        ->assertRedirect();

    expect(app(ComputePropertyBalances::class)->handle($property->fresh())['prepaid_balance'])->toBe('250.00');

    $charge = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 10,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'amount' => '200.00',
    ]);

    app(ApplyPrepaidToProperty::class)->handle($property->fresh());

    $balances = app(ComputePropertyBalances::class)->handle($property->fresh());
    $payment->refresh();

    expect($balances['outstanding_balance'])->toBe('0.00')
        ->and($balances['prepaid_balance'])->toBe('50.00')
        ->and($payment->prepaid_amount)->toBe('50.00')
        ->and($charge->fresh()->isFrozen())->toBeTrue()
        ->and($payment->allocations)->toHaveCount(1)
        ->and($payment->allocations->first()->amount)->toBe('200.00');
});
