<?php

use App\Actions\Payments\ConfirmPayment;
use App\Actions\Payments\VoidPayment;
use App\Enums\PlatformRole;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('Property switching keeps owner and resident financials separate with matching switcher totals', function () {
    $member = User::factory()->create();
    $member->assignPlatformRole(PlatformRole::Member);
    $officer = User::factory()->officer()->create();
    $first = Property::factory()->withOpeningBalance('100.00')->create();
    $second = Property::factory()->create();
    $ended = Property::factory()->withOpeningBalance('900.00')->create();
    $unrelated = Property::factory()->withOpeningBalance('1100.00')->create();
    Membership::factory()->owner()->create(['user_id' => $member->id, 'property_id' => $first->id]);
    Membership::factory()->resident()->create(['user_id' => $member->id, 'property_id' => $second->id]);
    Membership::factory()->ended()->create(['user_id' => $member->id, 'property_id' => $ended->id]);
    $charges = [];
    foreach ([[$first, [7 => '200.00', 8 => '0.00', 9 => '300.00']], [$second, [7 => '100.00', 8 => '200.00', 9 => '0.00']]] as [$property, $amounts]) {
        foreach ($amounts as $month => $amount) {
            $charge = Charge::factory()->create(['property_id' => $property->id, 'year' => 2026, 'month' => $month]);
            ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => $amount, 'fee_type_name' => 'Guard snapshot']);
            $charges[$property->id][$month] = $charge;
        }
    }
    $firstPayment = Payment::factory()->pending()->create([
        'property_id' => $first->id, 'declared_by_user_id' => $member->id, 'amount' => '250.00',
    ]);
    app(ConfirmPayment::class)->handle($firstPayment, $officer);
    $secondPayment = Payment::factory()->pending()->create([
        'property_id' => $second->id, 'declared_by_user_id' => $member->id, 'amount' => '350.00',
    ]);
    app(ConfirmPayment::class)->handle($secondPayment, $officer);
    $pending = Payment::factory()->pending()->create([
        'property_id' => $first->id, 'declared_by_user_id' => $member->id, 'amount' => '100.00',
    ]);

    $this->actingAs($member)->get(route('statement-of-account.show', ['property' => $first, 'charge' => $charges[$first->id][7]->id]))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->component('statement-of-account/Show')->where('property.id', $first->id)
        ->where('outstanding_balance', '350.00')->where('remaining_opening_balance', '0.00')
        ->has('switcher', 2)->where('switcher.0.property_id', $first->id)->where('switcher.0.outstanding_balance', '350.00')
        ->where('switcher.1.property_id', $second->id)->where('switcher.1.outstanding_balance', '0.00')
        ->missing('combined_outstanding_balance')->where('pending_declarations.0.id', $pending->id)
        ->where('periods.0.status', 'unpaid')->where('periods.1.status', 'paid')->where('periods.2.status', 'partial')
        ->where('selected_charge_id', $charges[$first->id][7]->id)->where('selected_period.remaining', '50.00')
        ->where('selected_period.lines.0.fee_type_name', 'Guard snapshot')
        ->has('selected_period.payments', 1)->where('selected_period.payments.0.id', $firstPayment->id)
        ->where('selected_period.payments.0.amount', '150.00'));

    $this->get(route('statement-of-account.show', ['property' => $second, 'charge' => $charges[$second->id][7]->id]))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->where('property.id', $second->id)->where('outstanding_balance', '0.00')->where('prepaid_balance', '50.00')
        ->where('switcher.1.outstanding_balance', '0.00')->where('pending_declarations', [])
        ->where('selected_period.status', 'paid')->where('selected_period.payments.0.id', $secondPayment->id)
        ->where('selected_period.payments.0.amount', '100.00'));

    $this->get(route('statement-of-account.show', ['property' => $first, 'charge' => $charges[$second->id][7]->id]))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->where('selected_charge_id', $charges[$first->id][9]->id)->where('selected_period.payments', []));
    $this->get(route('statement-of-account.show', ['property' => $second, 'charge' => $charges[$first->id][7]->id]))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->where('selected_charge_id', $charges[$second->id][9]->id)->where('selected_period.charge_total', '0.00'));
    $this->get(route('statement-of-account.show', $ended))->assertForbidden();
    $this->get(route('statement-of-account.show', $unrelated))->assertForbidden();

    app(VoidPayment::class)->handle($firstPayment->refresh(), $officer, 'Duplicate entry.');
    $this->get(route('statement-of-account.show', ['property' => $first, 'charge' => $charges[$first->id][7]->id]))
        ->assertOk()->assertInertia(fn ($page) => $page
        ->where('outstanding_balance', '600.00')->where('remaining_opening_balance', '100.00')
        ->where('switcher.0.outstanding_balance', '600.00')->where('switcher.1.outstanding_balance', '0.00')
        ->has('pending_declarations', 1)->where('selected_period.remaining', '200.00')
        ->where('selected_period.status', 'unpaid')->where('selected_period.payments', []));
});

test('a User Account with no Membership cannot access Property financials', function () {
    $member = User::factory()->create();
    $member->assignPlatformRole(PlatformRole::Member);
    $property = Property::factory()->withOpeningBalance('100.00')->create();

    $this->actingAs($member)->get(route('statement-of-account.show', $property))->assertForbidden();
});

test('Member financial query counts stay bounded as Memberships and Billing Periods grow', function () {
    $member = User::factory()->create();
    $member->assignPlatformRole(PlatformRole::Member);
    $officer = User::factory()->officer()->create();
    $first = Property::factory()->create();
    Membership::factory()->owner()->create(['user_id' => $member->id, 'property_id' => $first->id]);
    $charge = Charge::factory()->create(['property_id' => $first->id, 'year' => 2025, 'month' => 1]);
    ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
    $payment = Payment::factory()->pending()->create([
        'property_id' => $first->id, 'declared_by_user_id' => $member->id, 'amount' => '10.00',
    ]);
    app(ConfirmPayment::class)->handle($payment, $officer);
    $url = route('statement-of-account.show', $first);
    $this->actingAs($member)->get($url)->assertOk();
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
    $properties = Property::factory()->count(3)->create();
    foreach ($properties as $property) {
        Membership::factory()->resident()->create(['user_id' => $member->id, 'property_id' => $property->id]);
    }
    $properties->prepend($first);
    foreach ($properties as $property) {
        foreach (range(1, 12) as $month) {
            if ($property->id === $first->id && $month === 1) {
                continue;
            }
            $charge = Charge::factory()->create(['property_id' => $property->id, 'year' => 2025, 'month' => $month]);
            ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
        }
        $payment = Payment::factory()->pending()->create([
            'property_id' => $property->id, 'declared_by_user_id' => $member->id, 'amount' => '600.00',
        ]);
        app(ConfirmPayment::class)->handle($payment, $officer);
    }

    $large = $capture();
    $allocationQueries = fn (array $queries): int => count(array_filter($queries,
        fn (array $query): bool => str_contains($query['query'], '"payment_allocations"')));

    expect($allocationQueries($large))->toBeLessThanOrEqual(2);
    expect(count($large))->toBeLessThanOrEqual(count($small) + 2);
    $this->get($url)->assertInertia(fn ($page) => $page
        ->has('switcher', 4)->has('periods', 12)->where('outstanding_balance', '590.00')
        ->where('switcher.0.outstanding_balance', '590.00')->where('switcher.1.outstanding_balance', '600.00'));
});
