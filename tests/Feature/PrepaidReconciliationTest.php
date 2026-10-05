<?php

use App\Actions\Payments\ConfirmPayment;
use App\Enums\PaymentAllocationTarget;
use App\Enums\PaymentStatus;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\Membership;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\Exceptions;
use Inertia\Testing\AssertableInertia;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

test('voiding a Payment reapplies remaining confirmed Prepaid to its reopened Charge', function (string $firstAmount, string $sourceAmount, string $expectedPrepaid) {
    Pdf::fake();
    $officer = User::factory()->officer()->create();
    $member = User::factory()->member()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->create(['user_id' => $member->id, 'property_id' => $property->id]);
    $charge = Charge::factory()->create(['property_id' => $property->id]);
    ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
    $first = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => $firstAmount]);
    $remaining = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => $sourceAmount]);

    foreach ([$first, $remaining] as $payment) {
        $this->actingAs($officer)->post(route('officer.payments.confirm', $payment))->assertRedirect();
    }

    $this->actingAs($officer)->post(route('officer.payments.void', $first), ['void_reason' => 'Duplicate entry.'])->assertRedirect();

    expect($remaining->fresh()->prepaid_amount)->toBe($expectedPrepaid);
    expect($property->fresh()->prepaid_balance)->toBe($expectedPrepaid);
    expect($remaining->allocations()->sole()->amount)->toBe('100.00');
    expect($remaining->allocations()->sole()->charge_id)->toBe($charge->id);
    expect($remaining->allocations()->sole()->target)->toBe(PaymentAllocationTarget::Charge);
    expect($first->fresh()->status)->toBe(PaymentStatus::Voided);
    expect($first->fresh()->void_reason)->toBe('Duplicate entry.');
    expect($first->fresh()->voided_by_user_id)->toBe($officer->id);
    expect($first->allocations()->count())->toBe(0);
    expect($charge->fresh()->isFrozen())->toBeTrue();

    $this->actingAs($member)->get(route('statement-of-account.show', $property))
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->where('outstanding_balance', '0.00')->where('prepaid_balance', $expectedPrepaid)
            ->where('selected_period.status', 'paid')->where('selected_period.payments.0.id', $remaining->id));
    $this->actingAs($officer)->get(route('officer.unpaid.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('unpaid_count', 0)->has('rows', 0));
    $this->actingAs($officer)->get(route('officer.printed-bills.show', ['property' => $property, 'year' => $charge->year, 'month' => $charge->month]))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewData['bills'][0]['outstanding_balance'] === '0.00');

    $this->actingAs($officer)->post(route('officer.payments.void', $first), ['void_reason' => 'Repeat.'])
        ->assertSessionHasErrors('payment');
    expect($remaining->allocations()->count())->toBe(1);
    expect($remaining->fresh()->prepaid_amount)->toBe($expectedPrepaid);
})->with([
    'exact replacement' => ['100.00', '100.00', '0.00'],
    'surplus after removing the voided source residual' => ['120.00', '150.00', '50.00'],
]);

test('insufficient Prepaid leaves the same debt across financial outputs', function (string $operation) {
    Pdf::fake();
    $officer = User::factory()->officer()->create();
    $member = User::factory()->member()->create();
    $property = Property::factory()->create();
    Membership::factory()->owner()->create(['user_id' => $member->id, 'property_id' => $property->id]);
    $charge = Charge::factory()->create(['property_id' => $property->id]);

    if ($operation === 'void') {
        ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
        $first = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => '100.00']);
        $this->actingAs($officer)->post(route('officer.payments.confirm', $first))->assertRedirect();
    }

    $source = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => '40.00']);
    $this->actingAs($officer)->post(route('officer.payments.confirm', $source))->assertRedirect();

    if ($operation === 'void') {
        $this->post(route('officer.payments.void', $first), ['void_reason' => 'Duplicate.'])->assertRedirect();
    } else {
        $this->put(route('officer.charges.update', $charge), ['lines' => [['fee_type_name' => 'Guard', 'amount' => '100.00']]])->assertRedirect();
    }

    expect($source->allocations()->sole()->amount)->toBe('40.00');
    expect($source->fresh()->prepaid_amount)->toBe('0.00');
    expect($property->fresh()->prepaid_balance)->toBe('0.00');
    expect($charge->fresh()->isFrozen())->toBeTrue();
    $this->actingAs($member)->get(route('statement-of-account.show', $property))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('outstanding_balance', '60.00')->where('prepaid_balance', '0.00'));
    $this->actingAs($officer)->get(route('officer.unpaid.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('unpaid_count', 1)->has('rows', 1)->where('rows.0.outstanding_balance', '60.00'));
    $this->get(route('officer.printed-bills.show', ['property' => $property, 'year' => $charge->year, 'month' => $charge->month]))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewData['bills'][0]['outstanding_balance'] === '60.00');
})->with(['void', 'edit']);

