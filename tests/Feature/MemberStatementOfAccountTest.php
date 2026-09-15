<?php

use App\Actions\Payments\ConfirmPayment;
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

test('Member with a live Membership can view the Property Statement of Account workspace', function () {
    $member = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '300.00']);
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $july = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 7,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $july->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);

    $august = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 8,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $august->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);

    $officer = User::factory()->officer()->create();
    $partialPayment = Payment::factory()->pending()->create([
        'property_id' => $property->id,
        'amount' => '400.00',
        'method' => PaymentMethod::Bank,
        'reference' => 'BNK-PARTIAL',
    ]);
    app(ConfirmPayment::class)->handle($partialPayment, $officer);

    $pending = Payment::factory()->pending()->create([
        'property_id' => $property->id,
        'declared_by_user_id' => $member->id,
        'amount' => '100.00',
        'method' => PaymentMethod::GCash,
        'reference' => 'GC-PENDING',
    ]);

    $this->actingAs($member)
        ->get(route('statement-of-account.show', $property))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('statement-of-account/Show')
            ->where('property.id', $property->id)
            ->where('property.label', 'Block '.$property->block.' · Lot '.$property->lot)
            ->where('outstanding_balance', '300.00')
            ->where('remaining_opening_balance', '0.00')
            ->where('prepaid_balance', '0.00')
            ->has('pending_declarations', 1)
            ->where('pending_declarations.0.id', $pending->id)
            ->where('pending_declarations.0.amount', '100.00')
            ->has('periods', 2)
            ->where('periods.0.charge_id', $august->id)
            ->where('periods.0.status', 'unpaid')
            ->where('periods.0.remaining', '200.00')
            ->where('periods.1.charge_id', $july->id)
            ->where('periods.1.status', 'partial')
            ->where('periods.1.remaining', '100.00')
            ->where('selected_charge_id', $august->id)
            ->has('selected_period.lines', 1)
            ->where('selected_period.lines.0.fee_type_name', 'Guard')
            ->where('selected_period.charge_total', '200.00')
            ->where('selected_period.payments', [])
            ->has('switcher', 1)
            ->where('switcher.0.property_id', $property->id)
            ->missing('combined_outstanding_balance'));
});

test('selecting a Billing Period shows Fee Type lines and Payments allocated to that Charge', function () {
    $member = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '0.00']);
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $july = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 7,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $july->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $july->id,
        'fee_type_name' => 'Garbage collection',
        'amount' => '200.00',
    ]);

    $officer = User::factory()->officer()->create();
    $payment = Payment::factory()->pending()->create([
        'property_id' => $property->id,
        'amount' => '200.00',
        'method' => PaymentMethod::Maya,
        'reference' => 'MY-JUL',
    ]);
    app(ConfirmPayment::class)->handle($payment, $officer);

    $this->actingAs($member)
        ->get(route('statement-of-account.show', ['property' => $property, 'charge' => $july->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('selected_charge_id', $july->id)
            ->where('selected_period.status', 'partial')
            ->where('selected_period.remaining', '200.00')
            ->where('selected_period.charge_total', '400.00')
            ->has('selected_period.lines', 2)
            ->has('selected_period.payments', 1)
            ->where('selected_period.payments.0.id', $payment->id)
            ->where('selected_period.payments.0.amount', '200.00')
            ->where('selected_period.payments.0.status', 'confirmed'));
});

test('Member without a live Membership on the Property cannot view its Statement of Account', function () {
    $member = User::factory()->member()->create();
    $property = Property::factory()->create();

    $this->actingAs($member)
        ->get(route('statement-of-account.show', $property))
        ->assertForbidden();
});

test('resident Membership sees the same Statement of Account surface as owner', function () {
    $resident = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '150.00']);
    Membership::factory()->resident()->create([
        'user_id' => $resident->id,
        'property_id' => $property->id,
    ]);
    $resident->assignPlatformRole(PlatformRole::Member);

    $this->actingAs($resident)
        ->get(route('statement-of-account.show', $property))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('statement-of-account/Show')
            ->where('property.id', $property->id)
            ->where('outstanding_balance', '150.00')
            ->where('remaining_opening_balance', '150.00'));
});

test('I paid from the Statement of Account creates a pending Payment declaration for the Property', function () {
    Storage::fake('local');

    $member = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '500.00']);
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $this->actingAs($member)
        ->from(route('statement-of-account.show', $property))
        ->post(route('payments.store'), [
            'property_id' => $property->id,
            'amount' => '250.00',
            'method' => PaymentMethod::Maya->value,
            'reference' => 'MY-SOA',
            'screenshot' => UploadedFile::fake()->image('receipt.jpg'),
        ])
        ->assertRedirect(route('statement-of-account.show', $property));

    $payment = Payment::query()->where('property_id', $property->id)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->status)->toBe(PaymentStatus::Pending)
        ->and($payment->amount)->toBe('250.00')
        ->and($payment->method)->toBe(PaymentMethod::Maya);

    $this->actingAs($member)
        ->get(route('statement-of-account.show', $property))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('outstanding_balance', '500.00')
            ->has('pending_declarations', 1)
            ->where('pending_declarations.0.id', $payment->id));
});

test('Property switcher lists live Memberships without a combined Outstanding total', function () {
    $member = User::factory()->create();
    $first = Property::factory()->create(['opening_balance' => '100.00', 'block' => '1', 'lot' => '1']);
    $second = Property::factory()->create(['opening_balance' => '200.00', 'block' => '2', 'lot' => '2']);

    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $first->id,
    ]);
    Membership::factory()->resident()->create([
        'user_id' => $member->id,
        'property_id' => $second->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $this->actingAs($member)
        ->get(route('statement-of-account.show', $first))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('statement-of-account/Show')
            ->has('switcher', 2)
            ->where('switcher.0.property_id', $first->id)
            ->where('switcher.0.outstanding_balance', '100.00')
            ->where('switcher.1.property_id', $second->id)
            ->where('switcher.1.outstanding_balance', '200.00')
            ->where('outstanding_balance', '100.00')
            ->missing('combined_outstanding_balance'));
});
