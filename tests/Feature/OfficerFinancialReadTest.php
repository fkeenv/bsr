<?php

use App\Enums\PaymentAllocationTarget;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-15 10:00:00', 'Asia/Manila'));
});

afterEach(function () {
    Carbon::setTestNow();
});

test('financial fixture preserves inactive debt balances statuses and confirmed payment details', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->inactive()->create([
        'block' => '2', 'lot' => '2', 'opening_balance' => '100.00', 'prepaid_balance' => '25.00',
    ]);
    $charges = [];
    foreach ([6 => '0.00', 7 => '100.00', 8 => '200.00', 9 => '300.00'] as $month => $amount) {
        $charges[$month] = Charge::factory()->create(['property_id' => $property->id, 'year' => 2026, 'month' => $month]);
        ChargeLine::factory()->create(['charge_id' => $charges[$month]->id, 'amount' => $amount]);
    }
    $confirmed = Payment::factory()->confirmed()->create([
        'property_id' => $property->id, 'declared_by_user_id' => $officer->id,
        'confirmed_by_user_id' => $officer->id, 'amount' => '200.00',
    ]);
    foreach ([[null, '50.00'], [$charges[7]->id, '100.00'], [$charges[8]->id, '50.00']] as [$chargeId, $amount]) {
        PaymentAllocation::query()->create([
            'payment_id' => $confirmed->id, 'property_id' => $property->id,
            'target' => $chargeId === null ? PaymentAllocationTarget::OpeningBalance : PaymentAllocationTarget::Charge,
            'charge_id' => $chargeId, 'amount' => $amount,
        ]);
    }
    foreach ([PaymentStatus::Pending, PaymentStatus::Voided] as $status) {
        $payment = Payment::factory()->create([
            'property_id' => $property->id, 'declared_by_user_id' => $officer->id,
            'status' => $status, 'amount' => '300.00',
        ]);
        PaymentAllocation::query()->create([
            'payment_id' => $payment->id, 'property_id' => $property->id,
            'target' => PaymentAllocationTarget::Charge, 'charge_id' => $charges[9]->id, 'amount' => '300.00',
        ]);
    }
    $tie = Property::factory()->withOpeningBalance('500.00')->create(['block' => '10', 'lot' => '1']);

    $this->actingAs($officer)->get(route('officer.unpaid.index', ['property' => $property->id, 'charge' => $charges[8]->id]))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->where('roster_count', 3)->where('unpaid_count', 2)
        ->where('rows.0.property_id', $property->id)->where('rows.1.property_id', $tie->id)
        ->where('rows.0.outstanding_balance', '500.00')->where('rows.0.this_period_status', 'unpaid')
        ->where('rows.0.oldest_open_label', 'Opening Balance')
        ->where('filter_options.blocks', ['2', '10'])
        ->where('filter_options.owes_for', [
            ['value' => 'opening', 'label' => 'Opening Balance'],
            ['value' => '2026-08', 'label' => 'August 2026'],
            ['value' => '2026-09', 'label' => 'September 2026'],
        ])
        ->where('selected.outstanding_balance', '500.00')->where('selected.remaining_opening_balance', '50.00')
        ->where('selected.prepaid_balance', '25.00')->has('selected.pending_declarations', 1)
        ->where('selected.periods.0.status', 'unpaid')->where('selected.periods.0.remaining', '300.00')
        ->where('selected.periods.0.payments', [])
        ->where('selected.periods.1.status', 'partial')->where('selected.periods.1.remaining', '150.00')
        ->where('selected.periods.1.charge_total', '200.00')
        ->where('selected.periods.1.payments.0.id', $confirmed->id)
        ->where('selected.periods.1.payments.0.amount', '50.00')
        ->where('selected.periods.2.status', 'paid')->where('selected.periods.2.remaining', '0.00')
        ->where('selected.periods.3.status', 'paid')->where('selected.periods.3.charge_total', '0.00')
        ->where('selected.selected_charge_id', $charges[8]->id)
        ->where('selected.selected_period.remaining', '150.00'));
});