test('void reconciliation pays Opening Balance then the oldest Charge using only confirmed sources on the Property', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create(['opening_balance' => '100.00']);
    $newer = Charge::factory()->create(['property_id' => $property->id, 'year' => 2026, 'month' => 9]);
    $older = Charge::factory()->create(['property_id' => $property->id, 'year' => 2026, 'month' => 8]);
    foreach ([$newer, $older] as $charge) {
        ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
    }
    $first = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => '300.00']);
    $source = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => '150.00']);
    foreach ([$first, $source] as $payment) {
        $this->actingAs($officer)->post(route('officer.payments.confirm', $payment))->assertRedirect();
    }
    $excluded = collect([PaymentStatus::Pending, PaymentStatus::Rejected, PaymentStatus::Voided])
        ->map(fn (PaymentStatus $status) => Payment::factory()->create(['property_id' => $property->id, 'status' => $status, 'amount' => '500.00', 'prepaid_amount' => '500.00']));
    $other = Payment::factory()->create(['status' => PaymentStatus::Confirmed, 'prepaid_amount' => '500.00']);

    $this->post(route('officer.payments.void', $first), ['void_reason' => 'Duplicate.'])->assertRedirect();

    $allocations = $source->allocations()->orderBy('id')->get();
    expect($allocations)->toHaveCount(2);
    expect($allocations[0]->target)->toBe(PaymentAllocationTarget::OpeningBalance);
    expect($allocations[0]->amount)->toBe('100.00');
    expect($allocations[1]->charge_id)->toBe($older->id);
    expect($allocations[1]->amount)->toBe('50.00');
    expect($source->fresh()->prepaid_amount)->toBe('0.00');
    expect($property->fresh()->prepaid_balance)->toBe('0.00');
    expect($older->fresh()->isFrozen())->toBeTrue();
    expect($newer->fresh()->isFrozen())->toBeFalse();
    foreach ($excluded->push($other) as $payment) {
        expect($payment->allocations()->count())->toBe(0);
        expect($payment->fresh()->prepaid_amount)->toBe('500.00');
    }
});

test('failed allocation persistence rolls back the entire Officer operation', function (string $operation) {
    Exceptions::fake();
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create();
    $charge = Charge::factory()->create(['property_id' => $property->id]);
    if ($operation === 'void') {
        ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
        $first = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => '100.00']);
        $this->actingAs($officer)->post(route('officer.payments.confirm', $first))->assertRedirect();
    }
    $source = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => '100.00']);
    $this->actingAs($officer)->post(route('officer.payments.confirm', $source))->assertRedirect();

    PaymentAllocation::creating(function (): void {
        throw new RuntimeException('Allocation persistence failed.');
    });
    try {
        if ($operation === 'void') {
            $this->post(route('officer.payments.void', $first), ['void_reason' => 'Duplicate.'])->assertServerError();
        } else {
            $this->put(route('officer.charges.update', $charge), ['lines' => [['fee_type_name' => 'Guard', 'amount' => '60.00']]])->assertServerError();
        }
    } finally {
        PaymentAllocation::flushEventListeners();
    }

    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'Allocation persistence failed.');
    expect($property->fresh()->prepaid_balance)->toBe('100.00');
    expect($source->fresh()->prepaid_amount)->toBe('100.00');
    expect($source->allocations()->count())->toBe(0);
    if ($operation === 'void') {
        expect($first->fresh()->status)->toBe(PaymentStatus::Confirmed);
        expect($first->fresh()->voided_at)->toBeNull();
        expect($first->fresh()->void_reason)->toBeNull();
        expect($first->allocations()->sole()->amount)->toBe('100.00');
        expect($charge->fresh()->isFrozen())->toBeTrue();
    } else {
        expect($charge->fresh()->lines)->toHaveCount(0);
        expect($charge->fresh()->isFrozen())->toBeFalse();
    }
})->with(['void', 'edit']);

