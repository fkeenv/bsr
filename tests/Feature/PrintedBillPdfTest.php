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
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-09-15 10:00:00', 'Asia/Manila'));
    Pdf::fake();
});

afterEach(function () {
    Carbon::setTestNow();
});

test('Officer can generate a single-Property Printed Bill PDF for a Billing Period', function () {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create([
        'block' => '12',
        'lot' => '5',
        'recorded_owner_name' => 'Juan Dela Cruz',
        'opening_balance' => '100.00',
    ]);

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

    $september = Charge::factory()->create([
        'property_id' => $property->id,
        'year' => 2026,
        'month' => 9,
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $september->id,
        'fee_type_name' => 'Guard',
        'amount' => '200.00',
    ]);
    ChargeLine::factory()->create([
        'charge_id' => $september->id,
        'fee_type_name' => 'Garbage collection',
        'amount' => '200.00',
    ]);

    $this->actingAs($officer)
        ->get(route('officer.printed-bills.show', [
            'property' => $property,
            'year' => 2026,
            'month' => 9,
        ]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) {
        $html = $pdf->getHtml();
        $bill = $pdf->viewData['bills'][0] ?? null;

        return $pdf->isDownload()
            && str_contains((string) $pdf->downloadName, 'printed-bill')
            && $pdf->viewName === 'pdfs.printed-bill'
            && ($pdf->viewData['letterhead']['name'] ?? null) === 'Blessed Sacrament Residences Homeowners Association'
            && $bill !== null
            && $bill['bill_to'] === 'Juan Dela Cruz'
            && $bill['property_label'] === 'Block 12 · Lot 5'
            && $bill['period_label'] === 'September 2026'
            && $bill['balance_forward'] === '300.00'
            && $bill['outstanding_balance'] === '700.00'
            && collect($bill['lines'])->pluck('name')->all() === ['Guard', 'Garbage collection']
            && str_contains($html, 'Juan Dela Cruz')
            && str_contains($html, '700.00');
    });
});

test('Members cannot generate Printed Bills', function () {
    $member = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '50.00']);
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $this->actingAs($member)
        ->get(route('officer.printed-bills.show', [
            'property' => $property,
            'year' => 2026,
            'month' => 9,
        ]))
        ->assertForbidden();
});

test('Members cannot batch-print Printed Bills', function () {
    $member = User::factory()->create();
    $property = Property::factory()->create(['opening_balance' => '50.00']);
    Membership::factory()->owner()->create([
        'user_id' => $member->id,
        'property_id' => $property->id,
    ]);
    $member->assignPlatformRole(PlatformRole::Member);

    $this->actingAs($member)
        ->get(route('officer.printed-bills.batch', [
            'year' => 2026,
            'month' => 9,
        ]))
        ->assertForbidden();
});

test('Officer can batch-print unpaid Properties into one stacked PDF', function () {
    $officer = User::factory()->officer()->create();

    $unpaid = Property::factory()->create([
        'block' => '1',
        'lot' => '1',
        'recorded_owner_name' => 'Unpaid Owner',
        'opening_balance' => '500.00',
    ]);
    $cleared = Property::factory()->create([
        'block' => '2',
        'lot' => '2',
        'recorded_owner_name' => 'Cleared Owner',
        'opening_balance' => '0.00',
    ]);

    foreach ([$unpaid, $cleared] as $property) {
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
    }

    $payment = Payment::factory()->pending()->create([
        'property_id' => $cleared->id,
        'amount' => '200.00',
        'method' => PaymentMethod::Cash,
    ]);
    app(ConfirmPayment::class)->handle($payment, $officer);

    $this->actingAs($officer)
        ->get(route('officer.printed-bills.batch', [
            'year' => 2026,
            'month' => 9,
        ]))
        ->assertOk();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) {
        $bills = $pdf->viewData['bills'] ?? [];
        $billTos = collect($bills)->pluck('bill_to')->all();

        return $pdf->isDownload()
            && str_contains((string) $pdf->downloadName, 'printed-bills')
            && in_array('Unpaid Owner', $billTos, true)
            && ! in_array('Cleared Owner', $billTos, true)
            && collect($bills)->contains(
                fn (array $bill): bool => $bill['bill_to'] === 'Unpaid Owner'
                    && $bill['outstanding_balance'] === '700.00',
            );
    });
});