test('roster and selected Statement batch financial queries as Properties and periods grow', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->withOpeningBalance('100.00')->create();
    $charge = Charge::factory()->create(['property_id' => $property->id, 'year' => 2025, 'month' => 1]);
    ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
    $payment = Payment::factory()->confirmed()->create([
        'property_id' => $property->id, 'declared_by_user_id' => $officer->id,
        'confirmed_by_user_id' => $officer->id, 'amount' => '10.00',
    ]);
    PaymentAllocation::query()->create([
        'payment_id' => $payment->id, 'property_id' => $property->id,
        'target' => PaymentAllocationTarget::Charge, 'charge_id' => $charge->id, 'amount' => '10.00',
    ]);
    $url = route('officer.unpaid.index', ['property' => $property->id]);
    $this->actingAs($officer)->get($url)->assertOk();
    $capture = function () use ($url): array {
        DB::enableQueryLog();
        DB::flushQueryLog();
        try {
            $this->get($url)->assertOk();

            return DB::getQueryLog();
        } finally {
            DB::disableQueryLog();
            DB::flushQueryLog();
        }
    };
    $small = $capture();

    $properties = Property::factory()->count(11)->withOpeningBalance('100.00')->create();
    $properties->prepend($property);
    $payment->update(['amount' => '120.00']);
    foreach ($properties as $fixtureProperty) {
        $fixturePayment = $fixtureProperty->id === $property->id ? $payment : Payment::factory()->confirmed()->create([
            'property_id' => $fixtureProperty->id, 'declared_by_user_id' => $officer->id,
            'confirmed_by_user_id' => $officer->id, 'amount' => '120.00',
        ]);
        foreach (range(1, 12) as $month) {
            if ($fixtureProperty->id === $property->id && $month === 1) {
                continue;
            }
            $fixtureCharge = Charge::factory()->create(['property_id' => $fixtureProperty->id, 'year' => 2025, 'month' => $month]);
            ChargeLine::factory()->create(['charge_id' => $fixtureCharge->id, 'amount' => '100.00']);
            PaymentAllocation::query()->create([
                'payment_id' => $fixturePayment->id, 'property_id' => $fixtureProperty->id,
                'target' => PaymentAllocationTarget::Charge, 'charge_id' => $fixtureCharge->id, 'amount' => '10.00',
            ]);
        }
    }

    $large = $capture();
    $allocationQueries = fn (array $queries): int => count(array_filter($queries,
        fn (array $query): bool => str_contains($query['query'], '"payment_allocations"')));

    expect($allocationQueries($large))->toBeLessThanOrEqual(2);
    expect(count($large))->toBeLessThanOrEqual(count($small) + 2);
    $this->get($url)->assertInertia(fn ($page) => $page
        ->where('unpaid_count', 12)->has('selected.periods', 12)
        ->where('selected.outstanding_balance', '1180.00'));
});

test('later roster requests read fresh financials after confirmation and voiding', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->withOpeningBalance('100.00')->create();
    $charge = Charge::factory()->create(['property_id' => $property->id, 'year' => 2026, 'month' => 9]);
    ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '200.00']);
    $payment = Payment::factory()->pending()->create([
        'property_id' => $property->id, 'declared_by_user_id' => $officer->id, 'amount' => '150.00',
    ]);
    $url = route('officer.unpaid.index', ['property' => $property->id]);
    $this->actingAs($officer)->get($url)->assertInertia(fn ($page) => $page
        ->where('selected.outstanding_balance', '300.00')->has('selected.pending_declarations', 1));

    $this->post(route('officer.payments.confirm', $payment))->assertRedirect();
    $this->get($url)->assertInertia(fn ($page) => $page
        ->where('rows.0.outstanding_balance', '150.00')->where('rows.0.this_period_status', 'partial')
        ->where('rows.0.oldest_open_label', 'September 2026')
        ->where('selected.remaining_opening_balance', '0.00')->where('selected.outstanding_balance', '150.00')
        ->has('selected.pending_declarations', 0)->where('selected.periods.0.payments.0.amount', '50.00'));

    $this->post(route('officer.payments.void', $payment), ['void_reason' => 'Duplicate entry.'])->assertRedirect();
    $this->get($url)->assertInertia(fn ($page) => $page
        ->where('rows.0.outstanding_balance', '300.00')->where('rows.0.this_period_status', 'unpaid')
        ->where('selected.remaining_opening_balance', '100.00')->where('selected.outstanding_balance', '300.00')
        ->where('selected.periods.0.payments', []));
});