test('editing an unfrozen zero Charge applies confirmed Prepaid before financial displays update', function () {
    Pdf::fake();
    $officer = User::factory()->officer()->create();
    $member = User::factory()->member()->create();
    $property = Property::factory()->create();
    Membership::factory()->resident()->create(['user_id' => $member->id, 'property_id' => $property->id]);
    $charge = Charge::factory()->create(['property_id' => $property->id]);
    $payment = Payment::factory()->pending()->create(['property_id' => $property->id, 'amount' => '100.00']);
    $this->actingAs($officer)->post(route('officer.payments.confirm', $payment))->assertRedirect();

    $this->actingAs($officer)->put(route('officer.charges.update', $charge), [
        'lines' => [['fee_type_name' => 'Guard', 'amount' => '60.00']],
    ])->assertRedirect(route('officer.charges.edit', $charge));

    expect($property->fresh()->prepaid_balance)->toBe('40.00');
    expect($payment->fresh()->prepaid_amount)->toBe('40.00');
    expect($payment->allocations()->sole()->amount)->toBe('60.00');
    expect($payment->allocations()->sole()->charge_id)->toBe($charge->id);
    expect($charge->fresh()->isFrozen())->toBeTrue();

    $this->actingAs($member)->get(route('statement-of-account.show', $property))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('outstanding_balance', '0.00')
            ->where('prepaid_balance', '40.00')->where('selected_period.status', 'paid')
            ->where('selected_period.payments.0.id', $payment->id));
    $this->actingAs($officer)->get(route('officer.unpaid.index'))
        ->assertInertia(fn (AssertableInertia $page) => $page->where('unpaid_count', 0)->has('rows', 0));
    $this->actingAs($officer)->get(route('officer.printed-bills.show', ['property' => $property, 'year' => $charge->year, 'month' => $charge->month]))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewData['bills'][0]['outstanding_balance'] === '0.00');

    $this->actingAs($officer)->put(route('officer.charges.update', $charge), [
        'lines' => [['fee_type_name' => 'Guard', 'amount' => '90.00']],
    ])->assertSessionHas('error', 'Charge is frozen after a confirmed Payment.');
    expect($charge->fresh()->lines->sole()->amount)->toBe('60.00');
    expect($payment->fresh()->prepaid_amount)->toBe('40.00');
});

test('Charge editing rechecks eligibility when confirmation freezes the route-bound Charge', function () {
    $officer = User::factory()->officer()->create();
    $charge = Charge::factory()->create();
    $line = ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
    $payment = Payment::factory()->pending()->create(['property_id' => $charge->property_id, 'amount' => '100.00']);
    $confirmed = false;
    Charge::retrieved(function (Charge $retrieved) use ($charge, $payment, $officer, &$confirmed): void {
        if ($retrieved->id !== $charge->id || $confirmed) {
            return;
        }
        $confirmed = true;
        app(ConfirmPayment::class)->handle($payment, $officer);
    });

    try {
        $this->actingAs($officer)->put(route('officer.charges.update', $charge), [
            'lines' => [['id' => $line->id, 'fee_type_name' => 'Guard', 'amount' => '50.00']],
        ])->assertSessionHas('error', 'Charge is frozen after a confirmed Payment.');
    } finally {
        Charge::flushEventListeners();
    }

    expect($confirmed)->toBeTrue();
    expect($line->fresh()->amount)->toBe('100.00');
    expect($charge->fresh()->isFrozen())->toBeTrue();
    expect($payment->allocations()->sole()->amount)->toBe('100.00');
});

test('a stale confirmation request cannot confirm the same Payment twice', function () {
    $officer = User::factory()->officer()->create();
    $payment = Payment::factory()->pending()->create(['amount' => '100.00']);
    $confirmed = false;
    Payment::retrieved(function (Payment $retrieved) use ($payment, $officer, &$confirmed): void {
        if ($retrieved->id !== $payment->id || $confirmed) {
            return;
        }
        $confirmed = true;
        app(ConfirmPayment::class)->handle($retrieved, $officer);
    });

    try {
        $this->actingAs($officer)->post(route('officer.payments.confirm', $payment))->assertSessionHasErrors('payment');
    } finally {
        Payment::flushEventListeners();
    }

    expect($confirmed)->toBeTrue();
    expect($payment->fresh()->status)->toBe(PaymentStatus::Confirmed);
    expect($payment->fresh()->prepaid_amount)->toBe('100.00');
    expect($payment->property->prepaid_balance)->toBe('100.00');
    expect($payment->allocations()->count())->toBe(0);
});
