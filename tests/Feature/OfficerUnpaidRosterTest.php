<?php

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentMethod;
use App\Enums\PlatformRole;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-15 10:00:00', 'Asia/Manila'));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('Officer sees unpaid Properties sorted by Outstanding Balance descending', function () {
    $officer = User::factory()->officer()->create();
    $officerPropertyId = $officer->memberships()->live()->value('property_id');

    $low = Property::factory()->create([
        'block' => '2',
        'lot' => '1',
        'opening_balance' => '100.00',
        'recorded_owner_name' => 'Low Owner',
    ]);
    $high = Property::factory()->create([
        'block' => '1',
        'lot' => '5',
        'opening_balance' => '800.00',
        'recorded_owner_name' => 'High Owner',
    ]);
    Property::factory()->create([
        'block' => '9',
        'lot' => '9',
        'opening_balance' => '0.00',
    ]);

    $september = Charge::factory()->create([
        'property_id' => $low->id,
        'year' => 2026,
        'month' => 9,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $september->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/unpaid/Index')
            ->where('this_billing_period.label', 'September 2026')
            ->where('roster_count', 4)
            ->where('unpaid_count', 2)
            ->where('filters_active', false)
            ->has('rows', 2)
            ->where('rows.0.property_id', $high->id)
            ->where('rows.0.outstanding_balance', '800.00')
            ->where('rows.0.this_period_status', 'paid')
            ->where('rows.1.property_id', $low->id)
            ->where('rows.1.outstanding_balance', '300.00')
            ->where('rows.1.this_period_status', 'unpaid')
            ->where('selected', null)
            ->where('values.property', null));

    expect($officerPropertyId)->not->toBeNull();
});

test('unpaid roster filters by Block Lot this-period status and Owes for', function () {
    $officer = User::factory()->officer()->create();

    // Opening-only: no Charge this period, so status is paid while Opening Balance remains.
    $openingOnly = Property::factory()->create([
        'block' => '20',
        'lot' => '5',
        'opening_balance' => '500.00',
    ]);
    $currentUnpaid = Property::factory()->create([
        'block' => '40',
        'lot' => '120',
        'opening_balance' => '0.00',
    ]);
    $olderOnly = Property::factory()->create([
        'block' => '40',
        'lot' => '3',
        'opening_balance' => '0.00',
    ]);

    foreach ([$currentUnpaid, $olderOnly] as $property) {
        $charge = Charge::factory()->create([
            'property_id' => $property->id,
            'year' => 2026,
            'month' => 9,
        ]);
        ChargeLine::factory()->create([
            'charge_id' => $charge->id,
            'amount' => '400.00',
        ]);
    }

    $payOlderSeptember = Payment::factory()->pending()->create([
        'property_id' => $olderOnly->id,
        'amount' => '400.00',
        'method' => PaymentMethod::Cash,
    ]);
    app(ConfirmPayment::class)->handle($payOlderSeptember, $officer);

    // Backdated older Charge after this period was paid.
    ChargeLine::factory()->create([
        'charge_id' => Charge::factory()->create([
            'property_id' => $olderOnly->id,
            'year' => 2026,
            'month' => 8,
        ])->id,
        'amount' => '400.00',
    ]);

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', ['block' => '40']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rows', 2)
            ->where('filters_active', true)
            ->where('rows.0.block', '40')
            ->where('rows.1.block', '40'));

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', ['lot' => '120']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('values.lot', '120')
            ->has('rows', 1)
            ->where('rows.0.property_id', $currentUnpaid->id));

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', ['status' => 'unpaid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rows', 1)
            ->where('rows.0.property_id', $currentUnpaid->id)
            ->where('rows.0.this_period_status', 'unpaid'));

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', ['status' => 'paid']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rows', 2)
            ->where('rows.0.this_period_status', 'paid')
            ->where('rows.1.this_period_status', 'paid'));

    $partialProperty = Property::factory()->create([
        'block' => '50',
        'lot' => '1',
        'opening_balance' => '0.00',
    ]);
    $partialCharge = Charge::factory()->create([
        'property_id' => $partialProperty->id,
        'year' => 2026,
        'month' => 9,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $partialCharge->id,
        'amount' => '400.00',
    ]);
    $partialPayment = Payment::factory()->pending()->create([
        'property_id' => $partialProperty->id,
        'amount' => '150.00',
        'method' => PaymentMethod::GCash,
    ]);
    app(ConfirmPayment::class)->handle($partialPayment, $officer);

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', ['status' => 'partial']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rows', 1)
            ->where('rows.0.property_id', $partialProperty->id)
            ->where('rows.0.this_period_status', 'partial'));

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', ['owes_for' => 'opening']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rows', 1)
            ->where('rows.0.property_id', $openingOnly->id));

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', ['owes_for' => '2026-08']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('rows', 1)
            ->where('rows.0.property_id', $olderOnly->id));
});

test('selecting a Property returns the Statement of Account drill-in', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create([
        'block' => '3',
        'lot' => '7',
        'opening_balance' => '250.00',
    ]);
    $charge = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 9,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $charge->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', [
            'property' => $property->id,
            'charge' => $charge->id,
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/unpaid/Index')
            ->where('selected.property.id', $property->id)
            ->where('selected.property.label', 'Block 3 · Lot 7')
            ->where('selected.outstanding_balance', '450.00')
            ->where('selected.remaining_opening_balance', '250.00')
            ->where('selected.selected_charge_id', $charge->id)
            ->has('selected.periods', 1)
            ->where('selected.periods.0.status', 'unpaid')
            ->where('selected.switcher', []));
});

test('empty roster and filtered no-matches are distinct', function () {
    $officer = User::factory()->officer()->create();

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('unpaid_count', 0)
            ->where('roster_count', 1)
            ->where('filters_active', false)
            ->has('rows', 0)
            ->where('empty_state', 'clear'));

    Property::factory()->create([
        'block' => '1',
        'lot' => '1',
        'opening_balance' => '100.00',
    ]);

    $this->actingAs($officer)
        ->get(route('officer.unpaid.index', ['block' => '99']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('unpaid_count', 1)
            ->where('filters_active', true)
            ->has('rows', 0)
            ->where('empty_state', 'no_matches'));
});

test('Members cannot access the Officer unpaid roster', function () {
    $member = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '100.00']);
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $this->actingAs($member)
        ->get(route('officer.unpaid.index'))
        ->assertForbidden();
});

test('Super Admin can access the Officer unpaid roster', function () {
    $superAdmin = User::factory()->superAdmin()->create();
    Property::factory()->create(['opening_balance' => '50.00']);

    $this->actingAs($superAdmin)
        ->get(route('officer.unpaid.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('officer/unpaid/Index')
            ->has('rows', 1));
});
