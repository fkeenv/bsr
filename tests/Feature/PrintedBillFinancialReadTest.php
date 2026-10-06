<?php

use App\Actions\Charges\UpdateChargeLines;
use App\Actions\Payments\ApplyPrepaidToProperty;
use App\Actions\Payments\ConfirmPayment;
use App\Actions\Payments\VoidPayment;
use App\Models\AssociationSetting;
use App\Models\Charge;
use App\Models\ChargeLine;
use App\Models\Payment;
use App\Models\Property;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function () {
    Pdf::fake();
});

test('Printed Bill fixture preserves historical lines current debt fallback and batch ordering', function (string $mode) {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->inactive()->withOpeningBalance('100.00')->create([
        'block' => '10', 'lot' => '1', 'recorded_owner_name' => null, 'street_address' => '10 Example Street',
    ]);
    Property::factory()->withOpeningBalance('200.00')->create([
        'block' => '2', 'lot' => '2', 'recorded_owner_name' => 'Opening Owner',
    ]);
    foreach ([7 => '200.00', 8 => '300.00', 9 => '400.00'] as $month => $amount) {
        $charge = Charge::factory()->create(['property_id' => $property->id, 'year' => 2026, 'month' => $month]);
        ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => $amount, 'fee_type_name' => 'Historical Guard']);
    }
    $payment = Payment::factory()->pending()->create([
        'property_id' => $property->id, 'declared_by_user_id' => $officer->id, 'amount' => '50.00',
    ]);
    app(ConfirmPayment::class)->handle($payment, $officer);
    Payment::factory()->pending()->create([
        'property_id' => $property->id, 'declared_by_user_id' => $officer->id, 'amount' => '950.00',
    ]);
    AssociationSetting::current()->update(['letterhead_name' => 'Example Association', 'letterhead_contact' => 'billing@example.test']);
    $parameters = ['year' => 2026, 'month' => 8];
    if ($mode === 'single') {
        $parameters['property'] = $property;
    }

    $this->actingAs($officer)->get(route($mode === 'single' ? 'officer.printed-bills.show' : 'officer.printed-bills.batch', $parameters))->assertOk();

    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($mode): bool {
        expect($pdf->viewData['bills'][0])->toBe([
            'bill_to' => 'Block 10 · Lot 1', 'property_label' => 'Block 10 · Lot 1', 'address' => '10 Example Street',
            'period_label' => 'August 2026', 'lines' => [['name' => 'Historical Guard', 'amount' => '300.00']],
            'period_total' => '300.00', 'balance_forward' => '250.00', 'opening_balance_remaining' => '50.00',
            'older_unpaid_labels' => ['July 2026'], 'outstanding_balance' => '950.00',
        ]);
        expect($pdf->viewData['letterhead']['name'])->toBe('Example Association');
        expect($pdf->viewData['letterhead']['contact'])->toBe('billing@example.test');
        expect($pdf->isDownload())->toBeTrue();
        expect($pdf->downloadName)->toBe($mode === 'single' ? 'printed-bill-b10-l1-2026-08.pdf' : 'printed-bills-unpaid-2026-08.pdf');
        if ($mode === 'batch') {
            expect(collect($pdf->viewData['bills'])->pluck('bill_to')->all())->toBe(['Block 10 · Lot 1', 'Opening Owner']);
            expect($pdf->viewData['bills'][1]['balance_forward'])->toBe('200.00');
            expect($pdf->viewData['bills'][1]['lines'])->toBe([]);
        }
        expect($pdf->getHtml())->toContain('Historical Guard', '950.00', '10 Example Street', 'Example Association');

        return true;
    });
})->with(['single', 'batch']);

test('Printed Bills refresh amounts and inclusion after confirmation Prepaid void and Charge changes', function (string $mode) {
    $officer = User::factory()->officer()->create();
    $property = Property::factory()->create(['block' => '10', 'lot' => '1']);
    $july = Charge::factory()->create(['property_id' => $property->id, 'year' => 2026, 'month' => 7]);
    $line = ChargeLine::factory()->create(['charge_id' => $july->id, 'amount' => '200.00']);
    $parameters = ['year' => 2026, 'month' => 7];
    if ($mode === 'single') {
        $parameters['property'] = $property;
    }
    $url = route($mode === 'single' ? 'officer.printed-bills.show' : 'officer.printed-bills.batch', $parameters);
    $assertBill = function (string $outstanding, string $periodTotal) use ($officer, $url, $mode): void {
        Pdf::fake();
        $this->actingAs($officer)->get($url)->assertOk();
        Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($outstanding, $periodTotal, $mode): bool {
            $bills = $pdf->viewData['bills'];
            if ($mode === 'batch' && $outstanding === '0.00') {
                expect($bills)->toBe([]);
            } else {
                expect($bills)->toHaveCount(1);
                expect($bills[0]['outstanding_balance'])->toBe($outstanding);
                expect($bills[0]['period_total'])->toBe($periodTotal);
                expect($bills[0]['balance_forward'])->toBe('0.00');
            }

            return true;
        });
    };
    $assertBill('200.00', '200.00');
    $payment = Payment::factory()->pending()->create([
        'property_id' => $property->id, 'declared_by_user_id' => $officer->id, 'amount' => '250.00',
    ]);

    app(ConfirmPayment::class)->handle($payment, $officer);
    $assertBill('0.00', '200.00');
    $august = Charge::factory()->create(['property_id' => $property->id, 'year' => 2026, 'month' => 8]);
    ChargeLine::factory()->create(['charge_id' => $august->id, 'amount' => '100.00']);
    app(ApplyPrepaidToProperty::class)->handle($property);
    $assertBill('50.00', '200.00');
    app(VoidPayment::class)->handle($payment->refresh(), $officer, 'Duplicate entry.');
    $assertBill('300.00', '200.00');
    app(UpdateChargeLines::class)->handle($july->refresh(), ['lines' => [
        ['id' => $line->id, 'fee_type_name' => 'Corrected Guard', 'amount' => '150.00'],
    ]]);
    $assertBill('250.00', '150.00');
})->with(['single', 'batch']);

test('Printed Bill financial queries stay bounded as roster and history grow', function (string $mode) {
    $officer = User::factory()->officer()->create();
    $first = Property::factory()->withOpeningBalance('100.00')->create();
    $charge = Charge::factory()->create(['property_id' => $first->id, 'year' => 2025, 'month' => 1]);
    ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
    $parameters = ['year' => 2025, 'month' => 12];
    if ($mode === 'single') {
        $parameters['property'] = $first;
    }
    $url = route($mode === 'single' ? 'officer.printed-bills.show' : 'officer.printed-bills.batch', $parameters);
    $this->actingAs($officer)->get($url)->assertOk();
    $capture = function () use ($url): array {
        Pdf::fake();
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
    $properties = Property::factory()->count(7)->withOpeningBalance('100.00')->create();
    $properties->prepend($first);
    foreach ($properties as $property) {
        foreach (range(1, 12) as $month) {
            if ($property->id === $first->id && $month === 1) {
                continue;
            }
            $charge = Charge::factory()->create(['property_id' => $property->id, 'year' => 2025, 'month' => $month]);
            ChargeLine::factory()->create(['charge_id' => $charge->id, 'amount' => '100.00']);
        }
    }

    $large = $capture();
    $allocationQueries = fn (array $queries): int => count(array_filter($queries,
        fn (array $query): bool => str_contains($query['query'], '"payment_allocations"')));

    expect($allocationQueries($large))->toBeLessThanOrEqual(1);
    expect(count($large))->toBeLessThanOrEqual(count($small) + 2);
    Pdf::assertRespondedWithPdf(function (PdfBuilder $pdf) use ($mode): bool {
        expect($pdf->viewData['bills'])->toHaveCount($mode === 'single' ? 1 : 8);
        foreach ($pdf->viewData['bills'] as $bill) {
            expect($bill['outstanding_balance'])->toBe('1300.00');
            expect($bill['balance_forward'])->toBe('1200.00');
            expect($bill['period_total'])->toBe('100.00');
        }

        return true;
    });
})->with(['single', 'batch']);
